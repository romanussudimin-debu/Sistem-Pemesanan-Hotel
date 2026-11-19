<?php

// lang/id/messages.php

return [
    // Galat aturan bisnis (App\Exceptions\BusinessRuleException)
    'room_just_sold_out' => 'Maaf, kamar :room baru saja habis dipesan tamu lain. Ketersediaan terbaru sudah ditampilkan.',
    'single_branch_only' => 'Satu reservasi hanya boleh untuk satu cabang.',
    'cannot_cancel' => 'Reservasi ini tidak dapat dibatalkan.',
    'cannot_check_in' => 'Check-in hanya untuk reservasi lunas pada tanggal menginap.',
    'cannot_check_out' => 'Check-out hanya untuk tamu yang sudah check-in.',
    'not_payable' => 'Reservasi ini tidak dapat dibayar (sudah dibayar, dibatalkan, atau melewati batas bayar).',
    'invalid_callback' => 'Callback pembayaran tidak valid.',
    'payment_already_processed' => 'Pembayaran ini sudah diproses oleh admin lain.',
    'cancelled_after_rejections' => 'Dibatalkan otomatis setelah bukti pembayaran ditolak 3 kali.',
    'cannot_delete_in_use' => ':item tidak dapat dihapus karena memiliki riwayat reservasi. Nonaktifkan saja.',
    'rate_overlap' => 'Periode tarif tumpang tindih dengan tarif ":label" yang sudah ada.',

    // Pesan sukses (flash)
    'reservation_created' => 'Reservasi :code berhasil dibuat. Selesaikan pembayaran sebelum batas waktu.',
    'proof_uploaded' => 'Bukti transfer diterima dan sedang menunggu verifikasi admin.',
    'payment_success' => 'Pembayaran berhasil. Reservasi Anda sudah lunas.',
    'payment_failed' => 'Pembayaran gagal. Silakan coba lagi atau pilih metode lain.',
    'reservation_cancelled' => 'Reservasi :code telah dibatalkan.',
    'payment_approved' => 'Pembayaran disetujui. Reservasi :code kini lunas.',
    'payment_rejected' => 'Pembayaran ditolak.',
    'checked_in' => 'Tamu reservasi :code telah check-in.',
    'checked_out' => 'Tamu reservasi :code telah check-out.',
    'saved' => 'Data berhasil disimpan.',
    'deleted' => 'Data berhasil dihapus.',
    'locale_changed' => 'Bahasa diubah.',
];
