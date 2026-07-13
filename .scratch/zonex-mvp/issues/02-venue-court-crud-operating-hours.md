# 02 — Venue & Court CRUD with Operating Hours

**What to build:**
Sistem manajemen data operasional bagi Venue Admin untuk mengelola profil bisnis tempat olahraga mereka (nama, alamat, rekening bank), jam buka-tutup mingguan, serta data lapangan olahraga beserta foto galeri lapangan.

**Blocked by:** 01 — Setup Laravel Sanctum & User Authentication

**Status:** ready-for-agent

- [ ] Database migration untuk tabel `venues`, `venue_operating_hours`, `courts`, `court_images`, dan tabel relasi `venue_user` sesuai rancangan DBML di PRD.
- [ ] Model `Venue`, `VenueOperatingHour`, `Court`, dan `CourtImage` terkonfigurasi dengan relasi Eloquent yang tepat (misal: Venue hasMany Court).
- [ ] Endpoint API untuk CRUD Venue (`GET`, `POST`, `PUT` `/api/venues`) yang dilindungi middleware otentikasi Sanctum dan otorisasi khusus role `venue_owner`.
- [ ] Endpoint API untuk mengelola jam operasional (`GET`, `PUT` `/api/venues/{id}/operating-hours`), di mana hari disimpan dalam format angka `0` (Minggu) s.d `6` (Sabtu), serta validasi logika jam buka tidak boleh lebih besar dari jam tutup.
- [ ] Endpoint API untuk CRUD Lapangan (`GET`, `POST`, `PUT`, `DELETE` `/api/courts`) di bawah kelolaan venue.
- [ ] Fitur unggah gambar dinamis menggunakan parameter `$folderName` yang fleksibel untuk menyimpan galeri foto lapangan (`court_images`), serta validasi minimal satu foto utama (`is_primary = true`).
- [ ] Proteksi otorisasi (IDOR Protection) untuk memastikan Venue Admin hanya dapat membaca/mengubah data venue dan lapangan milik mereka sendiri berdasarkan token login (`auth()->id()`), bukan dari masukan parameter payload.
- [ ] Pengujian API menggunakan PHPUnit/Postman untuk menguji validasi input, hak akses role, proteksi IDOR, dan pengunggahan file gambar.
