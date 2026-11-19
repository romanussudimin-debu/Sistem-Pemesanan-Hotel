<?php

// app/Notifications/ReservationNotification.php

namespace App\Notifications;

use App\Enums\PaymentStatus;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FR-17: satu kelas notifikasi untuk semua peristiwa reservasi.
 * Event: created | paid | proof_submitted (ke admin) | proof_rejected | cancelled | expired.
 * Bahasa email mengikuti users.locale (User::preferredLocale()).
 */
class ReservationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Reservation $reservation,
        public string $event,
        public ?string $note = null,
    ) {
        $this->afterCommit(); // jangan kirim email bila transaksi dibatalkan
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->reservation->loadMissing('branch');
        $params = [
            'code' => $r->code,
            'branch' => $r->branch->name,
            'check_in' => $r->check_in->translatedFormat('d M Y'),
            'check_out' => $r->check_out->translatedFormat('d M Y'),
            'total' => 'Rp'.number_format((float) $r->total_amount, 0, ',', '.'),
            'deadline' => $r->payment_deadline->translatedFormat('d M Y, H:i').' WITA',
            'refund' => 'Rp'.number_format((float) $r->refund_amount, 0, ',', '.'),
        ];

        $mail = (new MailMessage)
            ->subject(__("notifications.{$this->event}.subject", $params))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->name]))
            ->line(__("notifications.{$this->event}.line", $params));

        if ($this->note) {
            $mail->line(__('notifications.reason', ['reason' => $this->note]));
        }

        if ($this->event === 'proof_submitted') {
            $payment = $r->payments()->where('status', PaymentStatus::Submitted)->first();

            return $mail->action(__('notifications.action_verify'), $payment ? route('admin.payments.show', $payment) : route('admin.payments.index'));
        }

        return $mail->action(__('notifications.action_view'), route('reservations.show', $r));
    }
}
