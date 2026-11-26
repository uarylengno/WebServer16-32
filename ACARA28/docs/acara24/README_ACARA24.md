# Acara 24 – Integrasi API dengan Frontend

BKPM Workshop SI Web Server (TIF330805). Melanjutkan proyek Acara 17–23 (`sistem-informasi-akademik`).
README Acara 21, 22, dan 23 diarsipkan di `docs/acara21/`, `docs/acara22/`, `docs/acara23/`.

## Yang berubah dari Acara 23

| Berkas | Perubahan |
|--------|-----------|
| `public/index.php` | Header **CORS** dipasang sekali sebelum Router (Langkah 1). Ditambah: permintaan `OPTIONS` (preflight) dijawab `204` lalu berhenti |
| `public/views/index.html` | **Baru.** View tabel data mahasiswa (Langkah 4) |
| `public/views/app.js` | **Baru.** Kode `fetch` + `async/await` (Langkah 3), indikator "Memuat data..." dan pesan "Belum ada data" (tugas mandiri 1 dan 2) |
| `docs/API_DOCUMENTATION.md`, `docs/openapi.yaml` | Ditambah penjelasan CORS dan preflight |
| `app/`, `db_workshop.sql`, Postman | Tidak berubah |

## Menjalankan

1. Taruh proyek di `htdocs/sistem-informasi-akademik`, jalankan Apache dan MySQL, import `db_workshop.sql`.
2. Buka `http://localhost/ACARA16-25/ACARA27/public/views/index.html`.
   (Berkas statis: tidak melewati Router, karena `.htaccess` hanya mengarahkan ke `index.php` bila berkas tidak ada.)
3. Isi data lewat Postman (`POST /mahasiswa` dengan token) lalu klik **Muat ulang**.
4. Buka Developer Tools (F12): tab **Network** (request `mahasiswa` berstatus 200) dan **Console** (tanpa error CORS).

## Perbedaan dengan kode di modul (sengaja)

- **Tidak memakai `innerHTML` dengan data dari API.** Kode modul menyisipkan `mhs.nim`, `mhs.nama`, `mhs.prodi` lewat template string ke `innerHTML`.
  Nama seperti `<img src=x onerror=...>` yang tersimpan di database akan dieksekusi browser (XSS). Versi ini memakai `textContent`.
- **`response.ok` diperiksa.** `fetch()` tidak melempar galat untuk status 404/500 (sesuai dasar teori: Promise selesai begitu header diterima),
  sehingga tanpa pemeriksaan ini `hasil.data.forEach` akan error pada response galat.
- **`BASE_URL` diturunkan dari alamat halaman**, dengan alamat modul sebagai cadangan bila halaman dibuka lewat `file://`.
- **Preflight `OPTIONS` ditangani.** Tanpa ini Router menjawab 405 dan browser membatalkan `PUT`/`DELETE`/request ber-`Authorization` dari origin lain.

## Hasil pengujian (Chromium, 24 pemeriksaan: semua lulus)

Indikator "Memuat data...", pesan "Belum ada data", isi tabel = hasil API, payload XSS tampil sebagai teks, penanganan galat (HTTP 500, 502 non-JSON, data bukan array, jaringan putus),
pulih setelah galat, CORS lintas origin (GET, PUT ber-Authorization, response 404), kontrol negatif (tanpa header CORS, browser memblokir), dan halaman dari `file://`.
Regresi API: 26 test case Acara 23 dan 27 request Postman tetap lulus. Pengujian di workspace memakai SQLite, bukan MySQL/XAMPP.

## Catatan

- `Access-Control-Allow-Origin: *` cukup untuk workshop. Saat deployment, ganti dengan origin frontend.
- Keamanan: ganti `Auth::SECRET` (atau set env `JWT_SECRET`) sebelum push ke GitHub publik.
