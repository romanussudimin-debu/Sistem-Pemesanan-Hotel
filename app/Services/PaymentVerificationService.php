<?php

// app/Services/PaymentVerificationService.php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationNotification;
use Illuminate\Support\Facades\DB;

/**
 * UC-15: verifikasi bukti transfer oleh admin.
 */
class PaymentVerificationService
{
    public function __construct(private readonly ReservationService $reservations) {}

    public function approve(Payment $payment, User $admin): Reservation
    {
        $reservation = DB::transaction(function () use ($payment, $admin) {
            [$reservation, $payment] = $this->lock($payment);

            $payment->forceFill([
                'status' => PaymentStatus::Approved,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ])->save();

            return $reservation->transitionTo(ReservationStatus::Paid, $admin, 'payment approved');
        });

        $reservation->user->notify(new ReservationNotification($reservation, 'paid'));

        return $reservation;
    }

    /**
     * Penolakan ke-1 dan ke-2: reservasi kembali ke Menunggu Pembayaran; batas bayar diperpanjang bila sudah lewat (BR-20).
     * Penolakan ke-3: reservasi dibatalkan (BR-11).
     */
    public function reject(Payment $payment, User $admin, string $reason): Reservation
    {
        $event = 'proof_rejected';

        $reservation = DB::transaction(function () use ($payment, $admin, $reason, &$event) {
            [$reservation, $payment] = $this->lock($payment);

            $payment->forceFill([
                'status' => PaymentStatus::Rejected,
                'verified_by' => $admin->id,
                'verified_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $reservation->rejection_count++;

            if ($reservation->rejection_count > config('hotel.payment.max_rejections')) {
                $reservation->save();
                $event = 'cancelled';

                return $this->reservations->cancel($reservation, $admin, __('messages.cancelled_after_rejections').' '.$reason);
            }

            if ($reservation->payment_deadline->isPast()) {
                $reservation->payment_deadline = now()->addHours(config('hotel.payment.rejection_grace_hours'));
            }

            return $reservation->transitionTo(ReservationStatus::PendingPayment, $admin, $reason);
        });

        // Notifikasi pembatalan sudah dikirim oleh ReservationService::cancel()
        if ($event === 'proof_rejected') {
            $reservation->user->notify(new ReservationNotification($reservation, 'proof_rejected', $reason));
        }

        return $reservation;
    }

    /** Mengunci reservasi lalu pembayaran, dan memastikan pembayaran belum diproses admin lain (UC-15 A3). */
    private function lock(Payment $payment): array
    {
        $reservation = Reservation::whereKey($payment->reservation_id)->lockForUpdate()->firstOrFail();
        $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

        if ($payment->status !== PaymentStatus::Submitted) {
            throw new BusinessRuleException('messages.payment_already_processed');
        }

        return [$reservation, $payment];
    }
}
