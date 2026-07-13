# 06 — Auto-Cancel Expired Payments Cron Job

**What to build:**
Sistem otomatisasi backend (*scheduled command*) untuk mendeteksi dan membatalkan pesanan yang belum dibayar dalam batas waktu 15 menit sejak pemesanan dibuat, guna membebaskan slot lapangan agar dapat dipesan kembali oleh pelanggan lain.

**Blocked by:** 05 — Midtrans Snap Integration & Webhook Handler

**Status:** ready-for-agent

- [ ] Pembuatan Custom Artisan Command baru di Laravel (misal: `php artisan booking:cancel-expired`).
- [ ] Logika query command untuk mengambil semua booking dengan status `booking_status = pending`, `payment_status = unpaid`, dan nilai kolom `expires_at` lebih kecil dari waktu sekarang (`now()`).
- [ ] Logika pembaruan status: membatalkan booking tersebut dengan mengubah status booking menjadi `cancelled` dan status pembayaran menjadi `expired`.
- [ ] Daftarkan command tersebut ke dalam penjadwalan Laravel (`routes/console.php` atau `app/Console/Kernel.php`) agar berjalan otomatis setiap menit (`everyMinute()`).
- [ ] Pengujian command menggunakan PHPUnit untuk memvalidasi bahwa booking yang telah melewati waktu 15 menit berhasil dibatalkan secara otomatis saat command dijalankan, sedangkan booking yang masih dalam durasi 15 menit tetap aktif.
