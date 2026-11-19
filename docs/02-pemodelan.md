# 02 · Pemodelan Proses & Sistem

**Sistem Pemesanan Hotel Multi-Cabang — "Nusa Stay Hotels" (fiktif)**

> 🇮🇩 Bahasa Indonesia · [🇬🇧 English](#-english-version) · ← [01 · SRS](01-srs.md) · → [03 · Basis Data](03-basis-data.md)
>
> Status: **as-built**. Diagram BPMN, use case, dan activity ditulis dengan Mermaid `flowchart` karena Mermaid belum mendukung notasi tersebut secara native. Untuk use case juga tersedia versi PlantUML (notasi UML standar).

| Notasi BPMN/UML | Representasi di Mermaid |
|---|---|
| Event mulai/selesai | Lingkaran `(( ))` atau stadion `([ ])` |
| Task / aktivitas | Persegi `[ ]` |
| Gateway / keputusan | Belah ketupat `{ }` |
| Lane (swimlane) | `subgraph` |
| «include» / «extend» | Panah putus-putus berlabel |

---

## 2.1 BPMN Proses Pemesanan

Elemen yang dipakai mengacu pada BPMN 2.0.2 (Object Management Group [OMG], 2013).

### 2.1.1 As-Is: Pemesanan Manual (Telepon/WhatsApp)

```mermaid
flowchart LR
  subgraph PEL["Pelanggan"]
    a0((Mulai)) --> a1["Menghubungi cabang via<br/>telepon / WhatsApp"]
    a4["Memilih kamar &<br/>memberikan data diri"]
    a6["Transfer ke rekening hotel"]
    a7["Mengirim foto bukti<br/>via WhatsApp"]
    a9["Menerima konfirmasi"] --> a10((Selesai))
    ax((Batal))
  end

  subgraph RES["Resepsionis Cabang"]
    b1["Menerima permintaan"] --> b2["Mengecek buku /<br/>spreadsheet cabang"]
    b2 --> b3{"Kamar<br/>tersedia?"}
    b5["Mencatat reservasi<br/>secara manual"]
    b6["Mencocokkan bukti dengan<br/>mutasi rekening"]
    b7{"Dana<br/>masuk?"}
    b7a["Mengingatkan pelanggan<br/>via WhatsApp"]
    b8["Mengirim konfirmasi<br/>via WhatsApp"]
  end

  subgraph MGT["Manajemen Kantor Pusat"]
    c1["Meminta rekap dari<br/>tiap cabang"] --> c2["Menggabungkan laporan<br/>di spreadsheet"] --> c3((Laporan<br/>bulanan))
  end

  a1 --> b1
  b3 -->|Tidak| ax
  b3 -->|Ya| a4
  a4 --> b5 --> a6 --> a7 --> b6 --> b7
  b7 -->|Belum| b7a --> b6
  b7 -->|Ya| b8 --> a9
  b8 -.->|akhir bulan| c1

  classDef pain fill:#fde2e2,stroke:#c0392b,color:#000
  class b2,b5,b6,b7a,c2 pain
```

| Aktivitas (merah) | Masalah | Keterangan |
|---|---|---|
| Mengecek buku/spreadsheet | P1, P4 | Data tidak real-time; dua resepsionis bisa menjual kamar yang sama |
| Mencatat reservasi manual | P1 | Tidak ada penguncian kamar |
| Mencocokkan bukti transfer | P2 | Bukti tercecer di chat |
| Mengingatkan pelanggan | P3 | Tidak ada batas waktu jelas |
| Menggabungkan laporan | P5 | Rekap manual, terlambat |

### 2.1.2 To-Be: Pemesanan Online

```mermaid
flowchart LR
  subgraph PEL["Pelanggan"]
    p0((Mulai)) --> p1["Mencari kamar:<br/>cabang, tanggal, jumlah tamu"]
    p3["Memilih tipe &<br/>jumlah kamar"]
    p4{"Sudah<br/>login?"}
    p5["Login / Registrasi"]
    p6["Mengisi data tamu &<br/>konfirmasi pesanan"]
    p8{"Metode<br/>bayar?"}
    p9["Bayar via gateway<br/>simulasi (MockPay)"]
    p10["Transfer &<br/>unggah bukti"]
    p13["Menerima email<br/>+ voucher"] --> p14((Selesai))
  end

  subgraph SYS["Sistem"]
    s1["Menampilkan tipe kamar<br/>tersedia + sisa kamar"]
    s2{"Masih tersedia?<br/>transaksi + lock"}
    s6["Pesan: kamar<br/>baru saja habis"]
    s3["Membuat reservasi, alokasi kamar,<br/>status Menunggu Pembayaran,<br/>set batas bayar"]
    s4["Status:<br/>Menunggu Verifikasi"]
    s5["Status: Lunas<br/>kirim email + voucher"]
  end

  subgraph ADM["Admin"]
    d1["Memeriksa<br/>bukti transfer"] --> d2{"Valid?"}
    d3["Menolak<br/>dengan alasan"]
  end

  subgraph AUTO["Scheduler (otomatis)"]
    t1(["Setiap 5 menit"]) --> t2{"Lewat batas bayar<br/>& belum bayar?"}
    t2 -->|Ya| t3["Status: Kedaluwarsa<br/>kamar dilepas"]
  end

  p1 --> s1 --> p3 --> p4
  p4 -->|Belum| p5 --> p6
  p4 -->|Sudah| p6
  p6 --> s2
  s2 -->|Tidak| s6 --> p1
  s2 -->|Ya| s3 --> p8
  p8 -->|Gateway| p9
  p9 -->|callback sukses| s5
  p9 -->|gagal| p8
  p8 -->|Transfer| p10 --> s4 --> d1
  d2 -->|Ya| s5
  d2 -->|Tidak| d3 -->|unggah ulang| p10
  s5 --> p13

  classDef gain fill:#e3f6e8,stroke:#1e8449,color:#000
  class s2,s3,t3,s5 gain
```

| Aspek | As-Is | To-Be | Masalah terselesaikan |
|---|---|---|---|
| Cek ketersediaan | Manual per cabang | Otomatis, terpusat, real-time | P1, P4 |
| Pencegahan pemesanan ganda | Tidak ada | Transaksi DB + row lock + indeks unik | P1 |
| Batas waktu bayar | Tidak jelas | 24 jam / 2 jam, kedaluwarsa otomatis | P3 |
| Verifikasi pembayaran | Chat WhatsApp | Antrean verifikasi + bukti tersimpan | P2 |
| Jam layanan | Jam kerja staf | 24/7 self-service | P4 |
| Laporan | Rekap bulanan manual | Dashboard real-time | P5 |

---

## 2.2 Use Case

| ID | Use Case | Aktor | Relasi | FR |
|---|---|---|---|---|
| UC-01 | Registrasi & Verifikasi Email | Pelanggan | — | FR-01 |
| UC-02 | Login / Logout / Reset Sandi | Pelanggan, Admin | — | FR-02, FR-03 |
| UC-03 | Mengganti Bahasa | Pelanggan, Admin | — | FR-05 |
| UC-04 | Mengelola Profil | Pelanggan | — | FR-04 |
| UC-05 | Mencari Kamar | Pelanggan | «include» UC-08 | FR-06 |
| UC-06 | Melihat Detail Tipe Kamar | Pelanggan | «extend» UC-05 | FR-07 |
| UC-07 | Memesan Kamar | Pelanggan | «include» UC-08, UC-24 | FR-08–FR-11 |
| UC-08 | Memeriksa Ketersediaan | (internal) | diinclude | FR-10 |
| UC-09 | Membayar Reservasi | Pelanggan | diextend UC-10, UC-11 | FR-12 |
| UC-10 | Bayar via Gateway Simulasi | Pelanggan, Gateway | «extend» UC-09 | FR-13 |
| UC-11 | Mengunggah Bukti Transfer | Pelanggan | «extend» UC-09 | FR-14 |
| UC-12 | Melihat Riwayat Reservasi | Pelanggan | — | FR-19 |
| UC-13 | Membatalkan Reservasi | Pelanggan, Admin | «include» UC-24 | FR-20, FR-21 |
| UC-14 | Mengunduh Voucher | Pelanggan | «extend» UC-12 | FR-18 |
| UC-15 | Memverifikasi Pembayaran | Admin | «include» UC-24 | FR-15 |
| UC-16 | Mengelola Cabang | Admin | — | FR-22, FR-27 |
| UC-17 | Mengelola Tipe Kamar & Fasilitas | Admin | — | FR-23, FR-25, FR-27 |
| UC-18 | Mengelola Kamar | Admin | — | FR-24, FR-27 |
| UC-19 | Mengelola Tarif Musiman | Admin | — | FR-26 |
| UC-20 | Mengelola Reservasi & Check-in/out | Admin | — | FR-28, FR-29 |
| UC-21 | Melihat Dashboard | Admin | — | FR-30 |
| UC-22 | Melihat & Mengekspor Laporan | Admin | — | FR-31–FR-35 |
| UC-23 | Mengedaluwarsakan Reservasi | Scheduler | «include» UC-24 | FR-16 |
| UC-24 | Mengirim Notifikasi Email | (internal) | diinclude | FR-17 |

### 2.2.1 Diagram (Mermaid)

```mermaid
flowchart LR
  P["👤 Pelanggan"]
  A["👤 Admin"]
  S["⏱️ Scheduler<br/>(sistem)"]
  G["💳 Payment Gateway<br/>(simulasi)"]

  subgraph SYS["Sistem Pemesanan Hotel Nusa Stay"]
    direction TB
    UC01(["UC-01 Registrasi & Verifikasi Email"])
    UC02(["UC-02 Login / Logout / Reset Sandi"])
    UC03(["UC-03 Mengganti Bahasa"])
    UC04(["UC-04 Mengelola Profil"])
    UC05(["UC-05 Mencari Kamar"])
    UC06(["UC-06 Melihat Detail Tipe Kamar"])
    UC07(["UC-07 Memesan Kamar"])
    UC08(["UC-08 Memeriksa Ketersediaan"])
    UC09(["UC-09 Membayar Reservasi"])
    UC10(["UC-10 Bayar via Gateway Simulasi"])
    UC11(["UC-11 Mengunggah Bukti Transfer"])
    UC12(["UC-12 Melihat Riwayat Reservasi"])
    UC13(["UC-13 Membatalkan Reservasi"])
    UC14(["UC-14 Mengunduh Voucher"])
    UC15(["UC-15 Memverifikasi Pembayaran"])
    UC16(["UC-16 Mengelola Cabang"])
    UC17(["UC-17 Mengelola Tipe Kamar & Fasilitas"])
    UC18(["UC-18 Mengelola Kamar"])
    UC19(["UC-19 Mengelola Tarif Musiman"])
    UC20(["UC-20 Mengelola Reservasi & Check-in/out"])
    UC21(["UC-21 Melihat Dashboard"])
    UC22(["UC-22 Melihat & Mengekspor Laporan"])
    UC23(["UC-23 Mengedaluwarsakan Reservasi"])
    UC24(["UC-24 Mengirim Notifikasi Email"])
  end

  P --- UC01 & UC02 & UC03 & UC04 & UC05 & UC07 & UC09 & UC12 & UC13
  A --- UC02 & UC03 & UC13 & UC15 & UC16 & UC17 & UC18 & UC19 & UC20 & UC21 & UC22
  UC10 --- G
  UC23 --- S

  UC05 -.->|«include»| UC08
  UC07 -.->|«include»| UC08
  UC06 -.->|«extend»| UC05
  UC10 -.->|«extend»| UC09
  UC11 -.->|«extend»| UC09
  UC14 -.->|«extend»| UC12
  UC07 -.->|«include»| UC24
  UC13 -.->|«include»| UC24
  UC15 -.->|«include»| UC24
  UC23 -.->|«include»| UC24
```

### 2.2.2 Diagram (PlantUML)

GitHub tidak merender PlantUML secara native. Render lewat [plantuml.com](https://www.plantuml.com/plantuml) atau ekstensi editor.

```plantuml
@startuml UseCase_NusaStay
left to right direction
skinparam packageStyle rectangle
actor Pelanggan as P
actor Admin as A
actor "Scheduler" as S <<sistem>>
actor "Payment Gateway\n(Simulasi)" as G <<sistem>>

rectangle "Sistem Pemesanan Hotel Nusa Stay" {
  usecase "UC-01 Registrasi" as UC01
  usecase "UC-02 Login / Reset Sandi" as UC02
  usecase "UC-03 Mengganti Bahasa" as UC03
  usecase "UC-04 Mengelola Profil" as UC04
  usecase "UC-05 Mencari Kamar" as UC05
  usecase "UC-06 Detail Tipe Kamar" as UC06
  usecase "UC-07 Memesan Kamar" as UC07
  usecase "UC-08 Memeriksa Ketersediaan" as UC08
  usecase "UC-09 Membayar Reservasi" as UC09
  usecase "UC-10 Bayar via Gateway" as UC10
  usecase "UC-11 Unggah Bukti Transfer" as UC11
  usecase "UC-12 Riwayat Reservasi" as UC12
  usecase "UC-13 Membatalkan Reservasi" as UC13
  usecase "UC-14 Mengunduh Voucher" as UC14
  usecase "UC-15 Verifikasi Pembayaran" as UC15
  usecase "UC-16 Kelola Cabang" as UC16
  usecase "UC-17 Kelola Tipe Kamar & Fasilitas" as UC17
  usecase "UC-18 Kelola Kamar" as UC18
  usecase "UC-19 Kelola Tarif Musiman" as UC19
  usecase "UC-20 Kelola Reservasi" as UC20
  usecase "UC-21 Dashboard" as UC21
  usecase "UC-22 Laporan & Ekspor" as UC22
  usecase "UC-23 Kedaluwarsa Otomatis" as UC23
  usecase "UC-24 Notifikasi Email" as UC24
}

P --> UC01
P --> UC02
P --> UC03
P --> UC04
P --> UC05
P --> UC07
P --> UC09
P --> UC12
P --> UC13
A --> UC02
A --> UC03
A --> UC13
A --> UC15
A --> UC16
A --> UC17
A --> UC18
A --> UC19
A --> UC20
A --> UC21
A --> UC22
UC10 --> G
S --> UC23

UC05 ..> UC08 : <<include>>
UC07 ..> UC08 : <<include>>
UC06 ..> UC05 : <<extend>>
UC10 ..> UC09 : <<extend>>
UC11 ..> UC09 : <<extend>>
UC14 ..> UC12 : <<extend>>
UC07 ..> UC24 : <<include>>
UC13 ..> UC24 : <<include>>
UC15 ..> UC24 : <<include>>
UC23 ..> UC24 : <<include>>
@enduml
```

---

## 2.3 Skenario Use Case Utama

Format *fully dressed use case* (Cockburn, 2001).

### UC-05 · Mencari Kamar

| Elemen | Isi |
|---|---|
| Aktor | Pelanggan (termasuk tamu yang belum login) |
| Prakondisi | Ada cabang aktif dengan tipe kamar aktif |
| Pascakondisi | Daftar tipe kamar ditampilkan; tidak ada data berubah |
| FR / BR | FR-06, FR-07 · BR-01, BR-02, BR-04–BR-06 |

| # | Aktor | Sistem |
|---|---|---|
| 1 | Mengisi cabang, check-in, check-out, tamu, kamar; menekan "Cari" | — |
| 2 | — | Memvalidasi input (BR-01, BR-02) |
| 3 | — | Menghitung kamar tersedia per tipe = kamar aktif − kamar dengan malam beririsan (UC-08) |
| 4 | — | Menandai tipe yang memenuhi kapasitas & jumlah kamar; tipe lain tampil redup |
| 5 | — | Menampilkan foto, kapasitas, fasilitas, tarif per malam, estimasi total, label "sisa n kamar" |
| 6 | Membuka detail (UC-06) atau memesan (UC-07) | — |

| Kode | Kondisi | Alur alternatif |
|---|---|---|
| A1 | Input tidak valid | Pesan galat per field dalam bahasa aktif |
| A2 | Tidak ada tipe tersedia | Pesan + saran cabang lain yang tersedia pada tanggal sama |
| A3 | Mengubah urutan | Hasil diurutkan ulang tanpa menghitung ulang ketersediaan |

### UC-07 · Memesan Kamar

| Elemen | Isi |
|---|---|
| Aktor | Pelanggan |
| Prakondisi | Sudah mencari (UC-05); login dengan email terverifikasi |
| Pascakondisi sukses | Reservasi tersimpan, kamar teralokasi, status **Menunggu Pembayaran**, batas bayar ditetapkan, email terkirim |
| Pascakondisi gagal | Tidak ada data tersimpan (rollback) |
| FR / BR | FR-08–FR-11, FR-17 · BR-01–BR-09 |

| # | Aktor | Sistem |
|---|---|---|
| 1 | Menekan "Pesan Sekarang" | — |
| 2 | — | Menampilkan checkout: rincian tarif per malam, pajak, total |
| 3 | Mengisi data tamu, tamu per kamar, permintaan khusus; menyetujui kebijakan; menekan "Konfirmasi" | — |
| 4 | — | Memvalidasi via Form Request (BR-01–BR-04) |
| 5 | — | Membuka transaksi; **mengunci baris cabang** (`FOR UPDATE`); memeriksa ulang kamar bebas |
| 6 | — | Menyimpan reservasi, item kamar (snapshot tarif), dan `room_nights`; mencatat log; commit |
| 7 | — | Mengirim email (antrean, setelah commit); mengarahkan ke halaman pembayaran |

| Kode | Kondisi | Alur alternatif |
|---|---|---|
| A1 | Belum login | Diarahkan ke login lalu kembali ke checkout |
| A2 | Validasi gagal | Pesan per field; isian tidak hilang |
| A3 | Kamar habis diambil pengguna lain | Rollback; kembali ke hasil pencarian dengan pesan "kamar baru saja habis" |
| E1 | Indeks unik `room_nights` menolak | Ditangani sama dengan A3 |

### UC-09 · Membayar Reservasi (UC-10, UC-11)

| Elemen | Isi |
|---|---|
| Prakondisi | Reservasi milik pelanggan, **Menunggu Pembayaran**, batas bayar belum lewat |
| Pascakondisi | Gateway sukses: **Lunas**. Transfer: **Menunggu Verifikasi** |
| FR / BR | FR-12–FR-14, FR-17, FR-18 · BR-09–BR-12 |

| # | Aktor | Sistem |
|---|---|---|
| 1 | Membuka halaman pembayaran | Menampilkan total, hitung mundur, metode |
| 2 | Memilih gateway, menekan "Bayar" | Membuat `payment` (pending, `gateway_ref` unik); mengarahkan ke MockPay |
| 3 | Memilih "Simulasikan BERHASIL" | — |
| 4 | Gateway mengirim callback (ref, nominal, hasil, signature HMAC) | Memverifikasi signature & nominal; payment approved; reservasi **Lunas**; email + voucher |

| Kode | Kondisi | Alur alternatif |
|---|---|---|
| A1 | Memilih transfer (UC-11) | Menampilkan rekening cabang & nominal; pelanggan mengunggah bukti; disimpan privat; status **Menunggu Verifikasi**; admin diberi notifikasi |
| A2 | Simulasi gagal | Payment failed; tetap Menunggu Pembayaran |
| A3 | Batas bayar lewat | Pembayaran tidak dapat dilakukan |
| A4 | Bukti sebelumnya ditolak | Alasan & sisa kesempatan ditampilkan |
| E1 | Signature/nominal salah | HTTP 400, status tidak berubah |
| E2 | Callback sukses tiba setelah kedaluwarsa | Sistem mencoba alokasi ulang kamar yang sama; bila gagal, refund 100% ditandai |

### UC-13 · Membatalkan Reservasi

| Elemen | Isi |
|---|---|
| Prakondisi | Status Menunggu Pembayaran / Menunggu Verifikasi / Lunas (BR-15); pelanggan pemilik atau admin |
| Pascakondisi | Status **Dibatalkan**, refund tercatat, `room_nights` dihapus, email terkirim |

| # | Aktor | Sistem |
|---|---|---|
| 1 | Menekan "Batalkan reservasi" | Memeriksa Policy & status |
| 2 | — | Menampilkan estimasi refund (BR-13/BR-14) |
| 3 | Mengonfirmasi (alasan wajib untuk admin) | Dalam satu transaksi: simpan refund, batalkan pembayaran menggantung, lepas kamar, ubah status + log |

| Kode | Kondisi | Alur alternatif |
|---|---|---|
| A1 | Status tidak dapat dibatalkan | Tombol tidak tampil; akses paksa → 403 |
| A2 | Admin (no-show) | Alasan wajib; refund mengikuti kebijakan |

### UC-15 · Memverifikasi Pembayaran

| Elemen | Isi |
|---|---|
| Prakondisi | Admin login; ada pembayaran `submitted` |
| Pascakondisi | Disetujui: **Lunas**. Ditolak: **Menunggu Pembayaran**, atau **Dibatalkan** pada penolakan ke-3 |

| # | Aktor | Sistem |
|---|---|---|
| 1 | Membuka antrean verifikasi | Daftar terlama di atas; penanda merah bila > 24 jam |
| 2 | Memilih pembayaran | Menampilkan bukti (rute terotorisasi), tagihan, data pengirim, daftar periksa |
| 3 | Mencentang daftar periksa, menekan "Setujui" | Mengunci reservasi & pembayaran; approved; reservasi **Lunas**; email voucher |

| Kode | Kondisi | Alur alternatif |
|---|---|---|
| A1 | Bukti tidak valid | Alasan wajib; status kembali **Menunggu Pembayaran**; batas bayar diperpanjang 2 jam bila sudah lewat (BR-20) |
| A2 | Penolakan ke-3 | Reservasi **Dibatalkan** (BR-11) |
| A3 | Sudah diproses admin lain | Pesan "sudah diproses"; tidak ada perubahan ganda |

---

## 2.4 Activity Diagram

### AD-01 · Pencarian & Pemesanan

```mermaid
flowchart TD
  subgraph PEL["Pelanggan"]
    a0(["● Mulai"])
    a1["Mengisi form pencarian"]
    a5["Memilih tipe & jumlah kamar"]
    a6{"Sudah login?"}
    a7["Login / Registrasi"]
    a8["Mengisi data tamu &<br/>menyetujui kebijakan"]
    a12(["◉ Halaman pembayaran"])
  end
  subgraph SYS["Sistem"]
    s1{"Input valid?<br/>BR-01, BR-02"}
    s2["Menghitung kamar tersedia per tipe"]
    s3{"Ada tipe<br/>tersedia?"}
    s4b["Saran tanggal / cabang lain"]
    s4["Menampilkan hasil + sisa kamar"]
    s5["Checkout: rincian tarif, pajak, total"]
    s6{"Data valid?<br/>BR-03, BR-04"}
    s7["BEGIN · kunci baris cabang"]
    s8{"Kamar masih<br/>cukup?"}
    s9["ROLLBACK · kamar baru saja habis"]
    s10["Alokasikan kamar, simpan reservasi,<br/>room_nights, log · COMMIT"]
    s11["Email konfirmasi (antrean)"]
  end
  a0 --> a1 --> s1
  s1 -->|Tidak| a1
  s1 -->|Ya| s2 --> s3
  s3 -->|Tidak| s4b --> a1
  s3 -->|Ya| s4 --> a5 --> a6
  a6 -->|Belum| a7 --> s5
  a6 -->|Sudah| s5
  s5 --> a8 --> s6
  s6 -->|Tidak| a8
  s6 -->|Ya| s7 --> s8
  s8 -->|Tidak| s9 --> s2
  s8 -->|Ya| s10 --> s11 --> a12
```

### AD-02 · Pembayaran

```mermaid
flowchart TD
  subgraph PEL["Pelanggan"]
    p0(["● Buka halaman pembayaran"])
    p2{"Pilih metode"}
    p3["Klik Bayar di MockPay"]
    p4{"Hasil<br/>simulasi?"}
    p6["Transfer & unggah bukti"]
    pEnd(["◉ Menerima voucher"])
    pExp(["◉ Kedaluwarsa"])
    pCan(["◉ Dibatalkan"])
  end
  subgraph SYS["Sistem"]
    s1{"Menunggu Pembayaran &<br/>belum lewat batas?"}
    s2["Total, hitung mundur, metode"]
    s3["payment: gateway, pending"]
    s4["Verifikasi signature · approved"]
    s5["payment: failed"]
    s6{"File valid?<br/>JPG/PNG/PDF ≤ 2 MB"}
    s7["Simpan privat · Menunggu Verifikasi"]
    s8["Lunas · log · email + voucher"]
  end
  subgraph ADM["Admin"]
    d1["Memeriksa bukti"] --> d2{"Disetujui?"}
  end
  p0 --> s1
  s1 -->|Tidak| pExp
  s1 -->|Ya| s2 --> p2
  p2 -->|Gateway| s3 --> p3 --> p4
  p4 -->|Berhasil| s4 --> s8
  p4 -->|Gagal| s5 --> s2
  p2 -->|Transfer| p6 --> s6
  s6 -->|Tidak| p6
  s6 -->|Ya| s7 --> d1
  d2 -->|Ya| s8
  d2 -->|"Tidak, masih ada kesempatan"| s2
  d2 -->|"Tidak, penolakan ke-3"| pCan
  s8 --> pEnd
```

### AD-03 · Pembatalan

```mermaid
flowchart TD
  subgraph AKT["Pelanggan / Admin"]
    c0(["● Mulai"])
    c1["Klik Batalkan"]
    c6["Membaca estimasi refund,<br/>mengisi alasan"]
    c7{"Konfirmasi?"}
    cEnd(["◉ Selesai"])
  end
  subgraph SYS["Sistem"]
    s1{"Pemilik atau admin?"}
    s1x["HTTP 403"]
    s2{"Dapat dibatalkan?<br/>BR-15"}
    s3{"Status Lunas?"}
    s4["Belum bayar: refund 0<br/>Menunggu Verifikasi: dana transfer 100%"]
    s5{"Sisa hari ke check-in"}
    s51["Refund 100%"]
    s52["Refund 50%"]
    s53["Refund 0%"]
    s8["Dibatalkan · simpan refund ·<br/>hapus room_nights · log"]
    s9["Email pembatalan"]
  end
  c0 --> c1 --> s1
  s1 -->|Tidak| s1x --> cEnd
  s1 -->|Ya| s2
  s2 -->|Tidak| cEnd
  s2 -->|Ya| s3
  s3 -->|Tidak| s4 --> c6
  s3 -->|Ya| s5
  s5 -->|"≥ 7 hari"| s51 --> c6
  s5 -->|"3–6 hari"| s52 --> c6
  s5 -->|"kurang dari 3 hari"| s53 --> c6
  c6 --> c7
  c7 -->|Tidak| cEnd
  c7 -->|Ya| s8 --> s9 --> cEnd
```

### State Diagram Status Reservasi (BR-18)

Diagram ini diimplementasikan di `App\Enums\ReservationStatus::allowedTransitions()`.

```mermaid
stateDiagram-v2
  state "Menunggu Pembayaran" as MP
  state "Menunggu Verifikasi" as MV
  state "Lunas" as LN
  state "Check-in" as CI
  state "Selesai" as SL
  state "Dibatalkan" as DB
  state "Kedaluwarsa" as KD

  [*] --> MP : reservasi dibuat
  MP --> MV : unggah bukti transfer
  MP --> LN : callback gateway sukses
  MP --> KD : lewat batas bayar (scheduler)
  MP --> DB : dibatalkan
  MV --> LN : admin menyetujui
  MV --> MP : admin menolak (sisa kesempatan)
  MV --> DB : penolakan ke-3 / dibatalkan
  LN --> CI : admin check-in
  LN --> DB : dibatalkan (refund BR-14)
  CI --> SL : admin check-out
  KD --> LN : callback terlambat & kamar masih bebas
  SL --> [*]
  DB --> [*]
```

---

## 2.5 Sequence Diagram

### SD-01 · Pemesanan Kamar

```mermaid
sequenceDiagram
  autonumber
  actor P as Pelanggan
  participant C as CheckoutController
  participant FR as StoreReservationRequest
  participant RS as ReservationService
  participant AS as AvailabilityService
  participant PS as PricingService
  participant DB as MySQL
  participant Q as Queue (Mail)

  P->>C: POST /checkout (+ token CSRF)
  C->>FR: validate() & authorize()
  alt Validasi gagal
    FR-->>P: kembali + pesan galat per field
  else Validasi lolos
    C->>RS: create(user, data)
    RS->>DB: SELECT branch_id dari room_types (sebelum transaksi)
    RS->>DB: BEGIN
    RS->>DB: SELECT branches WHERE id = ? FOR UPDATE (lapis 1)
    RS->>PS: quote(roomType, checkIn, checkOut, qty)
    PS-->>RS: rincian per malam, subtotal, pajak, total
    RS->>DB: INSERT reservations + status log
    RS->>AS: freeRooms(roomTypeId, checkIn, checkOut)
    AS->>DB: SELECT rooms WHERE tidak punya room_nights beririsan (lapis 2)
    alt Kamar bebas kurang dari qty
      RS->>DB: ROLLBACK
      RS-->>C: RoomNotAvailableException
      C-->>P: redirect ke pencarian + "kamar baru saja habis"
    else Cukup
      RS->>DB: INSERT reservation_rooms (snapshot tarif)
      RS->>DB: INSERT room_nights (UNIQUE room_id+stay_date, lapis 3)
      RS->>DB: COMMIT
      RS->>Q: ReservationNotification "created" (afterCommit)
      RS-->>C: Reservation
      C-->>P: redirect ke halaman pembayaran
    end
  end
```

### SD-02 · Verifikasi Pembayaran (Transfer)

```mermaid
sequenceDiagram
  autonumber
  actor P as Pelanggan
  actor A as Admin
  participant PC as PaymentController
  participant PS as PaymentService
  participant ST as Storage (privat)
  participant VC as Admin PaymentVerificationController
  participant PV as PaymentVerificationService
  participant DB as MySQL
  participant N as Notification

  P->>PC: POST /reservations/{code}/payment/proof
  PC->>PS: submitProof(reservation, file, data)
  PS->>ST: store("payment-proofs/…")
  PS->>DB: BEGIN · lock reservasi · INSERT payment (submitted)
  PS->>DB: status → Menunggu Verifikasi + log · COMMIT
  PS->>N: notifikasi ke semua admin
  PC-->>P: halaman status "bukti diterima"

  A->>VC: GET /admin/payments/{id}
  VC->>ST: stream bukti (rute terotorisasi)
  alt Disetujui
    A->>VC: POST approve
    VC->>PV: approve(payment, admin)
    PV->>DB: BEGIN · lock reservasi lalu payment · cek status submitted
    PV->>DB: payment approved · reservasi Lunas + log · COMMIT
    PV->>N: email voucher ke pelanggan
  else Ditolak
    A->>VC: POST reject (alasan wajib)
    VC->>PV: reject(payment, admin, alasan)
    PV->>DB: payment rejected · rejection_count + 1
    alt Penolakan ke-1 / ke-2
      PV->>DB: status → Menunggu Pembayaran (perpanjang batas bila lewat, BR-20)
      PV->>N: email "bukti ditolak" + alasan
    else Penolakan ke-3
      PV->>DB: batalkan reservasi, hapus room_nights
      PV->>N: email pembatalan
    end
  end
```

---

## 2.6 Class Diagram

Notasi UML 2.5.1 (OMG, 2017).

### 2.6.1 Model Domain

```mermaid
classDiagram
  direction LR
  class User {
    +UserRole role
    +string locale
    +isAdmin() bool
    +preferredLocale() string
  }
  class Branch {
    +string code
    +string slug
    +string bank_account_no
    +bool is_active
  }
  class RoomType {
    +int capacity
    +decimal base_price
    +rateFor(date) array
  }
  class Room {
    +string number
    +RoomStatus status
    +isBookable() bool
    +hasBookingHistory() bool
  }
  class Facility {
    +string name_id
    +string name_en
  }
  class RoomRate {
    +date start_date
    +date end_date
    +decimal price
  }
  class Reservation {
    +string code
    +date check_in
    +date check_out
    +decimal total_amount
    +ReservationStatus status
    +datetime payment_deadline
    +int rejection_count
    +decimal refund_amount
    +transitionTo(status, actor, note)
    +computeDeadline(created, checkIn)$
    +generateCode(branch, date)$
  }
  class ReservationRoom {
    +int guests
    +json rate_breakdown
    +decimal line_total
  }
  class RoomNight {
    +date stay_date
  }
  class Payment {
    +PaymentMethod method
    +PaymentStatus status
    +string gateway_ref
    +string proof_path
  }
  class ReservationStatusLog {
    +string from_status
    +string to_status
    +string note
  }

  User "1" --> "0..*" Reservation : membuat
  Branch "1" *-- "0..*" RoomType
  RoomType "1" *-- "0..*" Room
  RoomType "0..*" -- "0..*" Facility
  RoomType "1" *-- "0..*" RoomRate
  Branch "1" --> "0..*" Reservation
  Reservation "1" *-- "1..*" ReservationRoom
  ReservationRoom "0..*" --> "1" Room
  ReservationRoom "1" *-- "1..*" RoomNight
  Room "1" --> "0..*" RoomNight
  Reservation "1" --> "0..*" Payment
  Reservation "1" *-- "0..*" ReservationStatusLog
```

### 2.6.2 Lapisan Layanan

```mermaid
classDiagram
  direction LR
  class ReservationService {
    +create(user, data) Reservation
    +cancel(reservation, actor, reason) Reservation
    +expireOverdue() int
    +checkIn(reservation, admin)
    +checkOut(reservation, admin)
    +tryReallocate(reservation) bool
    +releaseInventory(reservation)
  }
  class AvailabilityService {
    +freeRooms(typeId, in, out) Builder
    +availableCount(type, in, out) int
    +searchByBranch(branch, in, out, guests, rooms) Collection
    +alternativeBranches(...) Collection
  }
  class PricingService {
    +quote(type, in, out, rooms) PriceBreakdown
  }
  class PaymentService {
    +startGateway(reservation) Payment
    +handleCallback(payload) Payment
    +submitProof(reservation, file, data, user) Payment
    +signature(ref, amount, result)$ string
  }
  class PaymentVerificationService {
    +approve(payment, admin)
    +reject(payment, admin, reason)
  }
  class CancellationPolicy {
    +refundPercentage(reservation) int
    +refundAmount(reservation) float
  }
  class ReportService {
    +occupancy(from, to, branch) array
    +revenue(from, to, branch, groupBy) Collection
    +reservationCounts(...) Collection
    +topRoomTypes(...) Collection
    +dashboard(branch) array
  }
  ReservationService ..> AvailabilityService
  ReservationService ..> PricingService
  ReservationService ..> CancellationPolicy
  PaymentService ..> ReservationService
  PaymentVerificationService ..> ReservationService
```

## Referensi

- Cockburn, A. (2001). *Writing effective use cases*. Addison-Wesley.
- Object Management Group. (2013). *Business Process Model and Notation (BPMN) version 2.0.2*. https://www.omg.org/spec/BPMN/2.0.2/
- Object Management Group. (2017). *OMG Unified Modeling Language (OMG UML) version 2.5.1*. https://www.omg.org/spec/UML/2.5.1/

---

## 🇬🇧 English Version

The diagrams above are shared by both versions. Code identifiers are in English, and the labels translate as in the glossary below.

| Indonesian | English | Indonesian | English |
|---|---|---|---|
| Pelanggan | Customer | Menunggu Pembayaran | Awaiting Payment |
| Resepsionis Cabang | Branch Receptionist | Menunggu Verifikasi | Awaiting Verification |
| Manajemen Kantor Pusat | Head Office | Lunas | Paid |
| Mulai / Selesai | Start / End | Selesai (status) | Completed |
| Mencari kamar | Search rooms | Dibatalkan | Cancelled |
| Memesan kamar | Book room | Kedaluwarsa | Expired |
| Membayar | Pay | Batas bayar | Payment deadline |
| Bukti transfer | Transfer receipt | Penolakan ke-3 | 3rd rejection |
| Kunci baris cabang | Lock branch row | Kamar baru saja habis | Room just sold out |

**BPMN.**
- **As-is:** a phone/WhatsApp process with five pain points: ledger checks, manual recording, receipt matching, chasing payments, and manual report consolidation.
- **To-be:** online search, locked transactional booking, gateway or transfer payment with an admin verification queue, and a scheduler that expires unpaid bookings every 5 minutes.

**Use cases.** 24 use cases with «include»/«extend» relations, traced to the FRs (table in 2.2).

**Key scenarios (summary):**
- **UC-05 Search:** validate dates → count free rooms per type → show results with "n left", dimming types that do not fit, and suggesting other branches when none are available.
- **UC-07 Book:** checkout → Form Request validation → transaction locking the branch row → re-check free rooms → save booking, rate snapshot, and `room_nights` → commit → queue email → redirect to payment. If rooms are gone (or the unique index fires), roll back and return to search.
- **UC-09 Pay:**
  - gateway: MockPay sends a callback with an HMAC signature, verified for signature and amount → Paid;
  - transfer: receipt stored privately → Awaiting Verification;
  - a late success callback after expiry triggers re-allocation, or a full refund flag.
- **UC-13 Cancel:** policy and status check → refund estimate → confirm (reason required for admins) → one transaction that records the refund, cancels hanging payments, releases rooms, and logs the change.
- **UC-15 Verify:** oldest-first queue → checklist → approve (Paid), or reject with a reason (back to Awaiting Payment, with the deadline extended per BR-20). The 3rd rejection cancels, and double processing is prevented by row locks.

**Activity diagrams.** AD-01 search & booking, AD-02 payment, and AD-03 cancellation with refund tiers. The **state diagram** is implemented in `ReservationStatus::allowedTransitions()`.

**Sequence diagrams.** SD-01 shows the three-layer booking transaction. SD-02 shows receipt upload, then admin approval or rejection.

**Class diagrams.** The domain model has 12 Eloquent models, including the `RoomNight` inventory ledger. The service layer has 7 services and thin controllers.
