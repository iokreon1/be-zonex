# 03 — Court Availability & Real-Time Schedule API

**What to build:**
Endpoint API publik bagi customer untuk mencari dan melihat jadwal ketersediaan slot kosong per lapangan olahraga secara real-time pada tanggal tertentu sebelum melakukan checkout pemesanan.

**Blocked by:** 02 — Venue & Court CRUD with Operating Hours

**Status:** ready-for-agent

- [ ] Endpoint API `GET /api/courts/{id}/availability?date=YYYY-MM-DD` untuk memeriksa jadwal kosong pada lapangan tertentu.
- [ ] Logika filter API untuk memvalidasi apakah tanggal pemesanan bertepatan dengan hari libur venue (`is_closed = true` pada tabel `venue_operating_hours`).
- [ ] Logika filter API untuk menghasilkan daftar slot waktu per jam (misalnya pukul 08:00 - 22:00) yang didasarkan pada jam buka-tutup venue pada hari tersebut.
- [ ] Logika pemetaan slot waktu untuk menandai status ketersediaan (misal: `is_booked: true/false`) dengan memeriksa data booking aktif berstatus lunas ("Paid"/"Confirmed") atau sedang pending dalam batas pembayaran 15 menit pada hari dan lapangan yang sama.
- [ ] Pengujian API menggunakan PHPUnit/Postman untuk menguji ketersediaan slot di hari kerja biasa, hari libur tutup venue, dan verifikasi slot yang sudah terisi berubah status menjadi ter-booking.
