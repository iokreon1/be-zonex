# 05 — Midtrans Snap Integration & Webhook Handler

**What to build:**
Integrasi payment gateway Midtrans Snap untuk memproses pembayaran digital secara real-time dan menangani callback webhook pembayaran lunas untuk otomatis memperbarui status booking menjadi "Paid" / "Confirmed".

**Blocked by:** 04 — Secure Court Booking & Concurrency Validation

**Status:** ready-for-agent

- [ ] Konfigurasi SDK Midtrans di Laravel menggunakan credential sandbox (Client Key, Server Key) yang diambil dari berkas `.env`.
- [ ] Endpoint API `POST /api/bookings/{id}/payment-token` bagi Customer untuk mendapatkan token pembayaran Midtrans Snap (`midtrans_order_id` dan token transaksi Snap) untuk transaksi booking berstatus "Pending".
- [ ] Endpoint API Webhook `POST /api/midtrans/webhook` untuk menerima notifikasi pembayaran asinkron dari Midtrans.
- [ ] Logika validasi signature key Midtrans webhook untuk memastikan keaslian payload notification yang dikirimkan oleh server Midtrans.
- [ ] Logika penanganan status webhook:
  - Jika pembayaran sukses (`settlement` / `capture`), perbarui `payment_status` booking menjadi `paid` dan `status` booking menjadi `confirmed`.
  - Jika pembayaran kedaluwarsa/gagal (`expire` / `cancel` / `deny`), perbarui `payment_status` menjadi `expired`/`failed` dan `status` booking menjadi `cancelled`.
- [ ] Keamanan model Eloquent: Pembaruan status pembayaran wajib menargetkan instansi model Eloquent secara spesifik (`$booking->update()`) untuk memicu event model, bukan query builder massal.
- [ ] Pengujian API menggunakan PHPUnit/Postman dengan mensimulasikan payload webhook sukses/kedaluwarsa dari Midtrans dan memverifikasi perubahan status booking di database.
