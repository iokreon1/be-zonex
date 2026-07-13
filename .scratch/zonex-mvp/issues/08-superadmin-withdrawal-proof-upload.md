# 08 — Super Admin Withdrawal Fulfillment & Proof Upload

**What to build:**
Sistem manajemen penarikan dana terpusat bagi Super Admin untuk memproses transfer dana manual ke bank venue, mengunggah bukti transfer, memotong komisi platform secara otomatis, dan memperbarui status penarikan dana menjadi "Completed".

**Blocked by:** 07 — Virtual Balance & Withdrawal Request

**Status:** ready-for-agent

- [ ] Endpoint API `GET /api/admin/withdrawals` bagi Super Admin untuk melihat seluruh daftar pengajuan penarikan dana berstatus `pending`.
- [ ] Endpoint API `POST /api/admin/withdrawals/{id}/complete` bagi Super Admin untuk menyelesaikan penarikan dana.
- [ ] Logika pemrosesan:
  - Super Admin wajib mengunggah file foto bukti transfer fisik (`proof_of_transfer`).
  - Sistem akan memperhitungkan pemotongan komisi platform berdasarkan nilai `commission_rate` milik venue pada database.
  - Jumlah dana bersih yang ditransfer = `amount_withdrawal * (1 - commission_rate)`.
  - Ubah status withdrawal menjadi `completed` dan kurangi virtual balance milik venue.
- [ ] Logika upload file bukti transfer menggunakan dynamic upload service dengan `$folderName` dinamis (misalnya `proofs`).
- [ ] Proteksi otorisasi: Endpoint ini hanya dapat diakses oleh user dengan role `super_admin`.
- [ ] Pengujian API menggunakan PHPUnit/Postman untuk menguji unggahan file bukti transfer, validasi perhitungan pemotongan komisi, hak akses super admin, dan penolakan jika diakses oleh non-admin.
