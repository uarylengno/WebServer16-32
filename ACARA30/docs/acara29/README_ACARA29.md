# Acara 29 — Penyempurnaan View (Loading State, Escaping XSS, Retry)

Folder Laragon: `C:\laragon\www\ACARA16-25\ACARA29`, database `db_workshop_acara29` (import `db_workshop_acara29.sql`).
Backend sama dengan Acara 28; perubahan hanya di `public/views`:

| Berkas | Perubahan |
|--------|-----------|
| `common.js` | `escapeHTML()`, `fetchDenganUlang()` (retry satu kali + batas waktu), `Tombol.sibuk()`, `barisPesan()`; `panggilApi()` memakai retry untuk request yang aman diulang |
| `app.js`, `index.html` | Baris "Memuat data..." dengan spinner, `renderTabel()` dengan `escapeHTML`, retry; `index.html` kini memuat `common.js` |
| `dashboard.js` | Baris loading di tabel, tombol Muat ulang dan Hapus bersatus loading, notifikasi retry |
| `form_umum.js` | try-catch-finally, tombol Simpan pulih otomatis, pengiriman ganda dicegah |
| `login.js` | Tombol loading dan retry login |

Dokumen: `docs/TEST_CASE_ACARA29.md` (79 pemeriksaan Pass). Dokumen deployment Acara 28 tetap berlaku (`docs/DEPLOYMENT.md`).

---

# Acara 27 – Integrasi Backend-Frontend (CRUD dari View dengan Bootstrap)

BKPM Workshop SI Web Server (TIF330805). Melanjutkan proyek Acara 17–26 (`sistem-informasi-akademik`).
README Acara 21–26 diarsipkan di `docs/acara21/` sampai `docs/acara26/`.

**Backend (PHP) tidak berubah dari Acara 26.** Acara ini hanya mengembangkan View.

## Yang berubah dari Acara 26

| Berkas | Perubahan |
|--------|-----------|
| `public/views/form_tambah.html`, `form_tambah.js` | **Baru** (Langkah 1). Form tambah data: `FormData` + header `Authorization` |
| `public/views/form_edit.html`, `form_edit.js` | **Baru** (Langkah 2). `muatDataUntukEdit(id)` dan `simpanPerubahan(id)` |
| `public/views/form_umum.js` | **Baru.** Logika bersama kedua form: penjaga admin, pratinjau foto, validasi, kirim, notifikasi |
| `public/views/dashboard.html`, `dashboard.js` | Memakai Bootstrap. Tombol **Tambah**/**Ubah** menuju halaman form, tombol **Hapus** (Langkah 3) memakai modal konfirmasi dan memuat ulang tabel tanpa reload halaman |
| `public/views/common.js` | Ditambah `Notifikasi` (toast, tugas mandiri 1) dan `Validasi` (tugas mandiri 2) |
| `public/views/vendor/bootstrap/` | **Baru.** Bootstrap 5.3.3 (CSS + JS bundle), disalin lokal sehingga tidak memerlukan internet |
| `db_workshop_acara27.sql`, `Database.php` | Nama database `db_workshop_acara27` |
| `docs/TEST_CASE_ACARA27.md` | **Baru.** 58 pengujian View |

## Menjalankan

1. Taruh proyek di `C:\laragon\www\ACARA16-25\ACARA27` (tanpa spasi pada nama folder). Pastikan `AllowOverride All` aktif di `httpd.conf`, lalu restart Apache.
2. Import `db_workshop_acara27.sql` lewat phpMyAdmin (tab Import).
3. Buka `http://localhost/ACARA16-25/ACARA27/public/views/login.html`, masuk sebagai `admin` / `Admin@12345`.
4. Coba alur CRUD: **Tambah** (isi form, pilih foto) → **Ubah** (ganti nama atau foto) → **Hapus** (konfirmasi pada modal).

## Alur halaman

| Halaman | Fungsi |
|---|---|
| `login.html` | Login, token disimpan di localStorage |
| `dashboard.html` | Ringkasan, tabel, tombol Hapus. Menampilkan notifikasi dari halaman form |
| `form_tambah.html` | `POST /mahasiswa` (multipart) |
| `form_edit.html?id=N` | `GET /mahasiswa?id=N` untuk mengisi form, lalu `POST /mahasiswa?id=N&_method=PUT` |

Form hanya untuk admin: role `user` yang membuka form dikembalikan ke dashboard. Itu hanya kenyamanan tampilan; penentu sebenarnya tetap `Auth::requireAdmin()` di server (403).

## Tugas mandiri

1. **Notifikasi visual** (`Notifikasi` di `common.js`): toast Bootstrap hijau untuk berhasil, merah untuk gagal, kuning untuk peringatan. Setelah form menyimpan, pesan diteruskan ke dashboard lewat `sessionStorage` dan tampil sekali.
2. **Validasi sisi View** (`Validasi` di `common.js`): NIM wajib dan tepat 9 karakter, nama wajib (maks 100), prodi wajib (maks 50), foto opsional (JPG/PNG, maks 2MB). Pesan tampil di bawah field (`is-invalid`) dan tidak ada request yang dikirim bila isian belum lolos. Aturannya sama dengan `MahasiswaController`, sehingga server tetap menjadi penentu.

## Perbedaan dengan kode di modul (sengaja)

- **`method: 'PUT'` pada `fetch()` dengan `FormData` tidak berfungsi di PHP** (kode modul Langkah 2): PHP hanya mengisi `$_POST` dan `$_FILES` dari body multipart untuk method POST, sehingga data dan foto tidak sampai ke Controller. Karena itu perubahan dikirim sebagai `POST ?id=N&_method=PUT`, yang diteruskan Router ke `update()` (sama seperti Acara 26). `PUT` asli dengan multipart dijawab 400 oleh server.
- `Content-Type` tidak diisi manual pada `FormData` agar browser menambahkan boundary (sesuai dasar teori).
- Konfirmasi hapus memakai modal Bootstrap, bukan `confirm()`. Setelah hapus, tabel dimuat ulang lewat `fetch`, tanpa memuat ulang halaman.
- Status respons diperiksa (`response.ok`), 401 mengalihkan ke login, 403 mengoreksi tampilan role, 404 dan galat jaringan menampilkan pesan. Kode modul tidak menangani kegagalan.
- Data dari API ditampilkan dengan `textContent`, bukan `innerHTML`, sehingga tag HTML pada data tidak dieksekusi (XSS).
- Perubahan tampil di dashboard tanpa reload manual: setelah form menyimpan, dashboard memuat data terbaru lewat `fetch`.

## Hasil pengujian (workspace)

- 58 pemeriksaan View di Chromium: seluruhnya Pass (dua kali berjalan). Rincian: `docs/TEST_CASE_ACARA27.md`.
- Pengujian memakai PHP 8.3 dan MariaDB, bukan Laragon. Ulangi di Laragon-mu sebelum menulis hasil di laporan.

## Catatan penting

- **Ganti password akun seed** (`Admin@12345`, `User@12345`) dan `Auth::SECRET` sebelum deployment atau push ke GitHub publik.
- Token berlaku 5 menit; halaman yang dibiarkan terbuka lebih lama dialihkan ke login.
- `index.html` (Acara 24) dan `login.html` tidak diubah; keduanya masih memakai `styles.css`.
- Resource `/buku` belum dibatasi role (di luar cakupan modul).
