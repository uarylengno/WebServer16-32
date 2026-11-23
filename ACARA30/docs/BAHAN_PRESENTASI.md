# Acara 30 — Tugas Mandiri 4: Bahan Presentasi Akhir (±10 menit)

Alur: masalah → arsitektur → keamanan → demo → bukti pengujian → pelajaran. Tiap bagian ±1-2 menit.

## 1. Pembuka (1 menit)
- Sistem Informasi Akademik berbasis PHP Native MVC: API REST untuk data mahasiswa dan buku, View Bootstrap, autentikasi JWT, dideploy ke cPanel.
- Alur kerja: 14 acara dari fondasi MVC (acara 17) sampai deployment produksi (acara 30). Peta acara: `docs/STRUKTUR_PROYEK.md` bagian 4.

## 2. Arsitektur MVC (2 menit)
- Tampilkan diagram folder dan alur request (`STRUKTUR_PROYEK.md` bagian 1-2).
- Poin: Front Controller satu pintu (`public/index.php`); Router memetakan URL + method; Controller mengatur request/response; Model satu-satunya yang menyentuh database (PDO prepared statement); `app/` di luar document root.
- Respons JSON seragam `{success, message, data, timestamp}` dan status code yang tepat (200, 201, 400, 401, 403, 404, 409, 422, 500).

## 3. Alur autentikasi dan hak akses (2 menit)
- Diagram `STRUKTUR_PROYEK.md` bagian 3: login → JWT HS256 (5 menit) → header Bearer → validasi di server.
- RBAC: admin boleh tambah/ubah/hapus, user hanya baca. Role dibaca dari token, bukan dari localStorage (yang dapat diubah pengguna).
- Demo singkat: login user_demo, coba hapus lewat Postman → 403.

## 4. Keamanan berlapis (2 menit)
| Ancaman | Penangkal | Bukti |
|---------|-----------|-------|
| Pemalsuan token | Kunci JWT khusus server (`secret.local.php`) | token berkunci bawaan → 401 (`cek_produksi.php`) |
| SQL injection | PDO prepared statement | test case Acara 23 |
| XSS | `textContent`, `escapeHTML` | nama `<b>Coba</b>` tampil sebagai teks |
| Unggah berbahaya | cek `exif_imagetype`, 2MB, nama acak, `uploads/.htaccess` | test case upload Acara 26 |
| Akses berkas internal | `app/` di luar `public_html`, `app/.htaccess` | `/app/config/Database.php` → 404/403 |
| Penyadapan | HTTPS + pengalihan 301 | `http://` dialihkan |
| Kebocoran error | `display_errors` Off, `log_errors` On | `POST /login` rusak → pesan umum, rincian di log |

## 5. Demo langsung (2 menit)
Login admin → dashboard (loading state) → tambah data dengan foto → ubah → hapus (modal) → nama berisi HTML tampil sebagai teks → matikan jaringan (DevTools Offline) sebentar: pesan "mencoba lagi".

## 6. Bukti pengujian (1 menit)
- Acara 23: 26 test case Pass (Postman); uji ulang di produksi (`TEST_CASE_PRODUKSI.md`).
- Uji otomatis View di browser: 79 pemeriksaan Pass (`docs/acara29/TEST_CASE_ACARA29.md`).
- Pemeriksaan keamanan produksi: `php scripts/cek_produksi.php https://DOMAIN`.
- Disaster recovery: tabel dihapus lalu dipulihkan, jumlah baris dan checksum sama (`DISASTER_RECOVERY.md`).

## 7. Kendala dan pelajaran (1 menit)
- Folder upload tertulis di luar document root saat struktur hosting berbeda dari lokal.
- Pola `FilesMatch` yang salah menulis dotfile; ditemukan oleh pemeriksaan otomatis.
- Pemulihan satu tabel menggeser `created_at` bila `TIME_ZONE` dump tidak ikut.
- Pelajaran: uji di lingkungan yang menyerupai produksi, otomatiskan pemeriksaan, dan uji restore sebelum bencana terjadi.

## Perkiraan pertanyaan
- *Mengapa JWT kedaluwarsa 5 menit dan tidak ada refresh token?* Batasan cakupan workshop; token tidak dapat dicabut sebelum habis (tanpa blacklist). Perbaikan: refresh token atau sesi di server.
- *Mengapa role di localStorage bila bisa diubah?* Hanya untuk menyembunyikan tombol; server memutuskan lewat token.
- *Mengapa POST tambah tidak diulang otomatis?* Tidak idempoten: bisa menggandakan data bila balasan hilang.
- *Apa risiko pengalihan HTTPS bila SSL belum terbit?* Situs tidak dapat dibuka; aktifkan setelah SSL valid.
