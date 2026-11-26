# Acara 26 – Manajemen Konten, Upload File, dan Database Management

BKPM Workshop SI Web Server (TIF330805). Melanjutkan proyek Acara 17–25 (`sistem-informasi-akademik`).
README Acara 21–25 diarsipkan di `docs/acara21/` sampai `docs/acara25/`.

## Yang berubah dari Acara 25

| Berkas | Perubahan |
|--------|-----------|
| `db_workshop_acara27.sql` | Tabel `mahasiswa` punya kolom `foto VARCHAR(255) DEFAULT NULL` (Langkah 1) |
| `docs/migrasi_acara26.sql` | `ALTER TABLE` saja, untuk database lama yang datanya tidak mau dihapus (jalankan satu kali) |
| `public/uploads/` | **Baru** (Langkah 2). Berisi `.htaccess` (PHP dimatikan, hanya jpg/jpeg/png yang bisa dibuka) dan `.gitkeep` |
| `app/models/Mahasiswa.php` | `create()` menerima `foto`; `update()` mengubah `foto` hanya bila ada foto baru (Langkah 3) |
| `app/controllers/MahasiswaController.php` | Upload di `store()` (Langkah 4) dan `update()`; **foto lama dihapus dengan `unlink()`** (tugas 1); foto ikut dihapus saat data dihapus |
| `app/core/Router.php` | `POST ?id=N&_method=PUT` diperlakukan sebagai `PUT` (PHP tidak mengisi `$_FILES` pada PUT asli) |
| `public/views/dashboard.*`, `common.js`, `styles.css` | Form tambah/ubah memakai `FormData` (Langkah 5), pratinjau foto, kolom Foto di tabel |
| `scripts/` | `backup_db.bat/.sh` dan `restore_db.bat/.sh` (database + folder uploads) |
| `docs/BACKUP_RESTORE.md` | Langkah 6 (phpMyAdmin) dan **jadwal backup tiap akhir sesi** (tugas 2) |
| `docs/TEST_CASE_ACARA26.md` | 93 pengujian (backend, frontend) |
| `contoh_uji/` | Berkas gambar untuk menguji upload (valid, PNG baru, skrip palsu bernama .jpg, pembuat foto > 2MB) |
| Postman | Folder baru "4. Upload Foto (Acara 26)", 14 request form-data |
| `docs/openapi.yaml`, `API_DOCUMENTATION.md` | Bagian multipart, field `foto`, bagian 5.4 |

## Menjalankan

1. Taruh proyek di folder web server (`C:\laragon\www\ACARA16-25\ACARA26`). Pastikan `AllowOverride All` aktif
   (di Laragon default-nya `None`, ubah di `httpd.conf`, lalu restart Apache).
2. **Import ulang `db_workshop_acara27.sql`** (menghapus data lama). Jika data lama mau dipertahankan, jalankan `docs/migrasi_acara26.sql` saja.
3. Buka `http://localhost/ACARA16-25/ACARA27/public/views/login.html`, masuk sebagai `admin` / `Admin@12345`.
4. Tambah mahasiswa dengan memilih foto; thumbnail tampil di tabel.

## Mengubah data sekaligus mengganti foto

Gunakan **`POST /mahasiswa?id=N&_method=PUT`** dengan form-data. `PUT` asli dengan form-data tidak membawa berkas di PHP
(`$_POST` dan `$_FILES` kosong), jadi dijawab `400` berisi petunjuk. Ubah data tanpa foto tetap bisa dengan `PUT` JSON.

## Keamanan upload (ringkas)

- Ekstensi dibatasi `jpg/jpeg/png` **dan** isi berkas diperiksa `exif_imagetype()`; `$_FILES['type']` tidak dipercaya.
- Maksimal 2MB; request yang melebihi `post_max_size` dijawab `422`, bukan error kosong.
- Nama diganti `uniqid('foto_', true)` + ekstensi sesuai isi berkas; berkas dipindahkan setelah semua validasi lolos,
  dan dibersihkan lagi bila penyimpanan database gagal.
- `is_uploaded_file()`, `.htaccess` di `uploads/`, dan `unlink()` hanya untuk nama berkas berpola `foto_*.jpg|png` dari database.
- Hanya admin (`403` untuk role user, `401` tanpa token), dicek sebelum berkas disentuh.

## Backup dan restore

Lihat `docs/BACKUP_RESTORE.md`. Singkat: `scripts\backup_db.bat` mencadangkan database **dan** `public/uploads/` (kolom `foto`
hanya nama berkas, jadi foto harus dicadangkan terpisah). Restore: `scripts\restore_db.bat backup\<berkas>.sql db_workshop_uji`.
Folder `backup/` dan isi `uploads/` tidak ikut ke Git.

## Hasil pengujian (workspace, MariaDB + Chromium)

- Backend: 49 skenario upload/update/hapus + 12 regresi, semuanya lulus.
- Frontend (Chromium): 32 pemeriksaan lulus, termasuk tampilan 375px dan XSS pada nama.
- Postman: collection 45 request (14 baru) dijalankan penuh dengan test lulus.
- OpenAPI 3.0 tervalidasi. Skrip `.bat` Windows belum dijalankan di lingkungan uji; coba sekali di komputermu.
- Rincian: `docs/TEST_CASE_ACARA26.md`.

## Catatan penting

- **Ganti password akun seed** (`Admin@12345`, `User@12345`) dan `Auth::SECRET` sebelum deployment atau push ke GitHub publik.
- Resource `/buku` belum dibatasi role. `Access-Control-Allow-Origin: *` cukup untuk workshop saja.
- Tema "smart village" pada indikator penilaian: proyek ini bertema akademik. Konfirmasikan ke dosen apakah cukup.
