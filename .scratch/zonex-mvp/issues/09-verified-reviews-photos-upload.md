# 09 — Verified Reviews & Photos Upload

**What to build:**
Sistem ulasan terverifikasi (Verified Reviews) bagi Customer untuk memberikan rating (1-5), komentar ulasan, dan mengunggah foto lapangan setelah waktu bermain sewa lapangan selesai dilakukan.

**Blocked by:** 05 — Midtrans Snap Integration & Webhook Handler

**Status:** ready-for-agent

- [ ] Database migration untuk tabel `reviews` dan `review_images` sesuai rancangan DBML di PRD, termasuk kolom `rating` tinyint, `comment` text, dan relasi ke tabel `bookings`, `venues`, dan `users`.
- [ ] Endpoint API `POST /api/bookings/{id}/reviews` bagi Customer terautentikasi untuk mengirim ulasan.
- [ ] Validasi keaslian ulasan:
  - Verifikasi bahwa user yang login adalah user yang melakukan booking tersebut (`user_id` cocok dengan token otentikasi, proteksi IDOR).
  - Booking wajib berstatus lunas (`payment_status = paid` dan `status = confirmed` atau `completed`).
  - Waktu bermain sewa lapangan (`booking_date` dan `end_time`) wajib sudah berlalu dari waktu sekarang.
  - Memastikan customer hanya dapat mengirim ulasan maksimal 1 kali per transaksi booking.
- [ ] Validasi input berupa `rating` (angka bulat 1 s.d 5), `comment` (teks komentar opsional), dan unggah file foto ulasan (opsional).
- [ ] Logika upload file foto ulasan menggunakan dynamic upload service dengan `$folderName` dinamis (misalnya `reviews`).
- [ ] Pengujian API menggunakan PHPUnit/Postman untuk menguji pembatasan pengiriman review jika belum bermain, double-review pada booking yang sama, upload foto ulasan, dan proteksi IDOR pemilik booking.
