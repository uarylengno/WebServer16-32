> **Catatan arsip Acara 17-20** (diagram alur, diskusi JWT tanpa logout server). Referensi API terbaru dan lengkap ada di `API_DOCUMENTATION.md`.

# Dokumentasi Endpoint – Acara 20 (API Authentication dan Error Handling)

Base URL: `http://localhost/sistem-informasi-akademik/public`
Seluruh response dibentuk oleh `Response::json()` sehingga formatnya seragam.

## Format response

```json
{
    "success": false,
    "message": "Token tidak valid atau kedaluwarsa",
    "data": null,
    "errors": { "field": "pesan" },
    "timestamp": "2026-10-22T18:31:20+07:00"
}
```
`errors` hanya muncul bila ada kesalahan per field. Mode `APP_ENV=development` memakai pretty print,
`production` memakai JSON satu baris (lihat `app/config/app.php`).

## Ringkasan endpoint

| Method | Alamat | Fungsi | Perlu token? | Sukses |
|--------|--------|--------|--------------|--------|
| POST | `/register` | Daftar pengguna baru | Tidak | 201 |
| POST | `/login` | Login, mendapat token JWT | Tidak | 200 |
| GET | `/mahasiswa`, `/mahasiswa?id=1` | Lihat data | **Tidak (publik)** | 200 |
| POST | `/mahasiswa` | Tambah data | **Ya** | 201 |
| PUT | `/mahasiswa?id=1` | Ubah data | **Ya** | 200 |
| DELETE | `/mahasiswa?id=1` | Hapus data | **Ya** | 200 |
| GET | `/buku`, `/buku?id=1` | Lihat data | Tidak (publik) | 200 |
| POST/PUT/DELETE | `/buku` | Tulis data | Ya | 201/200 |

Token dikirim lewat header: `Authorization: Bearer <token>`.

## Alur autentikasi

```
1. POST /register           → akun dibuat (password disimpan sebagai hash)
2. POST /login              → AuthController::login()
        ├─ cari user (User::findByUsername)
        ├─ password_verify(input, hash)           gagal → 401 "Username atau password salah"
        └─ Auth::generateToken(['id','role'])     → 200 { token, token_type, expires_in: 300, role }
3. POST /mahasiswa + header Authorization: Bearer <token>
        └─ MahasiswaController::store()
              └─ Auth::requireAuth()   ← baris pertama, sebelum membaca body / menyentuh database
                    ├─ header tidak ada / bukan "Bearer"  → 401 "Token tidak ditemukan"
                    ├─ signature salah / token diubah     → 401 "Token tidak valid atau kedaluwarsa"
                    ├─ exp sudah lewat                    → 401 "Token tidak valid atau kedaluwarsa"
                    └─ lolos → lanjut ke validasi, Model, dan response 201
```

## Contoh request dan response

**POST /login**
```json
{ "username": "andi_saputra", "password": "rahasia123" }
```
Response `200 OK`:
```json
{
    "success": true,
    "message": "Login berhasil",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MSwicm9sZSI6InVzZXIi....",
        "token_type": "Bearer",
        "expires_in": 300,
        "role": "user"
    },
    "timestamp": "2026-10-22T18:31:19+07:00"
}
```

**POST /login dengan password salah atau username tidak ada → 401** (pesan sengaja sama)
```json
{ "success": false, "message": "Username atau password salah", "data": null, "timestamp": "..." }
```

**POST /mahasiswa tanpa token → 401** (header respons: `WWW-Authenticate: Bearer`)
```json
{ "success": false, "message": "Token tidak ditemukan", "data": null, "timestamp": "..." }
```

**POST /mahasiswa dengan token diubah / kedaluwarsa → 401**
```json
{ "success": false, "message": "Token tidak valid atau kedaluwarsa", "data": null, "timestamp": "..." }
```

**POST /mahasiswa dengan token sah → 201**
```json
{ "success": true, "message": "Data berhasil ditambahkan", "data": { "id": 1 }, "timestamp": "..." }
```

## Tabel status code

| Kode | Dipakai ketika |
|------|----------------|
| 200 | GET/PUT/DELETE/login berhasil |
| 201 | POST (register, tambah data) berhasil |
| 400 | Field wajib kosong, JSON rusak, `id` bukan angka |
| 401 | Token tidak ada / tidak valid / kedaluwarsa; username atau password login salah |
| 404 | ID atau endpoint tidak ditemukan |
| 405 | Method tidak diizinkan (mis. GET /login) |
| 409 | NIM, username, atau email duplikat |
| 422 | Data lengkap tetapi gagal validasi (NIM bukan 9 karakter, email tidak valid, dst.) |
| 500 | Kesalahan server (database gagal, dst.) |

Kode 403 (Forbidden) belum dipakai karena belum ada pembatasan berdasarkan role; `role` sudah ada di
payload token sehingga dapat dipakai untuk itu pada pengembangan berikutnya.

## Error handling berlapis

| Lapisan | Mekanisme |
|---------|-----------|
| Model (`Mahasiswa.php`) | Setiap query dalam `try...catch (PDOException)`. Detail error → `error_log()`; client menerima 500 "Terjadi kesalahan pada server". Pelanggaran UNIQUE (kode `23000`) → 409. |
| Controller | Validasi 400/422, pemeriksaan 404/409, `Auth::requireAuth()` untuk 401. |
| Router | Endpoint tidak dikenal → 404; method salah → 405. |
| Front Controller (`index.php`) | `try...catch (Throwable)` terakhir: error tak terduga → 500 generik, detail hanya di log. |
| `Response` | Jika `json_encode()` gagal, tetap mengirim 500 berupa JSON sah. |

Prinsip: **pesan error untuk client tidak boleh membocorkan detail internal** (nama tabel, query SQL, path file).
Ini berbeda dari contoh modul yang menampilkan `$e->getMessage()` langsung ke client.

Cara mensimulasikan (untuk screenshot): ubah nama tabel `mahasiswa` menjadi `mahasiswa_x` di phpMyAdmin,
lalu `GET /mahasiswa` → 500 dengan pesan umum, sedangkan detail `no such table / Table ... doesn't exist`
hanya muncul di log error Apache (`xampp/apache/logs/error.log`). Kembalikan nama tabel setelah uji.

## Struktur JWT (RFC 7519)

```
header.payload.signature

header    = base64url({"alg":"HS256","typ":"JWT"})
payload   = base64url({"id":1,"role":"user","iat":1792668679,"exp":1792668979})
signature = base64url( HMAC-SHA256( header + "." + payload, SECRET ) )
```

- Ditulis native: `hash_hmac('sha256', ..., true)` + Base64URL (`+/` → `-_`, tanpa `=`).
- Validasi: (1) tanda tangan dihitung ulang dan dibandingkan dengan `hash_equals()`; (2) `alg` harus `HS256`;
  (3) `exp` harus ada dan belum lewat.
- Payload **hanya di-encode, tidak dienkripsi**: siapa pun bisa membacanya (coba tempel token ke jwt.io), jadi
  jangan menaruh data rahasia di payload. Yang dijamin signature adalah *keaslian*, bukan kerahasiaan.
- `SECRET` ada di `Auth.php` (`kunci_rahasia_workshop_2026`) sebagai nilai bawaan modul. **Ganti sebelum
  deployment atau sebelum repo di-push ke GitHub**, atau set environment variable `JWT_SECRET`.

## Tugas mandiri 1 – Mengapa JWT stateless tidak punya logout di sisi server?

**Penyebab.** JWT bersifat *self-contained*: semua informasi yang dibutuhkan untuk memeriksa token
(identitas, role, waktu kedaluwarsa, dan tanda tangan) ada di dalam token itu sendiri. Server tidak menyimpan
daftar sesi, sehingga setiap request cukup diverifikasi dengan menghitung ulang signature. Akibatnya, setelah
token diterbitkan server tidak punya catatan apa pun tentang token itu yang bisa dihapus. Selama signature benar
dan `exp` belum lewat, token akan selalu dianggap sah, termasuk bila pengguna sudah menekan "logout", dan juga
bila token dicuri.

**Yang sebenarnya terjadi saat "logout".** Logout hanya terjadi di sisi client: aplikasi membuang token
dari penyimpanannya. Token yang sama, jika masih tersimpan di tempat lain atau sudah disalin pihak lain,
tetap berlaku sampai `exp`.

**Mekanisme tambahan bila server harus bisa membatalkan token:**

| Mekanisme | Cara kerja | Konsekuensi |
|-----------|-----------|-------------|
| Masa berlaku pendek | `exp` singkat (praktikum ini 5 menit) | Jendela penyalahgunaan kecil; pengguna harus login ulang lebih sering |
| Access token pendek + refresh token | Refresh token disimpan di database dan bisa dicabut | Logout = cabut refresh token; access token tetap hidup sampai `exp` |
| Blacklist token | Simpan `jti` (ID token) yang dibatalkan sampai `exp`-nya lewat; `requireAuth()` memeriksanya | Server kembali menyimpan state (tidak murni stateless) dan ada satu query tambahan per request |
| Versi token per pengguna | Kolom `token_version` di tabel users, dimasukkan ke payload; logout menaikkan angka | Semua token lama pengguna itu langsung tidak sah, tetapi butuh pembacaan database tiap request |

**Kesimpulan.** Tidak adanya logout di sisi server adalah *konsekuensi langsung* dari desain stateless, bukan
kekurangan implementasi: kemudahan skala (tanpa penyimpanan sesi) dibayar dengan ketidakmampuan mencabut token
sebelum waktunya. Pada praktikum ini risikonya dikendalikan dengan masa berlaku 5 menit; untuk sistem nyata
yang membutuhkan logout atau pencabutan akses seketika, dipakai kombinasi token pendek dengan refresh token atau
blacklist, dengan sadar menerima bahwa sebagian sifat stateless hilang.

## Tugas mandiri 2 – Masa berlaku token 5 menit

`Auth::TOKEN_TTL = 300` dipakai pada `generateToken()` (sebelumnya 3600). Pengamatan yang dapat dilakukan:
1. Login, salin token, kirim `POST /mahasiswa` → 201.
2. Tunggu lebih dari 5 menit, kirim request yang sama → **401 "Token tidak valid atau kedaluwarsa"**.
3. Login ulang → token baru → 201 kembali.

Untuk pengujian tanpa menunggu, environment Postman memuat `expired_token` (token sah secara tanda tangan,
`exp`-nya sudah lewat), dipakai oleh request "POST token kedaluwarsa -> 401".
