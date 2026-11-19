<?php

// lang/id/validation.php — pesan validasi Bahasa Indonesia

return [
    'accepted' => ':attribute wajib disetujui.',
    'after' => ':attribute harus tanggal setelah :date.',
    'after_or_equal' => ':attribute harus tanggal setelah atau sama dengan :date.',
    'alpha' => ':attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'array' => ':attribute harus berupa daftar.',
    'before' => ':attribute harus tanggal sebelum :date.',
    'before_or_equal' => ':attribute harus tanggal sebelum atau sama dengan :date.',
    'boolean' => ':attribute harus bernilai benar atau salah.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi salah.',
    'date' => ':attribute bukan tanggal yang valid.',
    'date_format' => ':attribute tidak sesuai format :format.',
    'email' => ':attribute harus alamat email yang valid.',
    'enum' => ':attribute yang dipilih tidak valid.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'file' => ':attribute harus berupa file.',
    'image' => ':attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'integer' => ':attribute harus bilangan bulat.',
    'lowercase' => ':attribute harus huruf kecil.',
    'max' => [
        'array' => ':attribute maksimal berisi :max item.',
        'file' => ':attribute maksimal :max kilobyte.',
        'numeric' => ':attribute maksimal :max.',
        'string' => ':attribute maksimal :max karakter.',
    ],
    'mimes' => ':attribute harus berupa file bertipe: :values.',
    'min' => [
        'array' => ':attribute minimal berisi :min item.',
        'file' => ':attribute minimal :min kilobyte.',
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
    ],
    'numeric' => ':attribute harus berupa angka.',
    'password' => [
        'letters' => ':attribute harus mengandung minimal satu huruf.',
        'mixed' => ':attribute harus mengandung huruf besar dan huruf kecil.',
        'numbers' => ':attribute harus mengandung minimal satu angka.',
        'symbols' => ':attribute harus mengandung minimal satu simbol.',
        'uncompromised' => ':attribute ini pernah bocor dalam kebocoran data. Pilih :attribute lain.',
    ],
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':attribute wajib diisi.',
    'required_if' => ':attribute wajib diisi bila :other adalah :value.',
    'size' => [
        'array' => ':attribute harus berisi :size item.',
        'file' => ':attribute harus berukuran :size kilobyte.',
        'numeric' => ':attribute harus bernilai :size.',
        'string' => ':attribute harus :size karakter.',
    ],
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah digunakan.',
    'uploaded' => ':attribute gagal diunggah.',

    // Pesan khusus aturan bisnis
    'custom' => [
        'check_out' => ['max_nights' => 'Lama menginap maksimal :max malam.'],              // BR-02
        'guests' => ['capacity' => 'Jumlah tamu per kamar maksimal :max orang.'],         // BR-04
        'number' => ['unique_branch' => 'Nomor kamar :number sudah dipakai di cabang ini.'],
    ],

    'attributes' => [
        'name' => 'nama', 'email' => 'email', 'password' => 'kata sandi', 'phone' => 'telepon',
        'branch' => 'cabang', 'branch_id' => 'cabang', 'check_in' => 'tanggal check-in', 'check_out' => 'tanggal check-out',
        'guests' => 'jumlah tamu', 'rooms' => 'jumlah kamar', 'room_type_id' => 'tipe kamar',
        'guest_name' => 'nama tamu', 'guest_phone' => 'telepon tamu', 'special_request' => 'permintaan khusus',
        'agree' => 'persetujuan kebijakan', 'proof' => 'bukti transfer', 'sender_name' => 'nama pengirim',
        'sender_bank' => 'bank pengirim', 'transfer_date' => 'tanggal transfer', 'reason' => 'alasan',
        'code' => 'kode', 'city' => 'kota', 'address' => 'alamat', 'bank_name' => 'bank',
        'bank_account_no' => 'nomor rekening', 'bank_account_name' => 'nama pemilik rekening',
        'capacity' => 'kapasitas', 'base_price' => 'tarif dasar', 'number' => 'nomor kamar', 'floor' => 'lantai',
        'label' => 'label', 'start_date' => 'tanggal mulai', 'end_date' => 'tanggal akhir', 'price' => 'tarif',
        'name_id' => 'nama (Indonesia)', 'name_en' => 'nama (Inggris)', 'from' => 'tanggal awal', 'to' => 'tanggal akhir',
    ],
];
