<?php

// lang/id/enums.php

return [
    'user_role' => ['admin' => 'Admin', 'customer' => 'Pelanggan'],
    'room_status' => ['available' => 'Tersedia', 'maintenance' => 'Perawatan', 'inactive' => 'Nonaktif'],
    'payment_method' => ['gateway' => 'Payment Gateway (Simulasi)', 'bank_transfer' => 'Transfer Bank'],
    'payment_status' => [
        'pending' => 'Menunggu', 'submitted' => 'Menunggu Verifikasi', 'approved' => 'Disetujui',
        'rejected' => 'Ditolak', 'failed' => 'Gagal', 'cancelled' => 'Dibatalkan',
    ],
    'refund_status' => ['none' => 'Tidak ada', 'pending' => 'Menunggu diproses', 'processed' => 'Sudah diproses'],
    'reservation_status' => [
        'pending_payment' => 'Menunggu Pembayaran', 'awaiting_verification' => 'Menunggu Verifikasi', 'paid' => 'Lunas',
        'checked_in' => 'Check-in', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan', 'expired' => 'Kedaluwarsa',
    ],
];
