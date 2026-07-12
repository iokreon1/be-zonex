# PRD — Zonex (Sport Venue Booking Platform)

## 1. Executive Summary

- **Problem Statement**: Proses pemesanan lapangan olahraga yang manual via WhatsApp saat ini tidak efisien, memakan waktu bagi pelanggan, rentan terhadap kesalahan pencatatan (*double booking*) oleh admin venue, serta menyulitkan rekapitulasi keuangan secara terpusat.
- **Proposed Solution**: Platform SaaS terpusat (*mobile-first*) berbasis Laravel API dan Vue.js SPA yang mengotomatisasi pemesanan lapangan secara real-time, mengintegrasikan pembayaran digital instan melalui Midtrans Snap, serta menyediakan sistem saldo virtual (*virtual balance*) dan penarikan dana (*withdrawal*) bagi pemilik venue.
- **Success Criteria**:
  - **0 Kasus Double Booking**: Tercapai melalui mekanisme validasi irisan waktu di tingkat database transaksi menggunakan *pessimistic locking* (`lockForUpdate()`).
  - **Waktu Booking Mandiri < 3 Menit**: Dari pencarian venue, pemilihan slot jadwal kosong, hingga pembayaran sukses via Midtrans Snap.
  - **Validasi Pembayaran Instan**: Otomatisasi pembaruan status booking dari "Pending" menjadi "Paid" dalam waktu < 2 detik setelah menerima notifikasi webhook dari Midtrans.
  - **Laporan Keuangan Real-Time**: Dasbor pendapatan dan saldo virtual pemilik venue diperbarui secara instan sesaat setelah pembayaran divalidasi.

---

## 2. User Experience & Functionality

### User Personas
1. **Customer (Penyewa Lapangan)**: Pengguna *mobile-first* yang mencari jadwal lapangan kosong secara transparan dan melakukan pembayaran instan secara mandiri.
2. **Venue Admin (Pemilik/Staf Lapangan)**: Pengelola operasional harian tempat olahraga yang memantau jadwal pemesanan masuk, mengatur profil lapangan, dan menarik dana pendapatan bersih.
3. **Zonex Admin (Super Admin)**: Pengelola platform Zonex yang memverifikasi pengajuan kemitraan venue, memantau transaksi keseluruhan, dan memproses penarikan dana fisik ke rekening pemilik venue.

### User Stories & Acceptance Criteria

#### Story 1: Pendaftaran Akun Wajib (Mandatory Registration)
*As a customer, I want to register and login with my email and phone number so that my bookings and reviews can be securely tracked.*
- **Acceptance Criteria**:
  - Customer wajib memiliki akun aktif untuk melakukan booking lapangan (tidak ada *guest checkout* guna mempermudah pelacakan riwayat dan verifikasi ulasan).
  - Pendaftaran memerlukan nama lengkap, email unik, nomor telepon aktif, dan kata sandi.
  - Otentikasi menggunakan Laravel Sanctum dengan token yang disimpan dengan aman di sisi klien.

#### Story 2: Pemesanan Lapangan Real-Time
*As a customer, I want to see real-time court availability and book a slot so that I can secure my playing time instantly.*
- **Acceptance Criteria**:
  - Menampilkan kalender ketersediaan slot kosong per lapangan secara dinamis berdasarkan jam operasional venue.
  - Sistem melakukan validasi irisan waktu sebelum checkout untuk mencegah tumpang tindih jadwal.
  - Sistem menggunakan model *Direct Checkout* (satu pemesanan untuk satu slot lapangan per transaksi).
  - Setelah pesanan dibuat, slot waktu dikunci selama 15 menit dengan status pembayaran "Unpaid". Jika melewati batas waktu tersebut, booking dibatalkan otomatis oleh sistem (*cron job*) dan slot dilepas kembali.

#### Story 3: Pembayaran Digital Instan
*As a customer, I want to pay using modern digital payment methods so that my booking is immediately confirmed.*
- **Acceptance Criteria**:
  - Pembayaran terintegrasi dengan Midtrans Snap (QRIS, Virtual Account, dan E-Wallet).
  - Setelah menerima webhook pembayaran sukses dari Midtrans, sistem otomatis mengubah status pembayaran menjadi "Paid" dan status booking menjadi "Confirmed".
  - Uang pembayaran masuk terpusat ke akun Midtrans milik platform Zonex terlebih dahulu sebelum didistribusikan ke saldo virtual venue.

#### Story 4: Kebijakan Pembatalan & Refund MVP (Manual/Offline Only)
*As a customer/venue admin, I want to know the cancellation policy so that any disputes can be handled fairly.*
- **Acceptance Criteria**:
  - Di tingkat aplikasi untuk versi MVP, seluruh booking yang telah berstatus "Paid" bersifat **non-refundable (tidak dapat di-refund secara otomatis oleh sistem)**.
  - Jika pelanggan ingin mengajukan pembatalan atau perubahan jadwal (*reschedule*), mereka harus menghubungi pihak Venue Admin secara manual (offline) menggunakan kontak WhatsApp yang tertera pada detail profil venue.
  - Venue Admin dapat mengubah status booking menjadi "Cancelled" secara manual melalui dasbor mereka jika kesepakatan offline tercapai.

#### Story 5: Manajemen Operasional Venue
*As a venue admin, I want to manage my court details, photos, and operating hours so that customers see accurate information.*
- **Acceptance Criteria**:
  - Admin dapat memperbarui nama, alamat, kota, titik koordinat, jam buka-tutup, dan rekening bank venue.
  - Pengaturan hari operasional menggunakan format angka (`0` untuk Minggu hingga `6` untuk Sabtu) dengan opsi menandai hari libur (*is_closed*).
  - Admin dapat menambahkan banyak foto galeri untuk setiap lapangan, dengan menandai satu gambar utama (*is_primary*).

#### Story 6: Saldo Virtual & Penarikan Dana (Withdrawal)
*As a venue admin, I want to see my virtual balance and request withdrawals so that I can receive my revenue.*
- **Acceptance Criteria**:
  - Saldo virtual dihitung berdasarkan akumulasi booking lunas ("Paid").
  - Admin dapat mengajukan penarikan dana (*request withdrawal*) dengan menentukan nominal penarikan.
  - Pemotongan komisi platform Zonex (berdasarkan `commission_rate` milik venue) dilakukan pada saat pengajuan withdrawal disetujui/selesai, bukan di setiap transaksi booking lunas.
  - Contoh: Jika venue menarik Rp1.000.000 dengan tarif komisi 10%, saldo virtual akan berkurang sebesar Rp1.000.000, tetapi dana bersih yang ditransfer oleh Super Admin ke rekening bank venue adalah Rp900.000 (setelah dipotong komisi Rp100.000).

#### Story 7: Pemrosesan Keuangan oleh Super Admin
*As a Super Admin, I want to review withdrawal requests and mark them as completed after physical transfer so that the venue owner receives their money.*
- **Acceptance Criteria**:
  - Super Admin dapat melihat seluruh daftar pengajuan withdrawal berstatus "Pending".
  - Setelah melakukan transfer dana fisik secara manual ke rekening bank venue, Super Admin wajib mengunggah foto bukti transfer (`proof_of_transfer`) di sistem.
  - Mengubah status penarikan menjadi "Completed" secara otomatis akan mengurangi saldo virtual milik venue.

#### Story 8: Ulasan Terverifikasi (Verified Reviews)
*As a customer, I want to write a review with photos for a court I rented so that other users can see authentic feedback.*
- **Acceptance Criteria**:
  - Pengguna hanya dapat memberikan ulasan jika pemesanan berstatus lunas ("Paid"/"Completed") dan waktu bermain telah lewat.
  - Dibatasi maksimal 1 ulasan (skala bintang 1-5, komentar teks, dan unggahan foto opsional) per transaksi booking.

### Non-Goals (Fase MVP)
- Sistem keranjang belanja (*multi-booking* beberapa slot dalam satu transaksi).
- Aplikasi mobile native (iOS/Android) — difokuskan pada web app responsif.
- Fitur *split payment* langsung ke rekening bank mitra secara otomatis di tingkat payment gateway.
- Fitur obrolan langsung (*in-app chat*) antara customer dan venue.

---

## 3. AI System Requirements

- **N/A (Tidak Berlaku)**: Platform Zonex tidak menggunakan komponen berbasis AI pada fase MVP.

---

## 4. Technical Specifications

### Architecture Overview
- **Decoupled Architecture**: Laravel sebagai REST API backend murni dan Vue.js sebagai Single Page Application (SPA) frontend.
- **Validasi Double-Booking**: Menggunakan kombinasi database transaction dan *pessimistic locking* (`lockForUpdate()`) di level data akses (Repository). Kombinasi `court_id + booking_date + start_time` sengaja **tidak** dijadikan unique constraint di database karena tidak mampu menahan irisan waktu yang tumpang tindih sebagian (misal: 10.00-11.00 vs 10.30-11.30). Validasi dilakukan di aplikasi menggunakan rumus:
  `(start_time_new < end_time_old) AND (end_time_new > start_time_old)`

### Integration Points
- **Midtrans Snap API & Webhooks**: Menggunakan Snap API untuk membuat transaksi pembayaran digital dan memproses notifikasi webhook pembayaran lunas atau kedaluwarsa secara asinkron.
- **Laravel Sanctum Authentication**: Digunakan untuk pengamanan API dengan autentikasi berbasis token bagi seluruh role (Super Admin, Venue Owner/Staff, Customer).

### Security & Privacy
- **IDOR Protection**: Validasi otorisasi wajib dilakukan di setiap endpoint manipulasi data (seperti pembatalan booking atau pengunggahan ulasan). ID pemilik data wajib divalidasi langsung melalui token otentikasi (`auth()->id()`), bukan mempercayai parameter `user_id` dari payload request.
- **Eloquent Update Safety**: Pembaruan untuk kolom krusial (seperti status pembayaran, status penarikan dana, dan kata sandi) wajib menargetkan instansi model Eloquent secara spesifik untuk memicu event model, menghindari penggunaan massal query builder yang berisiko menimpa kolom tak terduga.
- **Dynamic File Management**: Logika unggah file (foto profil, lapangan, ulasan, bukti transfer) wajib menggunakan penamaan folder dinamis (`$folderName`) untuk pemisahan penyimpanan yang rapi.

---

## 5. Database Schema (Reference)

```dbml
Enum user_role {
  super_admin
  venue_owner
  customer
}

Enum venue_user_role {
  owner
  staff
}

Enum venue_status {
  active
  pending
  suspended
}

Enum court_category {
  badminton
  futsal
  tennis
  basketball
  volleyball
}

Enum court_status {
  active
  inactive
}

Enum booking_status {
  pending
  confirmed
  completed
  cancelled
}

Enum payment_status {
  unpaid
  paid
  refunded
  expired
}

Enum withdrawal_status {
  pending
  approved
  rejected
  completed
}

Table users {
  id bigint [pk, increment]
  name varchar
  email varchar [unique]
  phone varchar
  password varchar
  role user_role
  email_verified_at timestamp
  created_at timestamp
  updated_at timestamp
}

Table venue_user {
  user_id bigint [ref: > users.id]
  venue_id bigint [ref: > venues.id]
  role venue_user_role

  indexes {
    (user_id, venue_id) [pk]
  }
}

Table venues {
  id bigint [pk, increment]
  name varchar
  slug varchar [unique]
  address text
  city varchar
  latitude decimal
  longitude decimal
  featured_image varchar
  bank_account varchar
  commission_rate decimal
  status venue_status
  created_at timestamp
  updated_at timestamp
}

Table venue_operating_hours {
  id bigint [pk, increment]
  venue_id bigint [ref: > venues.id]
  day_of_week tinyint [note: '0=Minggu ... 6=Sabtu']
  open_time time
  close_time time
  is_closed boolean
}

Table courts {
  id bigint [pk, increment]
  venue_id bigint [ref: > venues.id]
  name varchar
  category court_category
  price_per_hour decimal
  status court_status
  created_at timestamp
  updated_at timestamp
}

Table court_images {
  id bigint [pk, increment]
  court_id bigint [ref: > courts.id]
  image_path varchar
  is_primary boolean
}

Table bookings {
  id bigint [pk, increment]
  booking_code varchar [unique]
  venue_id bigint [ref: > venues.id]
  court_id bigint [ref: > courts.id]
  user_id bigint [ref: > users.id]
  booking_date date
  start_time time
  end_time time
  total_price decimal
  status booking_status
  payment_status payment_status
  midtrans_order_id varchar
  expires_at timestamp [note: 'batas waktu pembayaran (15 menit), dipakai oleh Auto-Cancel Cron Job']
  created_at timestamp
  updated_at timestamp
}

Table withdrawals {
  id bigint [pk, increment]
  venue_id bigint [ref: > venues.id]
  requested_by bigint [ref: > users.id]
  amount decimal
  status withdrawal_status
  proof_of_transfer varchar
  created_at timestamp
  updated_at timestamp
}

Table reviews {
  id bigint [pk, increment]
  booking_id bigint [ref: - bookings.id]
  venue_id bigint [ref: > venues.id]
  user_id bigint [ref: > users.id]
  rating tinyint
  comment text
  created_at timestamp
}

Table review_images {
  id bigint [pk, increment]
  review_id bigint [ref: > reviews.id]
  image_path varchar
}
```

---

## 6. Risks & Roadmap

### Phased Rollout
- **Fase 1 (MVP - v1.0)**: Rilis booking lapangan real-time, integrasi Midtrans Snap, sistem withdrawal pendapatan dengan pemotongan komisi platform, dan ulasan terverifikasi.
- **Fase 2 (v2.0)**: Peluncuran **Sistem Membership** di mana pelanggan dapat mendaftar program keanggotaan pada venue tertentu untuk mendapatkan diskon khusus, poin loyalitas, atau akses pemesanan prioritas.

### Technical Risks
- **Database Lock Contention (Deadlock/Latency)**:
  - *Risiko*: Penggunaan `lockForUpdate()` dapat memicu antrean transaksi yang lama (*lock wait timeout*) jika puluhan pengguna mencoba memesan slot yang sama pada detik yang sama di suatu lapangan populer.
  - *Mitigasi*: Menambahkan indeks gabungan pada `(court_id, booking_date)` di tabel bookings untuk mempercepat pencarian baris yang akan dikunci, serta membatasi waktu tunggu kunci (*lock timeout*) di konfigurasi database MySQL.
- **Midtrans Webhook Failure / Asynchronous Desync**:
  - *Risiko*: Gangguan jaringan pada server Midtrans atau downtime server aplikasi Zonex dapat menyebabkan webhook pembayaran tidak diterima, sehingga pemesanan tetap bertatus "Unpaid" meskipun pelanggan sudah mentransfer dana.
  - *Mitigasi*: Menyediakan tombol "Cek Status Pembayaran" manual pada halaman pemesanan pelanggan yang akan memicu API call ke *Midtrans Status API*, ditambah cron job cadangan yang mengecek transaksi pending setiap 5 menit ke Midtrans untuk melakukan rekonsiliasi otomatis.

---

## 7. Notes for AI Coding Agent

- Gunakan **Repository Pattern** untuk memisahkan logika query database dari logika bisnis di Controller (Model $\rightarrow$ Repository $\rightarrow$ Controller).
- Pastikan setiap atribut `$fillable` didefinisikan secara eksplisit di model Eloquent; dilarang menggunakan `$guarded = []`.
- Semua logika unggah gambar wajib mendukung parameter dinamis `$folderName` demi modularitas dan fleksibilitas penyimpanan.
