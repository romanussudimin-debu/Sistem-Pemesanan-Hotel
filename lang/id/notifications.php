<?php

// lang/id/notifications.php

return [
    'greeting' => 'Halo, :name!',
    'reason' => 'Alasan: :reason',
    'action_view' => 'Lihat Reservasi',
    'action_verify' => 'Verifikasi Sekarang',
    'created' => [
        'subject' => 'Reservasi :code menunggu pembayaran',
        'line' => 'Reservasi Anda di :branch (:check_in – :check_out) berhasil dibuat. Total :total. Mohon selesaikan pembayaran sebelum :deadline.',
    ],
    'paid' => [
        'subject' => 'Reservasi :code lunas',
        'line' => 'Pembayaran untuk reservasi di :branch (:check_in – :check_out) telah kami terima. Voucher dapat diunduh dari halaman reservasi.',
    ],
    'proof_submitted' => [
        'subject' => '[Admin] Bukti transfer baru untuk :code',
        'line' => 'Pelanggan mengunggah bukti transfer :total untuk reservasi :code di :branch. Mohon verifikasi maksimal 1×24 jam.',
    ],
    'proof_rejected' => [
        'subject' => 'Bukti pembayaran :code ditolak',
        'line' => 'Bukti transfer untuk reservasi :code belum dapat kami terima. Silakan unggah ulang sebelum :deadline.',
    ],
    'cancelled' => [
        'subject' => 'Reservasi :code dibatalkan',
        'line' => 'Reservasi Anda di :branch (:check_in – :check_out) telah dibatalkan. Nominal refund: :refund.',
    ],
    'expired' => [
        'subject' => 'Reservasi :code kedaluwarsa',
        'line' => 'Reservasi Anda di :branch kedaluwarsa karena pembayaran tidak diterima sebelum batas waktu. Kamar telah dilepas.',
    ],
];
