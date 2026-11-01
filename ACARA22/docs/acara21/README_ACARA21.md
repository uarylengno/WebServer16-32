# Acara 21 – Dokumentasi Endpoint

BKPM Workshop SI Web Server (TIF330805). Melanjutkan proyek Acara 17–20 (`sistem-informasi-akademik`).

Acara ini **tidak mengubah kode PHP**. Yang bertambah adalah dokumentasi dan kelengkapan collection Postman.

## Yang berubah dari Acara 20

| Berkas | Perubahan |
|--------|-----------|
| `docs/API_DOCUMENTATION.md` | **Baru.** Dokumentasi seluruh endpoint (lihat isi di bawah) |
| `docs/CATATAN_ACARA_17-20.md` | `DOKUMENTASI_ENDPOINT.md` lama dipindahkan ke sini sebagai arsip (diagram alur, diskusi JWT tanpa logout) |
| `API_Sistem_Informasi_Akademik_MVC.postman_collection.json` | Diperbarui: **Description** di setiap request, **Examples** sukses dan gagal, dan 2 request Buku baru (`GET ?id=`, `PUT ?id=`) |
| `app/`, `public/`, `db_workshop.sql`, `Local/Staging.postman_environment.json` | Tidak berubah |

## Isi `docs/API_DOCUMENTATION.md`

1. **Ringkasan Endpoint**: tabel seluruh endpoint beserta Controller::method, kebutuhan token, dan status sukses (**tugas mandiri**).
2. **Peta Router**: resource URL → Controller (Langkah 2 modul).
3. **Format Response dan Status Code**: envelope, tabel kode status, contoh 500.
4. **Cara Mendapatkan dan Memakai Token**.
5. **Detail Endpoint**: untuk tiap endpoint ada Controller, autentikasi, headers, query parameter, tabel field request body beserta aturannya, urutan pemeriksaan, contoh response sukses dan gagal, serta contoh `curl` (Langkah 3 dan 4 modul).
6. **Response Umum Router** (404 dan 405).
7. **Dokumentasi di Postman**.

Semua contoh response dihasilkan dari eksekusi nyata kode (bukan ditulis tangan). Nilai yang berubah tiap eksekusi
(timestamp, `created_at`) diseragamkan dan token dipersingkat.

## Langkah Postman (Langkah 5 modul)

1. **Import** collection dan kedua environment, lalu pilih environment **Local**.
2. **Description**: sudah terisi di semua request (Controller + method, kebutuhan token, status sukses/gagal).
   Buka sebuah request lalu lihat panel dokumentasinya (ikon Documentation/Description di sisi kanan).
3. **Examples**: klik panah di samping nama request. Request pertama untuk tiap endpoint memuat seluruh contoh
   sukses dan gagalnya (mis. `POST /mahasiswa` punya contoh 201, 400, 401, 409, 422).
4. **View Documentation**: klik collection → menu **View documentation** (atau **Publish** bila ingin tautan publik).
   Simpan tautan atau screenshot-nya sebagai lampiran laporan. Langkah ini perlu akun Postman kamu sendiri,
   jadi tidak bisa disiapkan di dalam berkas.
5. Jalankan folder 1 → 2 → 3 lewat Collection Runner untuk memastikan dokumentasi masih sesuai perilaku API
   (import ulang `db_workshop.sql` dulu supaya ID mulai dari 1).

## Catatan

- **Cara menjaga dokumentasi tetap benar**: jika Controller atau Router berubah, perbarui tabel ringkasan,
  bagian endpoint terkait, dan Examples-nya. Dokumentasi yang usang lebih merugikan daripada tidak ada.
- Aturan NIM di kode adalah **tepat 9 karakter** (bukan harus angka); pesan errornya menyebut "9 digit".
  Dokumen mencatat aturan sesuai perilaku kode.
- `GET` publik, `POST/PUT/DELETE` pada `/mahasiswa` dan `/buku` memerlukan token (berlaku 5 menit).
- Ganti `Auth::SECRET` (atau set environment variable `JWT_SECRET`) sebelum repo di-push ke GitHub publik.
