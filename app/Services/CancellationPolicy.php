<?php

// app/Services/CancellationPolicy.php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Carbon\CarbonInterface;

class CancellationPolicy
{
    /** BR-14: persentase refund untuk reservasi lunas berdasarkan sisa hari menuju check-in. */
    public function refundPercentage(Reservation $reservation, ?CarbonInterface $at = null): int
    {
        if ($reservation->status !== ReservationStatus::Paid) {
            return 0;
        }

        $days = $this->daysBeforeCheckIn($reservation, $at);
        foreach (config('hotel.cancellation_policy') as $minDays => $percent) {
            if ($days >= $minDays) {
                return (int) $percent;
            }
        }

        return 0;
    }

    /**
     * Nominal refund:
     * - Lunas: total × persentase BR-14.
     * - Menunggu Verifikasi: dana yang sudah ditransfer dikembalikan penuh (BR-13, UC-13 A4).
     * - Menunggu Pembayaran: 0 (belum ada dana masuk).
     */
    public function refundAmount(Reservation $reservation, ?CarbonInterface $at = null): float
    {
        return match ($reservation->status) {
            ReservationStatus::Paid => round((float) $reservation->total_amount * $this->refundPercentage($reservation, $at) / 100),
            ReservationStatus::AwaitingVerification => (float) $reservation->payments()->where('status', PaymentStatus::Submitted)->sum('amount'),
            default => 0.0,
        };
    }

    public function daysBeforeCheckIn(Reservation $reservation, ?CarbonInterface $at = null): int
    {
        return (int) ($at ?? now())->copy()->startOfDay()->diffInDays($reservation->check_in, false);
    }

    /** Tanggal terakhir pembatalan gratis (refund 100%), untuk ditampilkan di halaman detail kamar. */
    public static function freeCancellationUntil(CarbonInterface $checkIn): CarbonInterface
    {
        return $checkIn->copy()->subDays(array_key_first(config('hotel.cancellation_policy')));
    }
}
