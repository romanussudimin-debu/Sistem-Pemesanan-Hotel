<?php

// app/Services/PaymentService.php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(private readonly ReservationService $reservations) {}

    /** UC-10 langkah 4: membuat percobaan bayar gateway dan referensi unik untuk halaman MockPay. */
    public function startGateway(Reservation $reservation): Payment
    {
        $this->ensurePayable($reservation);

        return $reservation->payments()->create([
            'method' => PaymentMethod::Gateway,
            'status' => PaymentStatus::Pending,
            'amount' => $reservation->total_amount,
            'gateway_ref' => 'MP-'.Str::upper(Str::random(10)),
        ]);
    }

    /** Tanda tangan HMAC-SHA256 yang meniru cara gateway sungguhan mengamankan callback (asumsi Tahap 2 no. 2). */
    public static function signature(string $ref, string $amount, string $result): string
    {
        return hash_hmac('sha256', "{$ref}|{$amount}|{$result}", (string) config('hotel.payment.mockpay_secret'));
    }

    /**
     * UC-10 langkah 6–7: memproses callback gateway. Idempoten: callback ganda untuk pembayaran yang
     * sudah diproses tidak mengubah apa pun.
     *
     * @param  array{gateway_ref: string, amount: string, result: string, signature: string}  $payload
     */
    public function handleCallback(array $payload): Payment
    {
        $payment = Payment::where('gateway_ref', $payload['gateway_ref'] ?? '')->first();
        $amount = number_format((float) ($payload['amount'] ?? 0), 2, '.', '');

        if (! $payment
            || ! hash_equals(self::signature($payment->gateway_ref, $amount, $payload['result'] ?? ''), (string) ($payload['signature'] ?? ''))
            || (int) round((float) $amount * 100) !== (int) round((float) $payment->amount * 100)) { // bandingkan dalam sen
            throw new BusinessRuleException('messages.invalid_callback'); // UC-09 E1
        }

        $notify = null;
        $payment = DB::transaction(function () use ($payment, $payload, &$notify) {
            // Urutan kunci konsisten di seluruh aplikasi: reservasi dulu, lalu pembayaran
            $reservation = Reservation::whereKey($payment->reservation_id)->lockForUpdate()->firstOrFail();
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status !== PaymentStatus::Pending) {
                return $payment; // sudah diproses (idempoten)
            }

            if ($payload['result'] !== 'success') {
                $payment->update(['status' => PaymentStatus::Failed]);

                return $payment;
            }

            $payment->forceFill(['status' => PaymentStatus::Approved, 'verified_at' => now()])->save();

            if ($reservation->status === ReservationStatus::PendingPayment) {
                $reservation->transitionTo(ReservationStatus::Paid, null, 'gateway callback');
                $notify = [$reservation, 'paid'];
            } elseif ($reservation->status === ReservationStatus::Expired && $this->reservations->tryReallocate($reservation)) {
                $reservation->transitionTo(ReservationStatus::Paid, null, 'gateway callback setelah kedaluwarsa, kamar dialokasikan ulang');
                $notify = [$reservation, 'paid'];
            } else {
                // Dana diterima tetapi reservasi tidak bisa diaktifkan: tandai refund penuh untuk admin
                $reservation->forceFill(['refund_amount' => $payment->amount, 'refund_status' => RefundStatus::Pending])->save();
            }

            return $payment;
        });

        if ($notify) {
            $notify[0]->user->notify(new ReservationNotification(...$notify));
        }

        return $payment;
    }

    /** UC-11: unggah bukti transfer; reservasi menjadi Menunggu Verifikasi dan admin diberi notifikasi. */
    public function submitProof(Reservation $reservation, UploadedFile $file, array $data, User $user): Payment
    {
        $this->ensurePayable($reservation);

        // Storage privat (NFR-04); nama file acak sehingga tidak bisa ditebak
        $path = $file->store('payment-proofs', 'local');

        $payment = DB::transaction(function () use ($reservation, $path, $data, $user) {
            $locked = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            $this->ensurePayable($locked); // cek ulang setelah dikunci

            $payment = $locked->payments()->create([
                'method' => PaymentMethod::BankTransfer,
                'status' => PaymentStatus::Submitted,
                'amount' => $locked->total_amount,
                'proof_path' => $path,
                'sender_name' => $data['sender_name'],
                'sender_bank' => $data['sender_bank'],
                'transfer_date' => $data['transfer_date'],
            ]);
            $locked->transitionTo(ReservationStatus::AwaitingVerification, $user, 'proof uploaded');

            return $payment;
        });

        Notification::send(User::where('role', UserRole::Admin)->get(), new ReservationNotification($reservation->refresh(), 'proof_submitted'));

        return $payment;
    }

    /** Prakondisi UC-09: status Menunggu Pembayaran dan batas bayar belum lewat. */
    public function ensurePayable(Reservation $reservation): void
    {
        if ($reservation->status !== ReservationStatus::PendingPayment || $reservation->payment_deadline->isPast()) {
            throw new BusinessRuleException('messages.not_payable');
        }
    }
}
