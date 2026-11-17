# Acara 25 – Authentication, Authorization, dan Dashboard Admin

BKPM Workshop SI Web Server (TIF330805). Melanjutkan proyek Acara 17–24 (`sistem-informasi-akademik`).
README Acara 21–24 diarsipkan di `docs/acara21/` sampai `docs/acara24/`.

## Yang berubah dari Acara 24

| Berkas | Perubahan |
|--------|-----------|
| `app/core/Auth.php` | Ditambah `Auth::requireAdmin()` (Langkah 1): `requireAuth()` lebih dulu (401), lalu cek role (403) |
| `app/controllers/MahasiswaController.php` | `store()`, `update()`, `destroy()` memakai `Auth::requireAdmin()` (Langkah 2). `GET` tetap publik |
| `db_workshop.sql` | Ditambah **akun awal**: `admin` / `Admin@12345` (role admin) dan `user_demo` / `User@12345` (role user) |
| `public/views/login.html`, `login.js` | **Baru.** Halaman login (Langkah 3) |
| `public/views/dashboard.html`, `dashboard.js` | **Baru.** Dashboard: penjaga halaman (Langkah 4), ringkasan data (Langkah 5), tambah/ubah/hapus, **Logout** (tugas 1), tombol disembunyikan untuk role user (tugas 2) |
| `public/views/common.js`, `styles.css` | **Baru.** Kode dan gaya bersama (alamat API, penyimpanan sesi, pemanggil API) |
| `public/views/index.html` | Ditambah tautan "Masuk" |
| Postman | Collection: `Login admin berhasil`, `{{admin_token}}` untuk tulis data mahasiswa, 3 request 403 (31 request). Environment: `admin_username`, `admin_password`, `admin_token` |
| `docs/` | `API_DOCUMENTATION.md` dan `openapi.yaml` (403, RBAC), `TEST_CASE_ACARA25.md` baru |
| `BukuController.php`, `Router.php`, `public/index.php` | Tidak berubah |

## Menjalankan

1. Taruh proyek di `htdocs/sistem-informasi-akademik`, jalankan Apache dan MySQL, **import ulang `db_workshop.sql`** (tabel `users` kini berisi dua akun awal).
2. Buka `http://localhost/ACARA16-25/ACARA26/public/views/login.html`.
3. Masuk sebagai `user_demo` (hanya melihat data) atau `admin` (tambah, ubah, hapus).

## Alur Authentication dan Authorization

| Kondisi | Hasil |
|---|---|
| Tanpa token / token rusak / kedaluwarsa | `401` (identitas belum terbukti) |
| Token sah, role `user`, operasi tulis mahasiswa | `403` "Akses ditolak, hanya admin yang diizinkan" |
| Token sah, role `admin` | Diproses (lalu validasi: 400, 404, 409, 422, atau sukses) |

- Role dibaca dari **token yang tanda tangannya diverifikasi**, bukan dari `localStorage`. Mengubah role di `localStorage` hanya mengubah tampilan; server tetap menjawab 403.
- Otorisasi dicek sebelum validasi body dan pencarian data, sehingga non-admin selalu mendapat 403 (bukan 400/404).
- `POST /register` selalu membuat role `user`. Admin pertama dibuat lewat seed.
- Dashboard menangani sesi berakhir (token 5 menit): 401 dari server atau waktu habis -> dialihkan ke login.

## Perbedaan dengan kode di modul (sengaja)

- `requireAdmin()` memakai `$payload['role'] ?? ''` agar token tanpa klaim role ditolak 403, bukan menimbulkan PHP Warning.
- Login memakai pesan di halaman, bukan `alert()`. Data dari API ditampilkan dengan `textContent` (bukan `innerHTML`) untuk mencegah XSS.
- Dashboard memeriksa masa berlaku token (bukan hanya ada atau tidaknya), menangani 401 dan 403 dari server, dan tombol Back setelah logout tidak memulihkan dashboard.
- Akun admin awal tidak bisa dibuat lewat register, jadi disediakan seed di `db_workshop.sql`.

## Hasil pengujian (workspace, SQLite)

- Backend RBAC: 23 skenario Pass (403 vs 401, token dipalsukan, `alg=none`, eskalasi lewat register, efek samping data).
- Frontend (Chromium): 38 pemeriksaan Pass. Regresi: 26 test case Acara 23, Collection Postman 31/31, halaman Acara 24 24/24.
- Pengujian memakai SQLite, bukan MySQL/XAMPP. Rinciannya ada di `docs/TEST_CASE_ACARA25.md`.

## Catatan penting

- **Ganti password akun seed** (`Admin@12345`, `User@12345`) sebelum deployment atau push ke GitHub publik. Petunjuknya ada di `db_workshop.sql`. Ganti juga `Auth::SECRET` (atau set env `JWT_SECRET`).
- **Resource `/buku` belum dibatasi role** (hanya `MahasiswaController` yang diminta modul): user biasa masih bisa menulis buku.
- `Access-Control-Allow-Origin: *` cukup untuk workshop; ganti dengan origin frontend saat deployment.
