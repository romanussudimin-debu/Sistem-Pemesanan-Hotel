# Panduan Instalasi & Deployment — Nusa Stay Hotels

> 🇬🇧 English version below.

## 0. Cara tercepat: GitHub Codespaces (tanpa instalasi)

Codespaces menjalankan aplikasi di server GitHub dan menampilkannya di browser. Akun GitHub pribadi mendapat kuota gratis bulanan (±60 jam untuk mesin 2-core).

1. Buka https://github.com/romanussudimin-debu/Sistem-Pemesanan-Hotel, klik tombol hijau **Code** → tab **Codespaces** → **Create codespace on main**. Bisa juga langsung lewat tombol **Open in GitHub Codespaces** di README.
2. Tunggu ±3–5 menit. Skrip [`.devcontainer/setup.sh`](../.devcontainer/setup.sh) otomatis memasang PHP 8.3, Node.js, dan dependensi, menyiapkan `.env` (SQLite), lalu mengisi data demo. Selesai bila terminal menampilkan **"✅ Siap!"**.
3. Aplikasi otomatis berjalan di port **8000** dan terbuka di tab baru. Bila tidak terbuka, buka tab **PORTS** di bagian bawah, cari port 8000, lalu klik ikon 🌐 (*Open in Browser*).
4. Masuk dengan `admin@nusastay.test` atau `budi@contoh.test` (kata sandi `password`).

**Membuka lagi di lain waktu:** github.com → foto profil → **Your codespaces**, lalu pilih codespace ini. Server aplikasi otomatis berjalan lagi. Bila tidak, ketik `php artisan serve --host=0.0.0.0 --port=8000` di terminal.

**Hemat kuota:** codespace berhenti sendiri setelah 30 menit tidak dipakai. Hapus codespace yang sudah tidak diperlukan di halaman *Your codespaces*.

**Catatan teknis:**
- Codespaces berjalan di balik proxy HTTPS GitHub. Karena itu skrip mengisi `TRUSTED_PROXIES=*` dan `APP_URL` dengan alamat publik codespace, supaya tautan dan aset memakai https.
- Email tidak benar-benar terkirim, melainkan ditulis ke `storage/logs/laravel.log`.
- Uji konkurensi butuh MySQL, jadi tidak dijalankan di Codespaces. Uji ini tetap berjalan di GitHub Actions.

## 1. Kebutuhan

| Perangkat lunak | Versi |
|---|---|
| PHP | 8.3+ (ekstensi: `pdo_mysql`, `mbstring`, `gd`, `fileinfo`; `pcntl` opsional untuk uji konkurensi) |
| Composer | 2.x |
| Node.js & npm | 20+ |
| MySQL / MariaDB | MySQL 8.0+ atau MariaDB 10.6+ |

Di Windows, cara termudah adalah memakai [Laragon](https://laragon.org/) atau XAMPP (PHP 8.3 + MySQL).

## 2. Instalasi lokal

```bash
git clone https://github.com/romanussudimin-debu/Sistem-Pemesanan-Hotel.git
cd Sistem-Pemesanan-Hotel
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Buat database kosong bernama `nusa_stay`, lalu sesuaikan `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nusa_stay
DB_USERNAME=root
DB_PASSWORD=
```

Isi tabel dan data contoh, lalu jalankan aplikasi:

```bash
php artisan migrate --seed     # 3 cabang, 60 kamar, ±3.200 reservasi contoh (±15 detik)
php artisan storage:link       # agar foto yang diunggah admin dapat ditampilkan
php artisan serve              # buka http://localhost:8000
```

Proses latar belakang (terminal terpisah):

```bash
php artisan schedule:work      # kedaluwarsa otomatis reservasi yang tidak dibayar (setiap 5 menit)
php artisan queue:work         # pengiriman email notifikasi
```

> Untuk demo cepat tanpa worker, set `QUEUE_CONNECTION=sync` di `.env`. Email ditulis ke `storage/logs/laravel.log` (`MAIL_MAILER=log`), atau gunakan [Mailpit](https://mailpit.axllent.org/) untuk melihatnya di browser.

### Akun demo (kata sandi: `password`)

| Peran | Email | Isi akun |
|---|---|---|
| Admin | `admin@nusastay.test` | Akses ke semua cabang, dashboard, verifikasi pembayaran |
| Pelanggan | `budi@contoh.test` | 1 reservasi untuk setiap status: menunggu pembayaran, menunggu verifikasi (pernah ditolak 1×), lunas, selesai |
| Pelanggan | `siti@contoh.test` | Bukti transfer menunggu verifikasi |
| Pelanggan (EN) | `john@example.test` | Antarmuka & email berbahasa Inggris |

### Skenario demo yang disarankan

1. Masuk sebagai Budi → cari kamar → pesan → bayar dengan **MockPay** → unduh voucher.
2. Pesan lagi → pilih **Transfer Bank** → unggah gambar apa saja sebagai bukti.
3. Masuk sebagai admin → **Verifikasi Pembayaran** → centang daftar periksa → setujui/tolak.
4. Lihat **Dashboard** dan **Laporan** (tab Okupansi, Pendapatan, Reservasi, Kamar Terlaris) → ekspor CSV/PDF.
5. Ganti bahasa dengan tombol **ID | EN** di kanan atas.

## 3. Pengujian

```bash
php artisan test                                  # 76 test (SQLite in-memory, tanpa konfigurasi)
php artisan hotel:concurrency-test --workers=20   # butuh MySQL/MariaDB + ekstensi pcntl (Linux/macOS/WSL)
```

Uji konkurensi menjalankan 20 proses paralel yang memesan tipe kamar yang sama pada malam yang sama secara serentak.
Hasil yang benar: jumlah yang berhasil = jumlah kamar bebas, dan tidak ada malam ganda. GitHub Actions menjalankan keduanya pada setiap push.

## 4. Deployment sederhana (VPS Ubuntu + Nginx)

```bash
# di server
sudo apt install nginx mysql-server php8.3-fpm php8.3-{mysql,mbstring,xml,gd,curl,zip,bcmath} unzip
git clone https://github.com/romanussudimin-debu/Sistem-Pemesanan-Hotel.git /var/www/nusa-stay
cd /var/www/nusa-stay
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env && php artisan key:generate
# isi .env: APP_ENV=production, APP_DEBUG=false, APP_URL=https://domain-anda, DB_*, MAIL_*, MOCKPAY_SECRET (string acak)
php artisan migrate --force          # tambahkan --seed bila ingin data demo
php artisan storage:link
php artisan optimize                 # cache config, route, view
sudo chown -R www-data:www-data storage bootstrap/cache
```

Konfigurasi Nginx (inti):

```nginx
server {
    server_name domain-anda.com;
    root /var/www/nusa-stay/public;
    index index.php;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:/run/php/php8.3-fpm.sock; }
}
```

Scheduler dan queue di server:

```bash
# crontab -e (user www-data)
* * * * * cd /var/www/nusa-stay && php artisan schedule:run >> /dev/null 2>&1
```

Jalankan `php artisan queue:work --tries=3` sebagai layanan systemd atau Supervisor. Pasang HTTPS dengan `sudo certbot --nginx` (NFR-01).

**Alternatif tanpa VPS:** layanan seperti Railway, Render, atau Laravel Cloud dapat menjalankan proyek ini; atur variabel lingkungan yang sama, perintah build `composer install && npm ci && npm run build && php artisan migrate --force`, serta worker untuk `schedule:work` dan `queue:work`.

---

## 🇬🇧 English

**Requirements:** PHP 8.3+ (`pdo_mysql`, `mbstring`, `gd`, `fileinfo`; `pcntl` optional), Composer 2, Node.js 20+, MySQL 8.0+ or MariaDB 10.6+.

**Fastest: GitHub Codespaces.** Click *Code → Codespaces → Create codespace on main* (or the README badge) and wait about 3–5 minutes. `.devcontainer/setup.sh` installs PHP 8.3, Node.js, and dependencies, configures SQLite, and seeds demo data. The app then runs on port 8000 (open it from the **PORTS** tab).

**Local install:** clone the repo, run `composer install`, `npm install && npm run build`, `cp .env.example .env`, `php artisan key:generate`, create a `nusa_stay` database and set `DB_*` in `.env`, then `php artisan migrate --seed`, `php artisan storage:link`, and `php artisan serve`. Run `php artisan schedule:work` (auto-expiry every 5 minutes) and `php artisan queue:work` (emails) in separate terminals, or set `QUEUE_CONNECTION=sync` for a quick demo.

**Demo accounts** (password `password`): `admin@nusastay.test` (admin), `budi@contoh.test` (one booking per status), `siti@contoh.test` (receipt awaiting verification), `john@example.test` (English UI and emails).

**Tests:** `php artisan test` (76 tests, SQLite in-memory) and `php artisan hotel:concurrency-test --workers=20` (needs MySQL/MariaDB and `pcntl`). Both run in GitHub Actions on every push.

**Deployment:** on an Ubuntu VPS with Nginx and PHP-FPM, install with `composer install --no-dev --optimize-autoloader`, build assets, set production `.env` values (`APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `DB_*`, `MAIL_*`, a random `MOCKPAY_SECRET`), run `php artisan migrate --force`, `storage:link`, and `optimize`, point Nginx's root at `public/`, add the `schedule:run` cron entry, run `queue:work` under systemd or Supervisor, and enable HTTPS with Certbot. PaaS options (Railway, Render, Laravel Cloud) work with the same environment variables and build commands.
