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
