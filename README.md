# Sistem-Pemesanan-Hotel
Projek pemesanan hotel

**Nusa Stay Hotels** adalah sistem pemesanan hotel multi-cabang (studi kasus fiktif: Jimbaran, Ubud, Seminyak), dibangun dengan Laravel 13, MySQL, Blade + Tailwind CSS, dan Laravel Breeze.

> 🚧 Sedang dikembangkan. README lengkap (fitur, tangkapan layar, akun demo, panduan instalasi) akan ditambahkan di tahap dokumentasi.

## Menjalankan secara lokal (sementara)

```bash
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
# atur DB_* di .env (MySQL 8), lalu:
php artisan migrate --seed
php artisan serve
```

Akun demo (kata sandi `password`): `admin@nusastay.test` (admin) · `budi@contoh.test` (pelanggan).

Proses latar belakang (jalankan di terminal terpisah):

```bash
php artisan schedule:work   # kedaluwarsa otomatis reservasi yang tidak dibayar (setiap 5 menit)
php artisan queue:work      # pengiriman email notifikasi
```

## Pengujian

```bash
php artisan test                                  # 68 test (SQLite in-memory)
php artisan hotel:concurrency-test --workers=20   # uji konkurensi anti-overbooking (butuh MySQL/MariaDB + pcntl)
```
