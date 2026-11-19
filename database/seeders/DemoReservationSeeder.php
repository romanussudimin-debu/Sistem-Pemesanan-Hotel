<?php

// database/seeders/DemoReservationSeeder.php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\ReservationStatus as S;
use App\Enums\UserRole;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Histori reservasi realistis (±6 bulan ke belakang s.d. 60 hari ke depan) agar dashboard & laporan
 * langsung berisi data saat demo. Alokasi kamar memakai peta keterpakaian di memori sehingga
 * invarian UNIQUE(room_id, stay_date) pada room_nights tidak pernah dilanggar.
 *
 * Seeder ini hanya untuk data contoh; alur pemesanan sungguhan memakai ReservationService (Tahap 5B).
 */
class DemoReservationSeeder extends Seeder
{
    public const DEMO_PROOF = 'payment-proofs/demo-bukti-transfer.png';

    /** @var array<int, array<string, true>> [room_id][Y-m-d] = terpakai */
    private array $occupied = [];

    /** @var array<string, int> nomor urut kode reservasi per prefiks */
    private array $sequence = [];

    private CarbonImmutable $now;

    private User $admin;

    public function run(): void
    {
        mt_srand(2026); // data acak tetapi dapat direproduksi
        $this->now = CarbonImmutable::now();
        $this->admin = User::where('role', UserRole::Admin)->firstOrFail();
        $this->storeDemoProof();

        $types = RoomType::with(['branch', 'rates', 'rooms' => fn ($q) => $q->bookable()->orderBy('number')])->get();
        $customers = User::customers()->get();

        DB::transaction(function () use ($types, $customers) {
            $this->demoScenarios($types, $customers);
            // Akun demo dikecualikan dari histori acak supaya riwayatnya tetap ringkas saat presentasi
            $this->randomHistory($types, $customers->whereNotIn('email', ['budi@contoh.test', 'siti@contoh.test'])->values());
        });
    }

    /** Skenario tetap untuk akun demo agar setiap status bisa ditunjukkan saat presentasi (cocok dengan wireframe W-06/W-10). */
    private function demoScenarios(Collection $types, Collection $customers): void
    {
        $type = fn (string $name) => $types->firstWhere('name', $name);
        $budi = $customers->firstWhere('email', 'budi@contoh.test');
        $siti = $customers->firstWhere('email', 'siti@contoh.test');
        $today = $this->now->startOfDay();

        $this->book($budi, $type('Deluxe Garden View'), $today->addDays(14), 2, 1, S::PendingPayment);
        $this->book($budi, $type('Jungle Villa'), $today->addDays(35), 3, 1, S::Paid);
        $this->book($budi, $type('Superior Room'), $today->subDays(96), 2, 2, S::Completed);
        $this->book($budi, $type('Deluxe Pool Access'), $today->addDays(20), 2, 1, S::AwaitingVerification, rejectedBefore: 1);
        $this->book($siti, $type('Superior Room'), $today->addDays(9), 2, 1, S::AwaitingVerification);
    }

    private function randomHistory(Collection $types, Collection $customers): void
    {
        $start = $this->now->startOfDay()->subDays(180);
        $end = $this->now->startOfDay()->addDays(60);

        foreach (CarbonPeriod::create($start, $end) as $day) {
            $day = CarbonImmutable::instance($day);
            $weekend = in_array($day->dayOfWeekIso, [5, 6], true);
            // Pemesanan untuk tanggal jauh di depan makin sedikit (belum banyak yang memesan)
            $leadFactor = $day->isFuture() ? max(0.25, 1 - $this->now->diffInDays($day) / 70) : 1;

            foreach ($types->groupBy('branch_id') as $branchTypes) {
                $count = (int) round(mt_rand($weekend ? 6 : 4, $weekend ? 9 : 7) * $leadFactor);

                for ($i = 0; $i < $count; $i++) {
                    // Tipe kamar termurah lebih sering dipesan
                    $sorted = $branchTypes->sortBy('base_price')->values();
                    $roomType = $sorted[min($sorted->count() - 1, (int) floor(abs(mt_rand(0, 100) - mt_rand(0, 100)) / 100 * $sorted->count()))];
                    $nights = [1, 2, 2, 3, 3, 4, 5][mt_rand(0, 6)];
                    $qty = mt_rand(1, 10) === 1 ? 2 : 1;

                    $this->book($customers->random(), $roomType, $day, $nights, $qty, $this->pickStatus($day, $nights));
                }
            }
        }
    }

    private function pickStatus(CarbonImmutable $checkIn, int $nights): S
    {
        $today = $this->now->startOfDay();
        $checkOut = $checkIn->addDays($nights);
        $r = mt_rand(1, 100);

        if ($checkOut->lessThanOrEqualTo($today)) {
            return match (true) {
                $r <= 5 => S::Expired, $r <= 14 => S::Cancelled, default => S::Completed
            };
        }
        if ($checkIn->lessThanOrEqualTo($today)) {
            return S::CheckedIn;
        }

        return match (true) {
            $r <= 2 => S::PendingPayment,
            $r <= 4 => S::AwaitingVerification,
            $r <= 12 => S::Cancelled,
            default => S::Paid,
        };
    }

    /**
     * Membuat satu reservasi lengkap (item kamar, malam, pembayaran, log status) dengan status akhir $status.
     */
    private function book(User $user, RoomType $type, CarbonImmutable $checkIn, int $nights, int $qty, S $status, int $rejectedBefore = 0): ?Reservation
    {
        $checkOut = $checkIn->addDays($nights);
        $dates = collect(CarbonPeriod::create($checkIn, $checkOut->subDay()))->map(fn ($d) => $d->format('Y-m-d'));

        // Cari kamar bebas pada semua malam (BR-05); lewati bila tidak cukup
        $rooms = $type->rooms->filter(fn ($room) => $dates->every(fn ($d) => ! isset($this->occupied[$room->id][$d])))->take($qty);
        if ($rooms->count() < $qty) {
            return null;
        }

        $guests = min($type->capacity, mt_rand(1, 2)) * $qty;
        $timeline = $this->timeline($checkIn, $checkOut, $status);

        // Harga per malam dengan tarif musiman (BR-07) + pajak (BR-08)
        $breakdown = $dates->map(fn ($d) => $type->rateFor(CarbonImmutable::parse($d)))->values()->all();
        $lineTotal = array_sum(array_column($breakdown, 'price'));
        $subtotal = $lineTotal * $qty;
        $taxRate = config('hotel.tax_rate');
        $tax = round($subtotal * $taxRate / 100);

        $reservation = new Reservation([
            'code' => $this->nextCode($type->branch->code, $timeline['created']),
            'user_id' => $user->id,
            'branch_id' => $type->branch_id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'nights' => $nights,
            'total_guests' => $guests,
            'guest_name' => $user->name,
            'guest_phone' => $user->phone ?? '-',
            'special_request' => mt_rand(1, 6) === 1 ? 'Mohon kamar di lantai atas bila memungkinkan.' : null,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $tax,
            'total_amount' => $subtotal + $tax,
            'payment_deadline' => $timeline['deadline'],
        ]);
        $reservation->forceFill([
            'status' => $status,
            'rejection_count' => $rejectedBefore,
            'paid_at' => $timeline['paid'],
            'checked_in_at' => $timeline['checked_in'],
            'completed_at' => $timeline['completed'],
            'cancelled_at' => $timeline['cancelled'],
            'created_at' => $timeline['created'],
            'updated_at' => $timeline['updated'],
        ]);

        if ($status === S::Cancelled) {
            $refund = $timeline['paid'] ? $this->refundFor($reservation->total_amount, $timeline['cancelled'], $checkIn) : 0;
            $reservation->forceFill([
                'cancelled_by' => $user->id,
                'cancel_reason' => 'Perubahan rencana perjalanan.',
                'refund_amount' => $refund,
                'refund_status' => $refund > 0 ? RefundStatus::Processed : RefundStatus::None,
            ]);
        }
        $reservation->save();

        $holdsInventory = in_array($status, S::holdingInventory(), true);
        foreach ($rooms as $room) {
            $line = $reservation->rooms()->create([
                'room_id' => $room->id,
                'room_type_id' => $type->id,
                'guests' => intdiv($guests, $qty),
                'rate_breakdown' => $breakdown,
                'line_total' => $lineTotal,
            ]);

            // Invarian room_nights: hanya untuk status yang menahan inventaris
            if ($holdsInventory) {
                $line->nights()->createMany($dates->map(fn ($d) => ['room_id' => $room->id, 'stay_date' => $d])->all());
                foreach ($dates as $d) {
                    $this->occupied[$room->id][$d] = true;
                }
            }
        }

        $this->createPayments($reservation, $status, $timeline, $rejectedBefore);
        $this->createLogs($reservation, $status, $timeline, $user);

        return $reservation;
    }

    /** Menyusun waktu-waktu kejadian yang konsisten satu sama lain dan tidak melewati "sekarang". */
    private function timeline(CarbonImmutable $checkIn, CarbonImmutable $checkOut, S $status): array
    {
        $now = $this->now;
        $checkInAt = $checkIn->setTimeFromTimeString(config('hotel.check_in_time'));

        if (in_array($status, [S::PendingPayment, S::AwaitingVerification], true)) {
            // Reservasi baru saja dibuat agar batas bayarnya masih berlaku
            $created = $now->subMinutes(mt_rand(20, 600));
        } else {
            $created = $checkInAt->subDays(mt_rand(1, 45))->setTime(mt_rand(7, 22), mt_rand(0, 59));
            if ($created->greaterThan($now)) {
                // Pemesanan jauh hari: sebar waktu pembuatan secara merata antara (check-in − 60 hari) dan sekarang
                $window = max(60, (int) $checkInAt->subDays(60)->diffInMinutes($now));
                $created = $now->subMinutes(mt_rand(60, $window));
            }
        }

        $deadline = Reservation::computeDeadline($created, $checkIn);
        $paid = in_array($status, [S::Paid, S::CheckedIn, S::Completed], true) || ($status === S::Cancelled && mt_rand(0, 1))
            ? $created->addMinutes(mt_rand(5, 600))->min($now)
            : null;

        $cancelled = null;
        if ($status === S::Cancelled) {
            $from = $paid ?? $created;
            $latest = $checkInAt->min($now);
            $cancelled = $latest->greaterThan($from)
                ? $from->addSeconds(mt_rand(60, max(61, (int) $from->diffInSeconds($latest))))
                : $from->addMinute();
        }

        $checkedIn = in_array($status, [S::CheckedIn, S::Completed], true) ? $checkInAt->addMinutes(mt_rand(0, 360)) : null;
        $completed = $status === S::Completed ? $checkOut->setTime(mt_rand(9, 11), mt_rand(0, 59)) : null;

        return [
            'created' => $created,
            'deadline' => $deadline,
            'paid' => $paid,
            'cancelled' => $cancelled,
            'checked_in' => $checkedIn,
            'completed' => $completed,
            'updated' => collect([$created, $paid, $cancelled, $checkedIn, $completed, $status === S::Expired ? $deadline : null])->filter()->max(),
        ];
    }

    /** BR-14: persentase refund berdasarkan jarak hari pembatalan ke check-in. */
    private function refundFor(float|string $total, CarbonImmutable $cancelledAt, CarbonImmutable $checkIn): float
    {
        $days = (int) $cancelledAt->startOfDay()->diffInDays($checkIn, false);
        foreach (config('hotel.cancellation_policy') as $minDays => $percent) {
            if ($days >= $minDays) {
                return round((float) $total * $percent / 100);
            }
        }

        return 0;
    }

    private function createPayments(Reservation $r, S $status, array $t, int $rejectedBefore): void
    {
        $amount = $r->total_amount;

        // Bukti yang pernah ditolak (skenario demo BR-11)
        for ($i = 0; $i < $rejectedBefore; $i++) {
            $p = $this->payment($r, PaymentMethod::BankTransfer, PaymentStatus::Rejected, $amount - 4000, $t['created']->addMinutes(10));
            $p->forceFill(['verified_by' => $this->admin->id, 'verified_at' => $t['created']->addMinutes(15), 'rejection_reason' => 'Nominal kurang Rp4.000.'])->save();
        }

        if ($t['paid']) {
            $method = mt_rand(1, 10) <= 6 ? PaymentMethod::Gateway : PaymentMethod::BankTransfer;
            $p = $this->payment($r, $method, PaymentStatus::Approved, $amount, $t['paid']);
            $p->forceFill([
                'verified_at' => $t['paid'],
                'verified_by' => $method === PaymentMethod::BankTransfer ? $this->admin->id : null,
            ])->save();
        } elseif ($status === S::AwaitingVerification) {
            $this->payment($r, PaymentMethod::BankTransfer, PaymentStatus::Submitted, $amount, $t['created']->addMinutes(20)->min($this->now));
        } elseif ($status === S::Expired && mt_rand(1, 3) === 1) {
            $this->payment($r, PaymentMethod::Gateway, PaymentStatus::Failed, $amount, $t['created']->addMinutes(5));
        }
    }

    private function payment(Reservation $r, PaymentMethod $method, PaymentStatus $status, float|string $amount, CarbonImmutable $at): Payment
    {
        $transfer = $method === PaymentMethod::BankTransfer;

        $payment = new Payment([
            'reservation_id' => $r->id,
            'method' => $method,
            'status' => $status,
            'amount' => $amount,
            'gateway_ref' => $transfer ? null : 'MP-'.strtoupper(bin2hex(random_bytes(4))),
            'proof_path' => $transfer ? self::DEMO_PROOF : null,
            'sender_name' => $transfer ? $r->guest_name : null,
            'sender_bank' => $transfer ? 'Bank XYZ (contoh)' : null,
            'transfer_date' => $transfer ? $at->toDateString() : null,
        ]);
        $payment->forceFill(['created_at' => $at, 'updated_at' => $at])->save();

        return $payment;
    }

    /** Jejak audit yang konsisten dengan state machine (NFR-14). */
    private function createLogs(Reservation $r, S $status, array $t, User $user): void
    {
        $logs = [[null, S::PendingPayment, $t['created'], $user->id, 'created']];

        $viaTransfer = $r->payments()->where('method', PaymentMethod::BankTransfer)->exists();
        if ($t['paid']) {
            if ($viaTransfer) {
                $logs[] = [S::PendingPayment, S::AwaitingVerification, $t['paid']->subMinutes(3), $user->id, 'proof uploaded'];
                $logs[] = [S::AwaitingVerification, S::Paid, $t['paid'], $this->admin->id, 'payment approved'];
            } else {
                $logs[] = [S::PendingPayment, S::Paid, $t['paid'], null, 'gateway callback'];
            }
        }
        if ($status === S::AwaitingVerification) {
            $logs[] = [S::PendingPayment, S::AwaitingVerification, $t['created']->addMinutes(20)->min($this->now), $user->id, 'proof uploaded'];
        }
        if ($t['checked_in']) {
            $logs[] = [S::Paid, S::CheckedIn, $t['checked_in'], $this->admin->id, null];
        }
        if ($t['completed']) {
            $logs[] = [S::CheckedIn, S::Completed, $t['completed'], $this->admin->id, null];
        }
        if ($status === S::Cancelled) {
            $logs[] = [$t['paid'] ? S::Paid : S::PendingPayment, S::Cancelled, $t['cancelled'], $user->id, 'Perubahan rencana perjalanan.'];
        }
        if ($status === S::Expired) {
            $logs[] = [S::PendingPayment, S::Expired, $t['deadline'], null, 'auto-expire'];
        }

        DB::table('reservation_status_logs')->insert(array_map(fn ($l) => [
            'reservation_id' => $r->id,
            'from_status' => $l[0]?->value,
            'to_status' => $l[1]->value,
            'changed_by' => $l[3],
            'note' => $l[4],
            'created_at' => $l[2],
        ], $logs));
    }

    private function nextCode(string $branchCode, CarbonImmutable $createdAt): string
    {
        $prefix = sprintf('NSH-%s-%s-', $branchCode, $createdAt->format('Ymd'));
        $this->sequence[$prefix] = ($this->sequence[$prefix] ?? 0) + 1;

        return $prefix.str_pad((string) $this->sequence[$prefix], 4, '0', STR_PAD_LEFT);
    }

    /** Menyalin gambar bukti transfer tiruan ke storage privat agar halaman verifikasi admin bisa menampilkannya. */
    private function storeDemoProof(): void
    {
        if (! Storage::disk('local')->exists(self::DEMO_PROOF)) {
            Storage::disk('local')->put(self::DEMO_PROOF, file_get_contents(__DIR__.'/assets/demo-bukti-transfer.png'));
        }
    }
}
