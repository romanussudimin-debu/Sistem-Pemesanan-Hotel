<?php

// config/hotel.php
// Seluruh nilai aturan bisnis dikumpulkan di sini agar mudah diubah tanpa menyentuh kode (asumsi Tahap 1 no. 5).

return [

    // BR-08: pajak & layanan (PB1 10% + service 11%)
    'tax_rate' => (float) env('HOTEL_TAX_RATE', 21),

    'booking' => [
        'max_days_ahead' => 365,   // BR-01
        'min_nights' => 1,         // BR-02
        'max_nights' => 14,        // BR-02
        'max_rooms' => 5,          // BR-03
    ],

    'payment' => [
        'deadline_hours' => (int) env('HOTEL_PAYMENT_DEADLINE_HOURS', 24),    // BR-09
        'short_deadline_hours' => (int) env('HOTEL_SHORT_DEADLINE_HOURS', 2), // BR-09: check-in < 24 jam lagi
        'max_rejections' => 2,          // BR-11: penolakan ke-3 = dibatalkan
        'rejection_grace_hours' => 2,   // BR-20
        'proof_max_kb' => 2048,         // NFR-04
        'proof_mimes' => ['jpg', 'jpeg', 'png', 'pdf'],
        'mockpay_secret' => env('MOCKPAY_SECRET', 'nusa-stay-demo-secret'),
    ],

    // BR-14: [minimal hari sebelum check-in => persentase refund], dievaluasi dari atas ke bawah
    'cancellation_policy' => [
        7 => 100,
        3 => 50,
        0 => 0,
    ],

    // BR-16
    'check_in_time' => '14:00',
    'check_out_time' => '12:00',

    'locales' => ['id', 'en'],
];
