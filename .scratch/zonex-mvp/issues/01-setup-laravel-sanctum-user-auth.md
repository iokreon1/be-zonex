# 01 — Setup Laravel Sanctum & User Authentication

**What to build:**
Sistem registrasi dan otentikasi pengguna berbasis token API menggunakan Laravel Sanctum. Customer, Venue Admin, dan Super Admin dapat melakukan pendaftaran, login, dan mengakses data profil mereka yang diamankan oleh Sanctum token middleware.

**Blocked by:** None — can start immediately

**Status:** ready-for-agent

- [ ] Database migration untuk tabel `users` mendukung kolom role (`super_admin`, `venue_owner`, `customer`), email unik, nama, nomor telepon, password hash, dan email_verified_at.
- [ ] Model `User` terkonfigurasi dengan trait `HasApiTokens` dan `$fillable` yang dieksplisitkan secara aman (tidak menggunakan `$guarded = []`).
- [ ] Endpoint API `POST /api/register` untuk pendaftaran akun baru dengan validasi data input (nama, email unik, nomor telepon, password minimal 8 karakter).
- [ ] Endpoint API `POST /api/login` yang memvalidasi kredensial (email & password) dan mengembalikan token otentikasi Sanctum beserta objek user dan role-nya.
- [ ] Endpoint API `POST /api/logout` yang mencabut/menghapus token otentikasi pengguna saat ini.
- [ ] Endpoint API `GET /api/me` yang dilindungi oleh middleware `auth:sanctum` untuk mengembalikan profil pengguna yang sedang login saat ini.
- [ ] Pengujian fungsionalitas (Feature Test) menggunakan PHPUnit/Postman untuk memvalidasi alur registrasi, login dengan kredensial valid/invalid, logout, dan pengaksesan profil terproteksi.
