# Laporan Pengujian RBAC dan Dashboard (Acara 25)

BKPM Workshop SI Web Server (TIF330805), Acara 25 - Authentication, Authorization, dan Dashboard Admin.
Pengujian: Uaryl Engno Laurenca (E41250317), 25 Oktober 2026.
Melanjutkan `docs/TEST_CASE.md` (Acara 23). Format dan istilah sama.

## 1. Ruang Lingkup

| Bagian | Yang diuji |
|---|---|
| Backend | `Auth::requireAdmin()` pada `MahasiswaController::store()`, `update()`, `destroy()`; hubungan 401 dan 403; pemalsuan token; akun hasil seed |
| Frontend | `login.html`, `dashboard.html` (login, penjaga halaman, ringkasan, tambah/ubah/hapus, Logout, tombol disembunyikan untuk role user) |
| Regresi | 26 test case Acara 23, Collection Postman, dan pengujian halaman Acara 24 |

## 2. Lingkungan dan Akun Uji

| Item | Nilai |
|---|---|
| Base URL | `http://localhost/ACARA16-25/ACARA25/public` |
| Akun admin (seed) | `admin` / `Admin@12345`, role `admin` |
| Akun user (seed) | `user_demo` / `User@12345`, role `user` |
| Kondisi awal | Database dari `db_workshop_acara25.sql` (tabel `users` berisi dua akun di atas) |
| Alat | Postman, browser (Developer Tools), `curl` |

Catatan kejujuran: hasil aktual diperoleh dengan mengirim request HTTP nyata ke aplikasi yang sama dan menjalankan halaman di Chromium,
tetapi pada lingkungan pengembangan yang memakai SQLite sebagai pengganti MySQL (server bawaan PHP). Kode PHP dan JavaScript identik.
Jalankan ulang di Laragon-mu dan sesuaikan kolom **Hasil aktual** bila berbeda.

## 3. Partisi Ekuivalen

| Identitas penelepon | Baca (`GET`) | Tulis `/mahasiswa` | Alasan |
|---|---|---|---|
| Tanpa token | 200 | 401 | Identitas belum terbukti |
| Token rusak / dipalsukan / kedaluwarsa / `alg=none` | 200 (GET tidak memeriksa token) | 401 | Identitas gagal diverifikasi |
| Token sah, role `user` | 200 | 403 | Identitas terbukti, hak akses tidak ada |
| Token sah tanpa klaim role | 200 | 403 | Role tidak ada dianggap bukan admin |
| Token sah, role `admin` | 200 | 200/201 (atau 400/404/409/422 sesuai data) | Lolos otorisasi, lanjut ke validasi |

## 4. Test Case Backend (R01 sampai R23)

| ID | Skenario | Data uji | Hasil diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|
| R01 | Login akun admin hasil seed | admin / Admin@12345 | 200 OK | 200 (Login berhasil) (role di response: admin) | Pass |
| R02 | Login akun user hasil seed | user_demo / User@12345 | 200 OK | 200 (Login berhasil) (role di response: user) | Pass |
| R03 | User: POST /mahasiswa | token role user | 403 Forbidden | 403 (Akses ditolak, hanya admin yang diizinkan) (jumlah data tidak berubah) | Pass |
| R04 | Admin: POST /mahasiswa | token role admin | 201 Created | 201 (Data berhasil ditambahkan) | Pass |
| R05 | User: PUT /mahasiswa?id=1 | token role user | 403 Forbidden | 403 (Akses ditolak, hanya admin yang diizinkan) (nama tetap Andi Saputra) | Pass |
| R06 | Admin: PUT /mahasiswa?id=1 | token role admin | 200 OK | 200 (Data berhasil diperbarui) (nama berubah jadi Andi S) | Pass |
| R07 | User: DELETE /mahasiswa?id=1 | token role user | 403 Forbidden | 403 (Akses ditolak, hanya admin yang diizinkan) (data masih ada) | Pass |
| R08 | User: GET /mahasiswa (membaca tetap boleh) | token role user | 200 OK | 200 (Data berhasil diambil) | Pass |
| R09 | Tanpa token: POST /mahasiswa | tanpa Authorization | 401 Unauthorized | 401 (Token tidak ditemukan) (401 (identitas belum ada), bukan 403) | Pass |
| R10 | Token admin kedaluwarsa: POST | exp lampau | 401 Unauthorized | 401 (Token tidak valid atau kedaluwarsa) | Pass |
| R11 | Token user dipalsukan: role diubah jadi admin, tanda tangan lama | payload.role=admin | 401 Unauthorized | 401 (Token tidak valid atau kedaluwarsa) (tanda tangan tidak cocok) | Pass |
| R12 | Token alg=none dengan role admin | tanpa tanda tangan | 401 Unauthorized | 401 (Token tidak valid atau kedaluwarsa) | Pass |
| R13 | Token role admin ditandatangani kunci lain | secret salah | 401 Unauthorized | 401 (Token tidak valid atau kedaluwarsa) | Pass |
| R14 | Token sah tanpa klaim role | payload tanpa role | 403 Forbidden | 403 (Akses ditolak, hanya admin yang diizinkan) | Pass |
| R15 | Otorisasi diperiksa sebelum validasi: user kirim body kosong | token user, body {} | 403 Forbidden | 403 (Akses ditolak, hanya admin yang diizinkan) (403, bukan 400) | Pass |
| R16 | User: PUT id tidak ada | token user, id=9999 | 403 Forbidden | 403 (Akses ditolak, hanya admin yang diizinkan) (403, bukan 404 (tidak membocorkan ada/tidaknya data)) | Pass |
| R17 | User: DELETE id tidak ada | token user, id=9999 | 403 Forbidden | 403 (Akses ditolak, hanya admin yang diizinkan) | Pass |
| R18 | Register dengan body role=admin tidak membuat admin | register + role=admin | 201 Created | 201 (Registrasi berhasil) (role hasil: user) | Pass |
| R19 | Akun hasil register (role user) ditolak menulis | token akun 'licik' | 403 Forbidden | 403 (Akses ditolak, hanya admin yang diizinkan) | Pass |
| R20 | Admin: validasi tetap berjalan setelah otorisasi | token admin, NIM 8 karakter | 422 Unprocessable Entity | 422 (Data tidak lolos validasi) | Pass |
| R21 | Admin: DELETE /mahasiswa?id=1 | token role admin | 200 OK | 200 (Data berhasil dihapus) (data terhapus) | Pass |
| R22 | Buku (di luar cakupan modul): user menulis buku | token user, POST /buku | 201 Created | 201 (Data berhasil ditambahkan) (masih requireAuth, bukan admin saja) | Pass |
| R23 | Header CORS ikut pada response 403 | token user | Header `Access-Control-Allow-Origin: *` | ACAO: * | Pass |

Ringkasan: 23 dari 23 Pass pada eksekusi pertama. Tidak ada Fail, sehingga tidak ada perbaikan kode pada Acara ini.
Pemeriksaan efek samping (jumlah data, isi data, data masih ada atau sudah terhapus) dilakukan lewat `GET` setelah request yang ditolak.

## 5. Test Case Frontend

Dijalankan di Chromium pada layar 1000 px dan 375 px (38 pemeriksaan, semuanya Pass).

| ID | Skenario | Yang diperiksa | Status |
|---|---|---|---|
| F01 | Buka dashboard tanpa login | Dialihkan ke `login.html` | Pass |
| F02 | Login dengan password salah | Pesan server "Username atau password salah", tetap di login, tidak ada token tersimpan | Pass |
| F03 | Login dengan field kosong | Pesan validasi tanpa mengirim request | Pass |
| F04 | Login sebagai `user_demo` | Masuk dashboard; token, role, username tersimpan; total mahasiswa = hasil API; jumlah program studi benar | Pass |
| F05 | Tampilan role user | Tombol Tambah, kolom Aksi, tombol Ubah dan Hapus tidak ada; catatan "hanya dapat dilihat" tampil (tugas mandiri 2) | Pass |
| F06 | Data berisi tag HTML (`<img onerror=...>`) | Tidak dieksekusi, tampil sebagai teks biasa | Pass |
| F07 | Logout | Token, role, username terhapus; pesan "Anda sudah keluar."; tombol Back dan akses langsung ke dashboard dialihkan ke login (tugas mandiri 1) | Pass |
| F08 | Login sebagai `admin` | Tombol Tambah, kolom Aksi, dan tombol Ubah/Hapus di setiap baris tampil | Pass |
| F09 | Tambah dengan NIM 8 karakter | Pesan 422 pada field NIM, dialog tetap terbuka | Pass |
| F10 | Tambah dengan NIM duplikat | Pesan 409 pada field NIM | Pass |
| F11 | Tambah dengan NIM kosong | Pesan 400 pada field NIM | Pass |
| F12 | Tambah dengan data benar | Dialog menutup, baris baru muncul, total naik, pesan sukses, data ada di database | Pass |
| F13 | Ubah data | Form terisi data baris; setelah simpan tabel dan database berubah; Batal tidak menyimpan | Pass |
| F14 | Hapus, konfirmasi dibatalkan lalu disetujui | Dibatalkan: data tetap; disetujui: data terhapus dan total turun | Pass |
| F15 | Role di `localStorage` diubah jadi `admin` oleh user | Tombol tampil, tetapi Tambah dan Hapus ditolak server (403), data tidak berubah; tampilan dikoreksi ke role user | Pass |
| F16 | Token kedaluwarsa di `localStorage` | Dialihkan ke login dengan pesan sesi berakhir, sesi dihapus | Pass |
| F17 | Token dipalsukan (role diubah, tanda tangan lama) | Halaman terbuka (GET publik), tetapi penyimpanan ditolak server (401), dialihkan ke login, data tidak berubah | Pass |
| F18 | Token habis saat halaman dibiarkan terbuka | Dialihkan otomatis ke login (sekitar 3 detik pada token uji berumur 3 detik) | Pass |
| F19 | Membuka login saat sudah masuk | Langsung ke dashboard | Pass |
| F20 | Server tidak terjangkau saat login | Pesan galat, tombol aktif kembali | Pass |
| F21 | `login.html?pesan=...` berisi skrip, `__proto__`, `constructor` | Tidak ada pesan yang tampil, skrip tidak berjalan | Pass |
| F22 | Layar 375 px | Tidak ada scroll horizontal pada halaman (tabel di-scroll di dalam wadahnya) | Pass |
| F23 | `index.html` (Acara 24) | Tetap menampilkan data, ada tautan Masuk | Pass |
| F24 | Seluruh skenario | Tidak ada galat JavaScript yang tak tertangani | Pass |

## 6. Regresi

| Pengujian | Hasil |
|---|---|
| 26 test case Acara 23 (operasi tulis memakai token admin) | 26 dari 26 Pass |
| Collection Postman (31 request: 27 lama, `Login admin berhasil`, dan tiga request 403) | 31 dari 31 sesuai harapan |
| Pengujian halaman Acara 24 (24 pemeriksaan, termasuk CORS lintas origin) | 24 dari 24 Pass |
| Log server | Tidak ada PHP Warning atau Fatal |

Dua perubahan pada pengujian lama akibat RBAC: operasi tulis di Acara 23 kini harus memakai token admin (sebelumnya token akun biasa), dan Collection Postman
memakai `{admin_token}` untuk POST/PUT/DELETE `/mahasiswa` serta `{token}` (role user) untuk menguji 403.

## 7. Temuan dan Catatan

| No | Catatan | Dampak | Tindakan |
|---|---|---|---|
| 1 | **Resource `/buku` belum dibatasi role** (R22): user biasa bisa menambah, mengubah, dan menghapus buku karena masih `Auth::requireAuth()` | Hak akses tidak konsisten antara mahasiswa dan buku | Sesuai cakupan modul (hanya `MahasiswaController`), tidak diubah. Untuk menyamakan, ganti `requireAuth()` menjadi `requireAdmin()` di `BukuController` lalu perbarui Postman dan dokumentasi |
| 2 | **Role tersimpan di dalam token**, bukan dibaca ulang dari database | Perubahan role (mis. admin diturunkan) baru berlaku setelah token lama habis, maksimal 300 detik | Diterima untuk workshop; tercatat di dokumentasi. Solusi lanjutan: cek role ke database atau token berumur lebih pendek |
| 3 | **Password akun seed tercantum di dokumen** (`Admin@12345`, `User@12345`) | Siapa pun yang membaca repositori publik dapat masuk sebagai admin bila password tidak diganti | Wajib diganti sebelum deployment atau push ke GitHub publik (petunjuk ada di `db_workshop_acara25.sql`) |
| 4 | **Token disimpan di `localStorage`** (sesuai modul) | Bila ada celah XSS, skrip penyerang dapat membaca token | Dikurangi dengan `textContent` (tidak ada `innerHTML` untuk data API; F06). Alternatif lebih aman: cookie `HttpOnly` |
| 5 | **Tidak ada pembatasan percobaan login** (rate limiting) | Tebakan password berulang tidak dibatasi | Di luar cakupan acara ini; perlu dipertimbangkan sebelum deployment |
| 6 | Logout hanya menghapus token di browser | Token yang sudah tersalin tetap sah sampai habis (JWT tanpa blacklist, lihat diskusi Acara 20) | Diterima; masa berlaku token dibuat singkat (300 detik) |
| 7 | Tidak ada "refresh token": setelah 5 menit pengguna dialihkan ke login | Isian form yang belum disimpan hilang saat sesi berakhir | Diterima; perpanjangan sesi dapat dikembangkan pada acara berikutnya |

## 8. Cara Menjalankan Manual

1. Import `db_workshop_acara25.sql` (membuat tabel dan dua akun awal), jalankan Laragon.
2. **Postman:** pilih Environment **Local**, jalankan Collection berurutan (folder 1, 2, 3). Request `Login admin berhasil` mengisi `{admin_token}`; `Login berhasil` mengisi `{token}` (role user).
3. **Browser:** buka `http://localhost/ACARA16-25/ACARA25/public/views/login.html`.
   - Masuk sebagai `user_demo`: tombol Tambah/Ubah/Hapus tidak tampil.
   - Masuk sebagai `admin`: seluruh fitur tampil; coba tambah, ubah, hapus.
   - Untuk melihat 403 dari server: masuk sebagai `user_demo`, buka Developer Tools > Application > Local Storage, ubah `role` menjadi `admin`, muat ulang, lalu klik Tambah dan simpan.
   - Untuk melihat sesi berakhir: tunggu 5 menit di dashboard, atau hapus satu karakter dari `token` lalu simpan data.
