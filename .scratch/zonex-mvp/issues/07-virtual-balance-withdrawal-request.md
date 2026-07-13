# 07 — Virtual Balance & Withdrawal Request

**What to build:**
Layanan pengelolaan keuangan bagi Venue Admin untuk memantau akumulasi saldo virtual dari transaksi sewa yang lunas serta mengajukan permintaan penarikan dana ke rekening bank mereka.

**Blocked by:** 05 — Midtrans Snap Integration & Webhook Handler

**Status:** ready-for-agent

- [ ] Database migration untuk tabel `withdrawals` sesuai rancangan DBML di PRD, termasuk kolom `amount` decimal, status withdrawal (`pending`), dan `proof_of_transfer` varchar (opsional).
- [ ] Endpoint API `GET /api/venues/{id}/balance` bagi Venue Admin untuk melihat total saldo virtual saat ini. Saldo virtual dihitung dari akumulasi pemesanan berstatus lunas (`payment_status = paid`) dikurangi total dana yang berhasil ditarik sebelumnya (`withdrawal_status = completed`).
- [ ] Endpoint API `POST /api/venues/{id}/withdrawals` bagi Venue Admin untuk mengajukan penarikan dana dengan nominal tertentu.
- [ ] Validasi penarikan dana: Nominal penarikan tidak boleh melebihi saldo virtual yang tersedia pada saat pengajuan.
- [ ] Logika proteksi IDOR: Memastikan pengguna yang mengajukan withdrawal terdaftar sebagai pemilik/staf dari venue tersebut (`venue_user` mapping table).
- [ ] Pengujian API menggunakan PHPUnit/Postman untuk menguji penghitungan saldo virtual secara akurat, pengajuan nominal valid/tidak valid, serta validasi hak akses venue.
