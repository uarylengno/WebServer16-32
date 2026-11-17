# Acara 22 – Dokumentasi Endpoint (OpenAPI / Swagger)

BKPM Workshop SI Web Server (TIF330805). Melanjutkan Acara 21: dokumentasi Markdown dikonversi menjadi **OpenAPI 3.0**.

Acara ini **tidak mengubah kode PHP**. README Acara 21 diarsipkan di `docs/acara21/README_ACARA21.md`.

## Yang berubah dari Acara 21

| Berkas | Perubahan |
|--------|-----------|
| `docs/openapi.yaml` | **Baru.** Spesifikasi OpenAPI 3.0 untuk 10 operasi (12 endpoint Acara 21 dikelompokkan per path) |
| `docs/API_DOCUMENTATION.md` | Tidak berubah (tetap rujukan versi teks) |
| `app/`, `public/`, `db_workshop.sql`, Postman | Tidak berubah |

## Isi `docs/openapi.yaml`

- `info`, `servers` (Langkah 1) dan `tags` (Autentikasi, Mahasiswa, Buku)
- `paths` (Langkah 2 dan 3 + **tugas mandiri no. 1**): `/register`, `/login`, `/mahasiswa` (GET, POST, PUT, DELETE), `/buku` (GET, POST, PUT, DELETE).
  Path mengikuti resource pada peta Router, ID lewat query `?id=`.
- `components.securitySchemes.bearerAuth` bertipe `http` / `bearer` / `JWT` (**tugas mandiri no. 2**), dipakai lewat `security: [ { bearerAuth: [] } ]`
  pada semua operasi POST, PUT, DELETE mahasiswa dan buku.
- `components.schemas`, `parameters`, `responses`: bagian yang dipakai ulang lewat `$ref` agar tidak diulang di tiap endpoint.

## Langkah Visualisasi (Langkah 4 modul)

1. Buka <https://editor.swagger.io>.
2. Ganti seluruh isi panel kiri dengan isi `docs/openapi.yaml` (File > Import file juga bisa).
3. Amati pratinjau di panel kanan. Klik **Authorize**, tempel token dari `POST /login`, lalu coba **Try it out**.
4. Ambil screenshot, simpan di `docs/` (mis. `docs/swagger-preview.png`).

Catatan: **Try it out** memanggil server dari browser. Server di `servers` adalah `http://localhost/...`, jadi XAMPP harus menyala.
Jika browser memblokir dengan error CORS (halaman `editor.swagger.io` berjalan di HTTPS), pengujian tetap bisa dilakukan lewat Postman.
Postman juga bisa mengimpor `openapi.yaml` (Import > pilih berkas) untuk membuat collection otomatis.

## Catatan

- NIM tervalidasi tepat 9 karakter (tidak harus angka), sesuai kode.
- Keamanan: ganti `Auth::SECRET` (atau set env `JWT_SECRET`) sebelum push ke GitHub publik.
