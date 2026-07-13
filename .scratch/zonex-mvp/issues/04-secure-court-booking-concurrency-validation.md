# 04 — Secure Court Booking & Concurrency Validation

**What to build:**
Sistem checkout pemesanan lapangan oleh customer yang menjamin tidak adanya kasus double booking (bentrok jadwal) meskipun terdapat puluhan request pemesanan yang masuk secara bersamaan untuk slot waktu yang sama.

**Blocked by:** 03 — Court Availability & Real-Time Schedule API

**Status:** ready-for-agent

- [ ] Database migration untuk tabel `bookings` sesuai rancangan DBML di PRD, termasuk kolom `booking_code` unik, `expires_at` (15 menit dari pembuatan), status booking (`pending`), dan status pembayaran (`unpaid`).
- [ ] Endpoint API `POST /api/bookings` untuk membuat pesanan baru yang hanya dapat diakses oleh customer terautentikasi (`auth:sanctum`).
- [ ] Validasi input berupa `court_id`, `booking_date`, `start_time`, dan `end_time` (memastikan sewa dalam kelipatan jam utuh, berada dalam rentang jam operasional venue, dan bukan di masa lalu).
- [ ] Implementasi validasi irisan waktu di service/repository layer menggunakan rumus irisan:
  `(start_time_new < end_time_old) AND (end_time_new > start_time_old)`
- [ ] Implementasi database transaction dan *pessimistic locking* (`lockForUpdate()`) pada baris bookings yang bersangkutan di tanggal tersebut sebelum melakukan penyimpanan record booking baru, guna mencegah race condition.
- [ ] Pembuatan `booking_code` unik secara otomatis (misalnya format string acak atau berbasis timestamp) saat penyimpanan berhasil.
- [ ] Penghitungan otomatis total harga sewa (`total_price`) berdasarkan selisih jam dikali `price_per_hour` lapangan.
- [ ] Pengujian konkurensi (Concurrency Test) menggunakan PHPUnit (fitur parallel testing atau mock concurrency request) untuk mensimulasikan dua booking bertabrakan waktu masuk bersamaan dan memastikan salah satunya berhasil terkunci sedangkan satunya lagi tertolak dengan aman.
