# Laporan Pengujian Alur CRUD dari Sisi View (Acara 27)

BKPM Workshop SI Web Server (TIF330805), Acara 27 - Integrasi Backend-Frontend.
Pengujian: Uaryl Engno Laurenca (E41250317). Melanjutkan `docs/TEST_CASE_ACARA25.md` dan `docs/TEST_CASE_ACARA26.md`.

## 1. Ruang Lingkup

Acara 27 tidak mengubah kode PHP (backend tetap dari Acara 26). Yang diuji adalah View: `dashboard.html`, `form_tambah.html`, `form_edit.html`, `form_umum.js`, `common.js` (Notifikasi dan Validasi), terhubung penuh ke `MahasiswaController` lewat Router.

## 2. Lingkungan

| Item | Nilai |
|---|---|
| Base URL (Laragon) | `http://localhost/ACARA16-25/ACARA27/public` |
| Akun admin / user (seed) | `admin` / `Admin@12345`, `user_demo` / `User@12345` |
| Basis data | `db_workshop_acara27` (dari `db_workshop_acara27.sql`) |
| Alat | Browser (Developer Tools), Postman |

Catatan kejujuran: hasil di bawah diperoleh dengan menjalankan halaman di Chromium otomatis (Playwright) terhadap PHP 8.3 dan MariaDB di lingkungan pengembangan, bukan di Laragon. Kode PHP dan JavaScript identik. Ulangi di Laragon-mu dan sesuaikan bila berbeda.

## 3. Test Case View (F01 sampai F58)

### Penjaga halaman dan role

| ID | Skenario | Status |
|---|---|---|
| F01 | dashboard tanpa login -> login | Pass |
| F02 | form_tambah tanpa login -> login | Pass |
| F03 | form_edit tanpa login -> login | Pass |
| F04 | user: tombol Tambah tidak tampil | Pass |
| F05 | user membuka form_tambah -> dashboard + toast peringatan | Pass |
| F06 | user membuka form_edit -> dashboard | Pass |

### Dashboard dan Bootstrap

| ID | Skenario | Status |
|---|---|---|
| F07 | Bootstrap termuat (objek bootstrap + gaya tombol) | Pass |
| F08 | dashboard kosong: pesan "Belum ada data" | Pass |
| F09 | Tambah membuka form_tambah.html | Pass |

### Validasi sisi View (tugas mandiri 2)

| ID | Skenario | Status |
|---|---|---|
| F10 | form kosong: 3 field ditandai is-invalid | Pass |
| F11 | pesan "NIM wajib diisi" | Pass |
| F12 | toast peringatan tampil | Pass |
| F13 | tidak ada request ke server saat validasi gagal | Pass |
| F14 | NIM 8 karakter ditolak di View | Pass |
| F15 | nama 101 karakter ditolak di View | Pass |
| F16 | prodi 51 karakter ditolak di View | Pass |
| F17 | foto >2MB ditolak di View | Pass |
| F18 | berkas bukan gambar ditolak di View | Pass |
| F19 | tidak ada request selama validasi View gagal | Pass |

### Tambah data (Langkah 1)

| ID | Skenario | Status |
|---|---|---|
| F20 | pratinjau foto tampil | Pass |
| F21 | POST /mahasiswa membawa Authorization Bearer | Pass |
| F22 | Content-Type multipart dengan boundary (tidak diisi manual) | Pass |
| F23 | toast "Data berhasil ditambahkan." di dashboard | Pass |
| F24 | baris baru tampil di tabel | Pass |
| F25 | thumbnail foto termuat | Pass |
| F26 | database berisi data + foto | Pass |
| F27 | statistik total = 1 | Pass |

### Penanganan galat server pada form tambah

| ID | Skenario | Status |
|---|---|---|
| F28 | NIM duplikat: 409 tampil di field NIM, tetap di form | Pass |
| F29 | toast gagal (merah) tampil | Pass |
| F30 | tombol Simpan aktif lagi setelah gagal | Pass |
| F31 | data duplikat tidak tersimpan | Pass |
| F32 | server tidak terjangkau: pesan + toast, tombol aktif | Pass |

### Ubah data (Langkah 2)

| ID | Skenario | Status |
|---|---|---|
| F33 | form_edit terisi dari GET /mahasiswa?id= | Pass |
| F34 | foto saat ini tampil sebagai pratinjau | Pass |
| F35 | ubah dikirim sebagai POST ?id=N&_method=PUT dengan Authorization | Pass |
| F36 | FormData ubah tanpa foto baru tidak memuat berkas | Pass |
| F37 | toast "Data berhasil diperbarui." + tabel menampilkan perubahan | Pass |
| F38 | database berubah, foto lama dipertahankan | Pass |
| F39 | ganti foto: foto baru tersimpan, foto lama dihapus (unlink) | Pass |
| F40 | tambah tanpa foto berhasil (foto NULL) | Pass |
| F41 | ubah ke NIM milik data lain: 409 tampil di field | Pass |
| F42 | id tidak valid -> dashboard + toast | Pass |
| F43 | id tidak ada (404) -> dashboard + toast | Pass |

### Hapus data (Langkah 3)

| ID | Skenario | Status |
|---|---|---|
| F44 | modal konfirmasi hapus memuat nama + NIM | Pass |
| F45 | Batal: data tetap | Pass |
| F46 | hapus tanpa memuat ulang halaman (penanda window tetap) | Pass |
| F47 | toast "berhasil dihapus" | Pass |
| F48 | data dan berkas foto terhapus | Pass |
| F49 | DELETE ?id=N dengan Authorization | Pass |
| F50 | hapus data yang sudah hilang (404): toast peringatan + tabel diperbarui | Pass |

### Keamanan dan ketahanan

| ID | Skenario | Status |
|---|---|---|
| F51 | data berisi HTML tampil sebagai teks (tidak dieksekusi) | Pass |
| F52 | role diubah di localStorage: server menjawab 403, toast gagal, data tidak tersimpan | Pass |
| F53 | role di localStorage dikoreksi menjadi user | Pass |
| F54 | 401 saat menyimpan -> login dengan pesan sesi berakhir | Pass |

### Tampilan dan galat JavaScript

| ID | Skenario | Status |
|---|---|---|
| F55 | layar 375px tanpa scroll horizontal: dashboard.html | Pass |
| F56 | layar 375px tanpa scroll horizontal: form_tambah.html | Pass |
| F57 | layar 375px tanpa scroll horizontal: form_edit.html | Pass |
| F58 | tidak ada galat JavaScript | Pass |

Ringkasan: 58 dari 58 Pass (dijalankan dua kali berturut-turut dengan hasil sama). Pemeriksaan efek samping (isi basis data dan folder `public/uploads/`) dilakukan lewat query SQL dan daftar berkas setelah setiap operasi.

## 4. Catatan

- Ubah data dikirim sebagai `POST /mahasiswa?id=N&_method=PUT` (bukan method `PUT` asli seperti di modul), karena PHP tidak mengisi `$_POST` dan `$_FILES` dari body multipart pada method PUT.
- Validasi di View hanya lapisan tambahan; server tetap penentu (F28, F41, F52).
- Token berlaku 5 menit; form yang dibiarkan terbuka lebih lama akan dialihkan ke login (F54).
