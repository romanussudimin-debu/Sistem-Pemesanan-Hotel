<?php

// app/Services/ReservationService.php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\RoomNotAvailableException;
use App\Models\Branch;
use App\Models\Reservation;
use App\Models\RoomNight;
use App\Models\RoomType;
use App\Models\User;
use App\Notifications\ReservationNotification;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
        private readonly CancellationPolicy $policy,
    ) {}

    /**
     * UC-07: membuat reservasi dengan perlindungan anti-overbooking tiga lapis (Tahap 3, 3.3.3):
     *  1. Mutex: baris cabang dikunci FOR UPDATE sebagai pernyataan PERTAMA transaksi
     *     (satu reservasi selalu satu cabang, BR-03; sekaligus menjamin nomor urut kode reservasi unik).
     *  2. Cek ulang kamar bebas setelah kunci didapat, sehingga membaca data terbaru yang sudah di-commit.
     *  3. UNIQUE(room_id, stay_date) di room_nights sebagai jaring pengaman terakhir.
     *
     * @param  array{check_in: string, check_out: string, guest_name: string, guest_phone: string, special_request?: ?string,
     *               items: list<array{room_type_id: int, guests: list<int>}>}  $data  satu elemen `guests` = satu kamar
     */
    public function create(User $user, array $data): Reservation
    {
        $checkIn = CarbonImmutable::parse($data['check_in'])->startOfDay();
        $checkOut = CarbonImmutable::parse($data['check_out'])->startOfDay();
        $items = collect($data['items']);

        // Dibaca SEBELUM transaksi agar tidak membentuk snapshot REPEATABLE READ sebelum kunci diperoleh
        $branchIds = RoomType::whereIn('id', $items->pluck('room_type_id'))->distinct()->pluck('branch_id');
        if ($branchIds->count() !== 1) {
            throw new BusinessRuleException('messages.single_branch_only'); // BR-03
        }

        try {
            $reservation = DB::transaction(function () use ($user, $data, $items, $checkIn, $checkOut, $branchIds) {
                // LAPIS 1: mutex per cabang
                $branch = Branch::whereKey($branchIds->first())->lockForUpdate()->firstOrFail();

                $types = RoomType::with('rates')->whereIn('id', $items->pluck('room_type_id'))->get()->keyBy('id');
                $quotes = $items->mapWithKeys(fn ($item) => [
                    $item['room_type_id'] => $this->pricing->quote($types[$item['room_type_id']], $checkIn, $checkOut, count($item['guests'])),
                ]);

                $subtotal = $quotes->sum('subtotal');
                $taxRate = (float) config('hotel.tax_rate');
                $tax = round($subtotal * $taxRate / 100);
                $now = now();

                $reservation = new Reservation([
                    'code' => Reservation::generateCode($branch, $now),
                    'user_id' => $user->id,
                    'branch_id' => $branch->id,
                    'check_in' => $checkIn->toDateString(),
                    'check_out' => $checkOut->toDateString(),
                    'nights' => $checkIn->diffInDays($checkOut),
                    'total_guests' => $items->sum(fn ($i) => array_sum($i['guests'])),
                    'guest_name' => $data['guest_name'],
                    'guest_phone' => $data['guest_phone'],
                    'special_request' => $data['special_request'] ?? null,
                    'subtotal' => $subtotal,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $tax,
                    'total_amount' => $subtotal + $tax,
                    'payment_deadline' => Reservation::computeDeadline($now, $checkIn), // BR-09
                ]);
                $reservation->transitionTo(ReservationStatus::PendingPayment, $user, 'created'); // INSERT + log

                $dates = collect(CarbonPeriod::create($checkIn, $checkOut->subDay()))->map->toDateString();

                foreach ($items as $item) {
                    $type = $types[$item['room_type_id']];
                    $qty = count($item['guests']);

                    // LAPIS 2: cek ulang di dalam transaksi, setelah kunci didapat
                    $rooms = $this->availability->freeRooms($type->id, $checkIn, $checkOut)->limit($qty)->get();
                    if ($rooms->count() < $qty) {
                        throw new RoomNotAvailableException($type->name); // rollback otomatis
                    }

                    foreach ($rooms->values() as $i => $room) {
                        $line = $reservation->rooms()->create([
                            'room_id' => $room->id,
                            'room_type_id' => $type->id,
                            'guests' => $item['guests'][$i],
                            'rate_breakdown' => $quotes[$type->id]->nights, // snapshot tarif (BR-07)
                            'line_total' => $quotes[$type->id]->perRoom,
                        ]);

                        // LAPIS 3: UNIQUE(room_id, stay_date) menolak malam yang sudah terpakai
                        $line->nights()->createMany($dates->map(fn ($d) => ['room_id' => $room->id, 'stay_date' => $d])->all());
                    }
                }

                return $reservation;
            }, attempts: 3); // diulang otomatis bila terjadi deadlock
        } catch (UniqueConstraintViolationException $e) {
            Log::warning('Overbooking dicegah oleh indeks unik room_nights', ['user' => $user->id, 'error' => $e->getMessage()]);
            throw new RoomNotAvailableException;
        }

        $user->notify(new ReservationNotification($reservation, 'created'));

        return $reservation->load('rooms.roomType', 'branch');
    }

    /**
     * UC-13: pembatalan oleh pelanggan atau admin. Refund dihitung CancellationPolicy (BR-13/BR-14),
     * kamar langsung dilepas dengan menghapus room_nights di transaksi yang sama.
     */
    public function cancel(Reservation $reservation, User $actor, ?string $reason = null): Reservation
    {
        $reservation = DB::transaction(function () use ($reservation, $actor, $reason) {
            $locked = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isCancellable()) {
                throw new BusinessRuleException('messages.cannot_cancel'); // BR-15
            }

            $refund = $this->policy->refundAmount($locked);
            $locked->forceFill([
                'cancelled_by' => $actor->id,
                'cancel_reason' => $reason,
                'refund_amount' => $refund,
                'refund_status' => $refund > 0 ? RefundStatus::Pending : RefundStatus::None,
            ]);

            // Percobaan bayar yang masih menggantung ikut dibatalkan
            $locked->payments()->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Submitted])
                ->update(['status' => PaymentStatus::Cancelled]);

            $this->releaseInventory($locked);

            return $locked->transitionTo(ReservationStatus::Cancelled, $actor, $reason);
        });

        $reservation->user->notify(new ReservationNotification($reservation, 'cancelled'));

        return $reservation;
    }

    /**
     * FR-16: dipanggil scheduler setiap 5 menit. Setiap reservasi diproses dalam transaksi sendiri
     * dan statusnya dicek ulang setelah dikunci, sehingga aman bila callback pembayaran datang bersamaan.
     */
    public function expireOverdue(): int
    {
        $expired = 0;

        foreach (Reservation::overdue()->orderBy('id')->limit(500)->pluck('id') as $id) {
            $reservation = DB::transaction(function () use ($id) {
                $r = Reservation::whereKey($id)->lockForUpdate()->first();
                if (! $r || ! $r->isOverdue()) {
                    return null; // sudah dibayar/diproses proses lain
                }

                // Percobaan gateway yang menggantung sengaja dibiarkan Pending: bila callback sukses tiba
                // terlambat, PaymentService masih bisa memprosesnya dan mencoba alokasi ulang (Tahap 3, 3.3.4)
                $this->releaseInventory($r);

                return $r->transitionTo(ReservationStatus::Expired, null, 'auto-expire');
            });

            if ($reservation) {
                $expired++;
                $reservation->user->notify(new ReservationNotification($reservation, 'expired'));
            }
        }

        return $expired;
    }

    /** FR-29 */
    public function checkIn(Reservation $reservation, User $admin): Reservation
    {
        $today = today();
        if ($reservation->status !== ReservationStatus::Paid || $today->lt($reservation->check_in) || $today->gte($reservation->check_out)) {
            throw new BusinessRuleException('messages.cannot_check_in');
        }

        return DB::transaction(fn () => $reservation->transitionTo(ReservationStatus::CheckedIn, $admin));
    }

    /** FR-29 */
    public function checkOut(Reservation $reservation, User $admin): Reservation
    {
        if ($reservation->status !== ReservationStatus::CheckedIn) {
            throw new BusinessRuleException('messages.cannot_check_out');
        }

        return DB::transaction(fn () => $reservation->transitionTo(ReservationStatus::Completed, $admin));
    }

    /**
     * Tahap 3, 3.3.4: callback gateway sukses tiba setelah reservasi kedaluwarsa.
     * Mencoba mengalokasikan ulang kamar yang sama; mengembalikan true bila berhasil.
     * Harus dipanggil di dalam transaksi yang sudah mengunci baris reservasi.
     */
    public function tryReallocate(Reservation $reservation): bool
    {
        Branch::whereKey($reservation->branch_id)->lockForUpdate()->first(); // mutex cabang (lapis 1)

        $dates = collect(CarbonPeriod::create($reservation->check_in, $reservation->check_out->subDay()))->map->toDateString();
        $lines = $reservation->rooms()->with('room')->get();

        $free = $lines->every(fn ($line) => $line->room->isBookable()
            && ! RoomNight::where('room_id', $line->room_id)->whereIn('stay_date', $dates)->exists());
        if (! $free) {
            return false;
        }

        foreach ($lines as $line) {
            $line->nights()->createMany($dates->map(fn ($d) => ['room_id' => $line->room_id, 'stay_date' => $d])->all());
        }

        return true;
    }

    /** Menghapus baris room_nights sehingga kamar kembali tersedia (invarian Tahap 3, T11). */
    public function releaseInventory(Reservation $reservation): void
    {
        RoomNight::whereIn('reservation_room_id', $reservation->rooms()->select('id'))->delete();
    }
}
