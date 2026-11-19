# 01 · Spesifikasi Kebutuhan Perangkat Lunak (SRS Ringkas)

**Sistem Pemesanan Hotel Multi-Cabang — "Nusa Stay Hotels" (fiktif)**

> 🇮🇩 Bahasa Indonesia · [🇬🇧 English](#-english-version) · Dokumen berikutnya: [02 · Pemodelan Proses & Sistem](02-pemodelan.md)
>
> Status: **as-built**. Dokumen sudah diselaraskan dengan aplikasi akhir. Perubahan sejak versi awal tercatat di [Riwayat Perubahan](#riwayat-perubahan).

---

## 1.1 Pendahuluan

Dokumen ini mengikuti struktur ringkas spesifikasi kebutuhan menurut ISO/IEC/IEEE 29148 (ISO/IEC/IEEE, 2018). Atribut kualitas non-fungsional mengacu pada model kualitas produk ISO/IEC 25010 (ISO/IEC, 2011).

### 1.1.1 Latar Belakang Masalah

Nusa Stay Hotels adalah jaringan hotel fiktif dengan tiga cabang di Bali: **Jimbaran**, **Ubud**, dan **Seminyak**. Tiap cabang mengelola tipe kamar, jumlah kamar, dan tarifnya sendiri. Pemesanan diterima lewat telepon, WhatsApp, dan email, lalu dicatat resepsionis di buku besar atau spreadsheet yang berbeda di tiap cabang.

| No | Masalah | Dampak |
|----|---------|--------|
| P1 | Data ketersediaan kamar tidak terpusat dan tidak real-time | **Pemesanan ganda (overbooking)**, tamu kecewa, reputasi turun |
| P2 | Konfirmasi pembayaran transfer dicek manual lewat chat | Lambat, bukti transfer tercecer, sulit dilacak |
| P3 | Reservasi yang tidak dibayar tidak dilepas tepat waktu | Kamar "terkunci" sia-sia sehingga okupansi turun |
| P4 | Pelanggan tidak bisa mengecek ketersediaan sendiri | Bergantung pada jam kerja staf, peluang penjualan hilang |
| P5 | Laporan pendapatan dan okupansi direkap manual per cabang | Laporan terlambat, rawan salah hitung, sulit membandingkan cabang |

### 1.1.2 Tujuan Sistem

1. Menyediakan kanal pemesanan online 24/7 dengan data ketersediaan kamar yang terpusat dan real-time untuk semua cabang.
2. **Mencegah overbooking** melalui validasi ketersediaan dan penguncian transaksi di basis data.
3. Mendigitalkan alur pembayaran (simulasi payment gateway dan unggah bukti transfer) beserta status yang jelas.
4. Memusatkan pengelolaan data master (cabang, tipe kamar, kamar, tarif, fasilitas) di satu panel admin.
5. Menyajikan dashboard dan laporan (okupansi, pendapatan, jumlah reservasi, kamar terlaris) per cabang maupun gabungan.
6. Menyediakan antarmuka dwibahasa (Indonesia/Inggris).

### 1.1.3 Ruang Lingkup

| Termasuk dalam lingkup | Di luar lingkup |
|---|---|
| Registrasi, login, reset kata sandi, verifikasi email | Integrasi payment gateway sungguhan (hanya simulasi) |
| Pencarian kamar per cabang, tanggal, jumlah tamu | Integrasi OTA / channel manager |
| Pemesanan satu atau beberapa kamar dalam satu cabang | Sistem front office penuh (housekeeping, POS restoran) |
| Pembayaran: simulasi gateway dan unggah bukti transfer | Pengembalian dana (*refund*) otomatis ke rekening |
| Verifikasi pembayaran oleh admin | Program loyalitas, kupon, dan promo |
| Pembatalan sesuai kebijakan; kedaluwarsa otomatis | Aplikasi mobile native |
| CRUD data master oleh admin | Multi-mata uang (hanya IDR) |
| Dashboard & laporan dengan grafik, ekspor CSV/PDF | Peran staf bertingkat (hanya 2 aktor) |
| Antarmuka & email dwibahasa ID/EN | Fitur pindah kamar langsung (*room move*) |

## 1.2 Identifikasi Aktor

| Aktor | Jenis | Deskripsi | Tujuan Utama |
|---|---|---|---|
| **Pelanggan** | Manusia, primer | Calon tamu atau tamu. Pengunjung yang belum login hanya bisa mencari dan melihat kamar | Menemukan kamar tersedia, memesan, membayar, dan mengelola pesanannya |
| **Admin** | Manusia, primer | Staf kantor pusat dengan akses ke semua cabang | Mengelola data master, reservasi, verifikasi pembayaran, laporan |
| **Scheduler** | Sistem, pendukung | Proses terjadwal (Laravel Scheduler) | Mengedaluwarsakan reservasi yang tidak dibayar |
| **Payment Gateway (simulasi)** | Sistem eksternal, pendukung | Modul tiruan "MockPay" | Mengirim callback status pembayaran |

## 1.3 Kebutuhan Fungsional

**A. Autentikasi & Akun**

| Kode | Kebutuhan | Aktor | Prioritas |
|---|---|---|---|
| FR-01 | Registrasi pelanggan (nama, email, telepon, kata sandi) dengan verifikasi email | Pelanggan | Tinggi |
| FR-02 | Login, logout, dan reset kata sandi melalui email | Pelanggan, Admin | Tinggi |
| FR-03 | Pembatasan akses halaman berdasarkan peran (admin/pelanggan) | Sistem | Tinggi |
| FR-04 | Melihat dan memperbarui profil | Pelanggan | Sedang |
| FR-05 | Mengganti bahasa antarmuka (ID/EN); pilihan tersimpan di sesi/profil | Pelanggan, Admin | Tinggi |

**B. Pencarian & Pemesanan**

| Kode | Kebutuhan | Aktor | Prioritas |
|---|---|---|---|
| FR-06 | Menampilkan kamar tersedia berdasarkan cabang, tanggal, dan jumlah tamu | Pelanggan | Tinggi |
| FR-07 | Menampilkan detail tipe kamar: foto, deskripsi, kapasitas, fasilitas, tarif, sisa kamar | Pelanggan | Tinggi |
| FR-08 | Menghitung total biaya otomatis (tarif per malam × kamar + pajak & layanan) | Sistem | Tinggi |
| FR-09 | Membuat reservasi satu atau lebih kamar di satu cabang | Pelanggan | Tinggi |
| FR-10 | **Memvalidasi ulang ketersediaan secara atomik** saat reservasi disimpan | Sistem | Tinggi |
| FR-11 | Menghasilkan kode reservasi unik (mis. `NSH-JMB-20261119-0001`) dan batas bayar | Sistem | Tinggi |

**C. Pembayaran**

| Kode | Kebutuhan | Aktor | Prioritas |
|---|---|---|---|
| FR-12 | Memilih metode: simulasi payment gateway atau transfer bank manual | Pelanggan | Tinggi |
| FR-13 | Menerima callback gateway (bertanda tangan) dan memperbarui status otomatis | Sistem | Tinggi |
| FR-14 | Mengunggah bukti transfer (JPG/PNG/PDF ≤ 2 MB); status menjadi *Menunggu Verifikasi* | Pelanggan | Tinggi |
| FR-15 | Admin menyetujui bukti (menjadi *Lunas*) atau menolaknya dengan alasan | Admin | Tinggi |
| FR-16 | Mengubah reservasi yang melewati batas bayar menjadi *Kedaluwarsa* dan melepas kamarnya | Sistem | Tinggi |
| FR-17 | Mengirim email notifikasi untuk setiap perubahan status penting | Sistem | Sedang |
| FR-18 | Mengunduh/mencetak voucher untuk reservasi *Lunas* | Pelanggan | Sedang |

**D. Riwayat & Pembatalan**

| Kode | Kebutuhan | Aktor | Prioritas |
|---|---|---|---|
| FR-19 | Melihat riwayat reservasi dengan filter status | Pelanggan | Tinggi |
| FR-20 | Membatalkan reservasi; sistem menampilkan estimasi refund sebelum konfirmasi | Pelanggan | Tinggi |
| FR-21 | Admin membatalkan reservasi dengan alasan wajib | Admin | Sedang |

**E. Data Master (Admin)**

| Kode | Kebutuhan | Aktor | Prioritas |
|---|---|---|---|
| FR-22 | CRUD **cabang** (termasuk rekening tujuan transfer) | Admin | Tinggi |
| FR-23 | CRUD **tipe kamar** per cabang (kapasitas, tarif dasar, foto) | Admin | Tinggi |
| FR-24 | CRUD **kamar fisik** (nomor, lantai, status tersedia/perawatan/nonaktif) | Admin | Tinggi |
| FR-25 | CRUD **fasilitas** dwibahasa dan menautkannya ke tipe kamar | Admin | Sedang |
| FR-26 | Mengatur **tarif musiman** per periode yang menimpa tarif dasar | Admin | Sedang |
| FR-27 | Mencegah penghapusan data master yang memiliki riwayat reservasi | Sistem | Tinggi |

**F. Pengelolaan Reservasi (Admin)**

| Kode | Kebutuhan | Aktor | Prioritas |
|---|---|---|---|
| FR-28 | Melihat, mencari, dan memfilter semua reservasi | Admin | Tinggi |
| FR-29 | Menandai reservasi *Lunas* sebagai *Check-in*, lalu *Selesai* | Admin | Sedang |

**G. Laporan & Dashboard**

| Kode | Kebutuhan | Aktor | Prioritas |
|---|---|---|---|
| FR-30 | Dashboard: reservasi, check-in, check-out hari ini; antrean verifikasi; pendapatan bulan berjalan | Admin | Tinggi |
| FR-31 | **Okupansi** = malam-kamar terjual ÷ malam-kamar tersedia × 100%, per cabang & gabungan | Admin | Tinggi |
| FR-32 | **Pendapatan** per cabang & gabungan per periode, dalam grafik | Admin | Tinggi |
| FR-33 | **Jumlah reservasi** per periode dan per status | Admin | Tinggi |
| FR-34 | **Tipe kamar terlaris** berdasarkan malam-kamar terjual | Admin | Sedang |
| FR-35 | Ekspor laporan ke CSV/Excel dan versi cetak PDF | Admin | Rendah |

## 1.4 Kebutuhan Non-Fungsional

| Kode | Kategori (ISO/IEC 25010) | Kebutuhan | Ukuran Penerimaan | Implementasi |
|---|---|---|---|---|
| NFR-01 | Keamanan | Kata sandi di-hash (bcrypt); HTTPS di produksi | Tidak ada kata sandi teks biasa | Cast `hashed`, panduan Certbot |
| NFR-02 | Keamanan | Proteksi CSRF, XSS, SQL injection (OWASP Top 10) | Lolos daftar periksa dasar | Token CSRF, escaping Blade, Eloquent |
| NFR-03 | Keamanan | Pelanggan hanya mengakses reservasinya sendiri | Akses lintas pengguna → HTTP 403 | `ReservationPolicy`, middleware `role` |
| NFR-04 | Keamanan | Unggahan divalidasi dan disimpan privat | Tidak bisa diakses lewat URL publik | Disk `local` + rute admin terotorisasi |
| NFR-05 | Keamanan | Pembatasan percobaan login | Percobaan ke-6 diblokir sementara | Rate limiter Breeze |
| NFR-06 | Performa | Halaman utama termuat ≤ 2 detik dengan ±10.000 reservasi | Diukur dengan profiler | Indeks basis data (Tahap 3) |
| NFR-07 | Konkurensi | Dua pemesanan bersamaan untuk kamar terakhir tidak boleh sama-sama berhasil | Hanya 1 yang sukses | `hotel:concurrency-test` (lulus) |
| NFR-08 | Usability | Responsif dari 360 px sampai desktop | Diuji di beberapa ukuran layar | Tailwind, tangkapan layar mobile |
| NFR-09 | Usability | Pemesanan selesai dalam ≤ 4 langkah dengan pesan validasi jelas | Uji pengguna | Stepper 4 langkah |
| NFR-10 | Lokalisasi | Semua teks dari file bahasa; format tanggal & uang sesuai locale | Tidak ada teks hard-code | `lang/`, helper `money()` |
| NFR-11 | Ketersediaan | Target 99%/bulan; backup harian | Jadwal backup terdokumentasi | Panduan deployment |
| NFR-12 | Keandalan | Kedaluwarsa otomatis setiap 5 menit | Log scheduler | `reservations:expire` |
| NFR-13 | Pemeliharaan | PSR-12, konvensi Laravel, service class | Lolos Pint | CI `pint --test` |
| NFR-14 | Auditabilitas | Setiap perubahan status tercatat (siapa, kapan, dari → ke) | Tabel log terisi | `reservation_status_logs` |

## 1.5 Aturan Bisnis

| Kode | Kategori | Aturan |
|---|---|---|
| BR-01 | Pemesanan | Check-in minimal **hari ini**, maksimal **365 hari** ke depan; check-out setelah check-in |
| BR-02 | Pemesanan | Lama menginap **1–14 malam** |
| BR-03 | Pemesanan | Satu reservasi untuk **satu cabang**, maksimal **5 kamar** |
| BR-04 | Pemesanan | Tamu per kamar ≤ **kapasitas tipe kamar** |
| BR-05 | Ketersediaan | Kamar terpakai bila ada reservasi berstatus Menunggu Pembayaran, Menunggu Verifikasi, Lunas, Check-in, atau Selesai yang **beririsan**: `checkin_baru < checkout_lama` **dan** `checkout_baru > checkin_lama`. Tanggal check-out tidak dihitung sebagai malam |
| BR-06 | Ketersediaan | Kamar berstatus *Perawatan/Nonaktif* tidak dapat dipesan |
| BR-07 | Tarif | Tarif **per malam**; tarif musiman menimpa tarif dasar; tarif disimpan sebagai *snapshot* saat pemesanan |
| BR-08 | Tarif | Total = subtotal + **pajak & layanan 21%** (dapat dikonfigurasi) |
| BR-09 | Pembayaran | Batas bayar **24 jam** sejak reservasi dibuat, atau **2 jam** bila check-in kurang dari 24 jam lagi |
| BR-10 | Pembayaran | Lewat batas tanpa pembayaran → **Kedaluwarsa**. Reservasi *Menunggu Verifikasi* tidak ikut kedaluwarsa |
| BR-11 | Pembayaran | Admin memverifikasi maksimal **1×24 jam**. Bukti boleh ditolak maksimal **2 kali**; **penolakan ke-3 membatalkan reservasi** |
| BR-12 | Pembayaran | Pembayaran **penuh** (tanpa DP) |
| BR-13 | Pembatalan | Pembatalan sebelum lunas tanpa biaya; dana transfer yang sudah masuk dikembalikan 100% |
| BR-14 | Pembatalan | Setelah lunas: **≥ 7 hari** sebelum check-in → refund 100%; **3–6 hari** → 50%; **< 3 hari / no-show** → 0%. Refund diproses manual |
| BR-15 | Pembatalan | Reservasi *Check-in, Selesai, Kedaluwarsa, Dibatalkan* tidak dapat dibatalkan |
| BR-16 | Operasional | Check-in mulai **14.00**, check-out paling lambat **12.00** WITA |
| BR-17 | Operasional | Tamu menunjukkan kode reservasi/voucher dan identitas resmi saat check-in |
| BR-18 | Status | `Menunggu Pembayaran → (Menunggu Verifikasi) → Lunas → Check-in → Selesai`; cabang akhir `Dibatalkan`, `Kedaluwarsa` |
| BR-19 | Data master | Data dengan riwayat reservasi tidak boleh dihapus permanen, cukup dinonaktifkan |
| BR-20 | Pembayaran | Bila bukti ditolak setelah batas bayar terlewati, batas bayar **diperpanjang 2 jam** sejak penolakan |

## 1.6 Asumsi

1. Nama jaringan hotel dan seluruh data bersifat fiktif.
2. **Kedua metode pembayaran** didukung agar demo lebih lengkap.
3. Sistem menetapkan **kamar fisik spesifik** saat reservasi dibuat (*room assignment*).
4. Status tambahan **Check-in** dan **Selesai** ditambahkan agar laporan okupansi dan pendapatan akurat.
5. Nilai kebijakan (pajak 21%, batas bayar 24/2 jam, refund 7/3 hari) adalah contoh yang wajar dan **dapat dikonfigurasi** di `config/hotel.php`.
6. Satu mata uang (IDR) dan satu zona waktu (WITA, `Asia/Makassar`).
7. Admin bersifat terpusat untuk semua cabang.
8. Email dikirim lewat SMTP uji (log/Mailpit) di lingkungan demo.

## Riwayat Perubahan

| Versi | Perubahan | Alasan |
|---|---|---|
| 1.0 | Versi awal (Tahap 1) | — |
| 1.1 | BR-11 diperjelas (penolakan ke-3 = batal); BR-20 ditambahkan | Ditemukan saat menyusun skenario use case (Tahap 2) |
| 1.2 | BR-05 mencakup status *Selesai*; kolom "Implementasi" pada NFR | Diselaraskan dengan implementasi (Tahap 5) |

## Referensi

- ISO/IEC. (2011). *ISO/IEC 25010:2011 Systems and software engineering — Systems and software Quality Requirements and Evaluation (SQuaRE) — System and software quality models*. International Organization for Standardization.
- ISO/IEC/IEEE. (2018). *ISO/IEC/IEEE 29148:2018 Systems and software engineering — Life cycle processes — Requirements engineering*. International Organization for Standardization.
- OWASP Foundation. (2021). *OWASP Top 10:2021*. https://owasp.org/Top10/

---

## 🇬🇧 English Version

### 1.1 Introduction

This document follows a condensed SRS structure based on ISO/IEC/IEEE 29148 (ISO/IEC/IEEE, 2018). Quality attributes follow ISO/IEC 25010 (ISO/IEC, 2011).

**Background.** Nusa Stay Hotels is a fictional chain with three Bali branches (Jimbaran, Ubud, Seminyak). Bookings arrive by phone, WhatsApp, and email and are recorded in separate per-branch ledgers. This causes:
- **P1 overbooking**;
- **P2** slow, manual receipt checks;
- **P3** unpaid bookings holding rooms;
- **P4** no customer self-service;
- **P5** manual reporting.

**Objectives:**
1. A centralized, real-time 24/7 booking channel.
2. **Overbooking prevention** via transactional validation and locking.
3. Digital payments (simulated gateway and receipt upload) with clear statuses.
4. Centralized master data.
5. Per-branch and consolidated dashboards and reports.
6. A bilingual ID/EN interface.

**Scope** is the same as the Indonesian table above. Out of scope: real gateways, OTA integration, a full front office, automated refunds, loyalty programs, native apps, multi-currency, staff role hierarchy, and room moves.

### 1.2 Actors

**Customer** and **Admin** (primary). **Scheduler** and the **simulated Payment Gateway** (supporting).

### 1.3 Functional Requirements

| Code | Requirement | Actor | Priority |
|---|---|---|---|
| FR-01 | Customer registration with email verification | Customer | High |
| FR-02 | Login, logout, password reset | Customer, Admin | High |
| FR-03 | Role-based page access | System | High |
| FR-04 | View/update profile | Customer | Medium |
| FR-05 | Switch UI language (ID/EN), persisted in session/profile | Customer, Admin | High |
| FR-06 | Show available rooms by branch, dates, guests | Customer | High |
| FR-07 | Room-type details: photos, description, capacity, facilities, rate, rooms left | Customer | High |
| FR-08 | Automatic total (nightly rates × rooms + tax & service) | System | High |
| FR-09 | Book one or more rooms at one branch | Customer | High |
| FR-10 | **Atomic availability re-validation** when saving | System | High |
| FR-11 | Unique booking code and payment deadline | System | High |
| FR-12 | Choose payment method: simulated gateway or bank transfer | Customer | High |
| FR-13 | Signed gateway callback updates status automatically | System | High |
| FR-14 | Upload receipt (JPG/PNG/PDF ≤ 2 MB) → *Awaiting Verification* | Customer | High |
| FR-15 | Admin approves (→ *Paid*) or rejects with a reason | Admin | High |
| FR-16 | Overdue unpaid bookings → *Expired*, rooms released | System | High |
| FR-17 | Email notifications on key status changes | System | Medium |
| FR-18 | Download/print voucher for *Paid* bookings | Customer | Medium |
| FR-19 | Booking history with status filter | Customer | High |
| FR-20 | Cancel with refund estimate shown first | Customer | High |
| FR-21 | Admin cancellation with mandatory reason | Admin | Medium |
| FR-22 | Branch CRUD (incl. transfer account) | Admin | High |
| FR-23 | Room-type CRUD (capacity, base rate, photos) | Admin | High |
| FR-24 | Physical room CRUD (number, floor, status) | Admin | High |
| FR-25 | Bilingual facility CRUD linked to room types | Admin | Medium |
| FR-26 | Seasonal rates overriding the base rate | Admin | Medium |
| FR-27 | Prevent deleting master data with booking history | System | High |
| FR-28 | View, search, filter all bookings | Admin | High |
| FR-29 | Mark *Paid* → *Checked-in* → *Completed* | Admin | Medium |
| FR-30 | Dashboard: today's bookings/check-ins/check-outs, verification queue, month revenue | Admin | High |
| FR-31 | **Occupancy** = room-nights sold ÷ available, per branch and consolidated | Admin | High |
| FR-32 | **Revenue** per branch/period with charts | Admin | High |
| FR-33 | **Booking counts** per period and status | Admin | High |
| FR-34 | **Best-selling room types** by room-nights sold | Admin | Medium |
| FR-35 | CSV/Excel export and printable PDF | Admin | Low |

### 1.4 Non-Functional Requirements

The same 14 NFRs as the Indonesian table, each with an acceptance measure and its implementation:
- **Security (NFR-01–05):** bcrypt hashing and HTTPS; CSRF/XSS/SQL-injection protection; ownership policy returning 403; private uploads; login rate limiting.
- **Performance (NFR-06):** ≤ 2 s pages with ~10k bookings.
- **Concurrency (NFR-07):** only one of two simultaneous last-room bookings may succeed. Verified by `hotel:concurrency-test`.
- **Usability & localization (NFR-08–10):** responsive from 360 px; ≤ 4 booking steps; all text in language files.
- **Availability & reliability (NFR-11–12):** 99% availability target with daily backups; auto-expiry every 5 minutes.
- **Maintainability (NFR-13):** PSR-12, checked by Pint in CI.
- **Auditability (NFR-14):** status-change log.

### 1.5 Business Rules

| Code | Rule |
|---|---|
| BR-01 | Check-in from today up to 365 days ahead; check-out after check-in |
| BR-02 | Stay length 1–14 nights |
| BR-03 | One branch per booking, max 5 rooms |
| BR-04 | Guests per room ≤ room-type capacity |
| BR-05 | A room is occupied if an Awaiting Payment / Awaiting Verification / Paid / Checked-in / Completed booking **overlaps**: `new_in < old_out AND new_out > old_in` (check-out date is not a night) |
| BR-06 | Rooms under Maintenance/Inactive cannot be booked |
| BR-07 | Per-night rates; seasonal rates override base; rates snapshotted at booking |
| BR-08 | Total = subtotal + 21% tax & service (configurable) |
| BR-09 | Payment deadline 24 h after booking, or 2 h if check-in is < 24 h away |
| BR-10 | Unpaid past deadline → Expired; Awaiting Verification never auto-expires |
| BR-11 | Admin verifies within 24 h; receipts may be rejected at most twice; **3rd rejection cancels** |
| BR-12 | Full payment only |
| BR-13 | Free cancellation before payment; transferred funds refunded 100% |
| BR-14 | After payment: ≥ 7 days → 100%, 3–6 days → 50%, < 3 days/no-show → 0%; refunds processed manually |
| BR-15 | Checked-in, Completed, Expired, Cancelled bookings cannot be cancelled |
| BR-16 | Check-in from 14:00, check-out by 12:00 WITA |
| BR-17 | Voucher/code and official ID required at check-in |
| BR-18 | Lifecycle: Awaiting Payment → (Awaiting Verification) → Paid → Checked-in → Completed; terminal: Cancelled, Expired |
| BR-19 | Master data with booking history is deactivated, never hard-deleted |
| BR-20 | A receipt rejected after the deadline extends the deadline by 2 h from rejection |

### 1.6 Assumptions

Fictional data; both payment methods supported; a specific physical room is assigned at booking; Checked-in/Completed statuses added; policy values configurable in `config/hotel.php`; IDR and WITA only; centralized admin; test SMTP in the demo.
