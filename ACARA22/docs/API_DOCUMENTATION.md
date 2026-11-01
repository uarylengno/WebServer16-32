# Dokumentasi API - Sistem Informasi Akademik

BKPM Workshop SI Web Server (TIF330805), Acara 21 - Dokumentasi Endpoint.
Dokumen ini menjelaskan seluruh endpoint yang dilayani kode pada Acara 20 (PHP Native, arsitektur MVC). Semua contoh response diambil dari eksekusi nyata kode tersebut.

| Informasi | Nilai |
|---|---|
| Base URL | `http://localhost/sistem-informasi-akademik/public` |
| Format | JSON (`Content-Type: application/json`) untuk request dan response |
| Autentikasi | Bearer Token (JWT HS256), berlaku 300 detik (5 menit) |
| Pembaruan terakhir | 24 Oktober 2026 |

## 1. Ringkasan Endpoint

| No | Method | Endpoint | Controller | Autentikasi | Sukses | Fungsi |
|---|---|---|---|---|---|---|
| 1 | `POST` | `/register` | `AuthController::register()` | Publik | 201 | Mendaftarkan pengguna baru |
| 2 | `POST` | `/login` | `AuthController::login()` | Publik | 200 | Memverifikasi kredensial dan menerbitkan token JWT (HS256) yang berlaku 300 detik (5 menit) |
| 3 | `GET` | `/mahasiswa` | `MahasiswaController::index()` | Publik | 200 | Menampilkan seluruh data mahasiswa (terbaru lebih dulu) |
| 4 | `GET` | `/mahasiswa?id={id}` | `MahasiswaController::index()` | Publik | 200 | Menampilkan satu mahasiswa berdasarkan ID (method yang sama dengan daftar, dibedakan oleh `?id=`) |
| 5 | `POST` | `/mahasiswa` | `MahasiswaController::store()` | Bearer Token | 201 | Menambahkan mahasiswa baru |
| 6 | `PUT` | `/mahasiswa?id={id}` | `MahasiswaController::update()` | Bearer Token | 200 | Mengubah seluruh data mahasiswa (semua field wajib dikirim) |
| 7 | `DELETE` | `/mahasiswa?id={id}` | `MahasiswaController::destroy()` | Bearer Token | 200 | Menghapus mahasiswa berdasarkan ID |
| 8 | `GET` | `/buku` | `BukuController::index()` | Publik | 200 | Menampilkan seluruh data buku |
| 9 | `GET` | `/buku?id={id}` | `BukuController::index()` | Publik | 200 | Menampilkan satu buku berdasarkan ID (method yang sama dengan daftar, dibedakan oleh `?id=`) |
| 10 | `POST` | `/buku` | `BukuController::store()` | Bearer Token | 201 | Menambahkan buku baru |
| 11 | `PUT` | `/buku?id={id}` | `BukuController::update()` | Bearer Token | 200 | Mengubah seluruh data buku |
| 12 | `DELETE` | `/buku?id={id}` | `BukuController::destroy()` | Bearer Token | 200 | Menghapus buku berdasarkan ID |

Aturan umum: operasi **baca (GET)** bersifat publik, sedangkan operasi **tulis (POST/PUT/DELETE)** pada `/mahasiswa` dan `/buku` wajib membawa token. `/register` dan `/login` publik karena dipakai untuk memperoleh token.

## 2. Peta Router

`Router::dispatch()` membaca segmen terakhir URL sebagai nama resource, lalu memanggil Controller yang sesuai.

| Resource (URL) | Controller | Keterangan |
|---|---|---|
| `/mahasiswa` | `MahasiswaController` | CRUD data mahasiswa (`index` / `store` / `update` / `destroy`, dipilih dari HTTP method) |
| `/buku` | `BukuController` | CRUD data buku (pola sama dengan mahasiswa) |
| `/login` | `AuthController::login` | Autentikasi pengguna, menghasilkan token. Hanya menerima POST |
| `/register` | `AuthController::register` | Pendaftaran pengguna baru. Hanya menerima POST |

Pemetaan HTTP method pada resource CRUD: `GET` -> `index()`, `POST` -> `store()`, `PUT` -> `update($id)`, `DELETE` -> `destroy($id)`. ID dikirim lewat query string `?id=`. Method lain dibalas `405`; resource yang tidak ada dibalas `404`.

```
Client -> public/index.php (Front Controller) -> Router::dispatch()
            -> Controller (Auth::requireAuth() bila terproteksi, validasi)
            -> Model (PDO + prepared statement) -> MySQL
            -> Response::json() -> Client
```

## 3. Format Response dan Status Code

Semua response (sukses maupun gagal, dari Router maupun Controller) dibentuk oleh `Response::json()`:

| Field | Tipe | Keterangan |
|---|---|---|
| `success` | boolean | `true` jika berhasil, `false` jika gagal |
| `message` | string | Pesan ringkas untuk manusia |
| `data` | object / array / null | Data hasil, atau `null` |
| `errors` | object | **Hanya muncul** bila ada kesalahan per field, contoh `{"nim": "..."}` |
| `timestamp` | string | Waktu response (ISO 8601) |

Mode `APP_ENV=development` (`app/config/app.php`) menampilkan JSON berindentasi; mode `production` menampilkan JSON satu baris. Isi datanya sama.

| Kode | Arti | Dipakai ketika |
|---|---|---|
| 200 | OK | GET, PUT, DELETE, atau login berhasil |
| 201 | Created | POST berhasil membuat data (register, tambah data) |
| 400 | Bad Request | Body bukan JSON valid, field wajib kosong, atau `id` bukan angka positif |
| 401 | Unauthorized | Token tidak ada / tidak valid / kedaluwarsa; username atau password login salah |
| 404 | Not Found | ID atau endpoint tidak ditemukan |
| 405 | Method Not Allowed | Method tidak didukung endpoint (header `Allow` disertakan) |
| 409 | Conflict | NIM, username, atau email sudah dipakai |
| 422 | Unprocessable Entity | JSON lengkap tetapi isinya melanggar aturan (format NIM, email, tahun, panjang teks) |
| 500 | Internal Server Error | Kesalahan server (mis. database); detail hanya di log server |

Kode `403 Forbidden` belum dipakai karena belum ada pembatasan berdasarkan role.

Contoh `500` (semua endpoint; pesan sengaja umum, tanpa detail SQL):

```json
{
    "success": false,
    "message": "Terjadi kesalahan pada server",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

## 4. Cara Mendapatkan dan Memakai Token

1. `POST /register` (sekali) untuk membuat akun.
2. `POST /login` -> salin nilai `data.token`.
3. Kirim pada endpoint terproteksi: `Authorization: Bearer <token>`.
4. Token berlaku 300 detik. Setelah itu response `401` ("Token tidak valid atau kedaluwarsa"); login ulang untuk token baru.

## 5. Detail Endpoint

### 5.1 Autentikasi

#### 1. `POST /register`

- **Controller:** `AuthController::register()`
- **Autentikasi:** Publik (tidak perlu token)
- **Fungsi:** Mendaftarkan pengguna baru. Role selalu `user` (input `role` dari client diabaikan).

**Headers:**

| Header | Nilai | Keterangan |
|---|---|---|
| `Content-Type` | `application/json` | Body dikirim sebagai JSON |

**Request body:**

| Field | Tipe | Wajib | Aturan |
|---|---|---|---|
| `username` | string | Ya | 3-50 karakter; hanya huruf, angka, titik, garis bawah; harus unik |
| `email` | string | Ya | Format email valid (`filter_var`), maks 100 karakter, harus unik |
| `password` | string | Ya | 8-72 karakter. Disimpan sebagai hash, tidak pernah dikembalikan |

```json
{
    "username": "andi_saputra",
    "email": "andi@example.com",
    "password": "rahasia123"
}
```

**Urutan pemeriksaan:**

1. `400` - body bukan JSON valid atau ada field yang kosong
2. `422` - format data tidak valid (email, panjang password, karakter username)
3. `409` - username atau email sudah terdaftar
4. `201` - password di-hash dengan `password_hash()`, data disimpan

**Contoh response:**

*Sukses - `201 Created` - Registrasi berhasil*

```json
{
    "success": true,
    "message": "Registrasi berhasil",
    "data": {
        "id": 1,
        "username": "andi_saputra",
        "email": "andi@example.com",
        "role": "user"
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `400 Bad Request` - Field wajib kosong*

```json
{
    "success": false,
    "message": "Username, email, dan password wajib diisi",
    "data": null,
    "errors": {
        "password": "Field password wajib diisi"
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `405 Method Not Allowed` - Method selain POST*

(header `Allow: POST`)

```json
{
    "success": false,
    "message": "Method tidak diizinkan, gunakan POST",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `409 Conflict` - Username sudah dipakai*

```json
{
    "success": false,
    "message": "Username atau email sudah terdaftar",
    "data": null,
    "errors": {
        "username": "Username sudah dipakai"
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `422 Unprocessable Entity` - Email tidak valid*

```json
{
    "success": false,
    "message": "Data tidak lolos validasi",
    "data": null,
    "errors": {
        "email": "Format email tidak valid"
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

**Contoh curl:**

```bash
curl -X POST "http://localhost/sistem-informasi-akademik/public/register" \
  -H "Content-Type: application/json" \
  -d '{"username": "andi_saputra", "email": "andi@example.com", "password": "rahasia123"}'
```

#### 2. `POST /login`

- **Controller:** `AuthController::login()`
- **Autentikasi:** Publik (tidak perlu token)
- **Fungsi:** Memverifikasi kredensial dan menerbitkan token JWT (HS256) yang berlaku 300 detik (5 menit).

**Headers:**

| Header | Nilai | Keterangan |
|---|---|---|
| `Content-Type` | `application/json` | Body dikirim sebagai JSON |

**Request body:**

| Field | Tipe | Wajib | Aturan |
|---|---|---|---|
| `username` | string | Ya | Username hasil registrasi |
| `password` | string | Ya | Password akun |

```json
{
    "username": "andi_saputra",
    "password": "rahasia123"
}
```

**Urutan pemeriksaan:**

1. `400` - body bukan JSON valid atau username/password kosong
2. `401` - username tidak ada **atau** password salah (pesan sengaja sama)
3. `200` - token diterbitkan

**Contoh response:**

*Sukses - `200 OK` - Login berhasil*

```json
{
    "success": true,
    "message": "Login berhasil",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MSwicm9sZSI6InV...(dipersingkat)",
        "token_type": "Bearer",
        "expires_in": 300,
        "role": "user"
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `400 Bad Request` - Field wajib kosong*

```json
{
    "success": false,
    "message": "Username dan password wajib diisi",
    "data": null,
    "errors": {
        "password": "Field password wajib diisi"
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `401 Unauthorized` - Username atau password salah*

```json
{
    "success": false,
    "message": "Username atau password salah",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

**Contoh curl:**

```bash
curl -X POST "http://localhost/sistem-informasi-akademik/public/login" \
  -H "Content-Type: application/json" \
  -d '{"username": "andi_saputra", "password": "rahasia123"}'
```

### 5.2 Mahasiswa

#### 3. `GET /mahasiswa`

- **Controller:** `MahasiswaController::index()`
- **Autentikasi:** Publik (tidak perlu token)
- **Fungsi:** Menampilkan seluruh data mahasiswa (terbaru lebih dulu).

**Headers:** tidak ada yang wajib.

**Urutan pemeriksaan:**

1. `200` - daftar data (bisa kosong `[]`)

**Contoh response:**

*Sukses - `200 OK` - Semua data (publik)*

```json
{
    "success": true,
    "message": "Data berhasil diambil",
    "data": [
        {
            "id": 2,
            "nim": "220100102",
            "nama": "Rina Wulandari",
            "prodi": "Teknik Informatika",
            "created_at": "2026-10-24 06:40:00"
        },
        {
            "id": 1,
            "nim": "220100101",
            "nama": "Andi Saputra",
            "prodi": "Teknik Informatika",
            "created_at": "2026-10-24 06:40:00"
        }
    ],
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

**Contoh curl:**

```bash
curl -X GET "http://localhost/sistem-informasi-akademik/public/mahasiswa"
```

#### 4. `GET /mahasiswa?id={id}`

- **Controller:** `MahasiswaController::index()`
- **Autentikasi:** Publik (tidak perlu token)
- **Fungsi:** Menampilkan satu mahasiswa berdasarkan ID (method yang sama dengan daftar, dibedakan oleh `?id=`).

**Headers:** tidak ada yang wajib.

**Query parameter:**

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `id` | integer | Ya | ID data; angka positif (contoh `?id=1`) |

**Urutan pemeriksaan:**

1. `400` - `id` bukan angka positif
2. `404` - ID tidak ditemukan
3. `200` - data ditemukan

**Contoh response:**

*Sukses - `200 OK` - Satu data*

```json
{
    "success": true,
    "message": "Data berhasil diambil",
    "data": {
        "id": 1,
        "nim": "220100101",
        "nama": "Andi Saputra",
        "prodi": "Teknik Informatika",
        "created_at": "2026-10-24 06:40:00"
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `400 Bad Request` - ID bukan angka*

```json
{
    "success": false,
    "message": "Parameter id wajib berupa angka positif",
    "data": null,
    "errors": {
        "id": "ID harus berupa angka positif, contoh: ?id=1"
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `404 Not Found` - ID tidak ditemukan*

```json
{
    "success": false,
    "message": "Data tidak ditemukan",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

**Contoh curl:**

```bash
curl -X GET "http://localhost/sistem-informasi-akademik/public/mahasiswa?id=1"
```

#### 5. `POST /mahasiswa`

- **Controller:** `MahasiswaController::store()`
- **Autentikasi:** **Wajib** - header `Authorization: Bearer <token>` (`Auth::requireAuth()`)
- **Fungsi:** Menambahkan mahasiswa baru.

**Headers:**

| Header | Nilai | Keterangan |
|---|---|---|
| `Content-Type` | `application/json` | Body dikirim sebagai JSON |
| `Authorization` | `Bearer <token>` | Token dari `POST /login` (berlaku 5 menit) |

**Request body:**

| Field | Tipe | Wajib | Aturan |
|---|---|---|---|
| `nim` | string | Ya | Tepat 9 karakter; harus unik |
| `nama` | string | Ya | Maks 100 karakter |
| `prodi` | string | Ya | Maks 50 karakter |

```json
{
    "nim": "220100101",
    "nama": "Andi Saputra",
    "prodi": "Teknik Informatika"
}
```

**Urutan pemeriksaan:**

1. `401` - token tidak ada / tidak valid / kedaluwarsa (dicek paling awal oleh `Auth::requireAuth()`)
2. `400` - body bukan JSON valid atau ada field yang kosong
3. `422` - NIM bukan 9 karakter, atau nama/prodi terlalu panjang
4. `409` - NIM sudah terdaftar
5. `201` - data disimpan

**Contoh response:**

*Sukses - `201 Created` - Data berhasil ditambahkan*

```json
{
    "success": true,
    "message": "Data berhasil ditambahkan",
    "data": {
        "id": 1
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `400 Bad Request` - Field wajib kosong*

```json
{
    "success": false,
    "message": "Field nim, nama, dan prodi wajib diisi",
    "data": null,
    "errors": {
        "nim": "Field nim wajib diisi"
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `401 Unauthorized` - Tanpa token*

(header `WWW-Authenticate: Bearer`)

```json
{
    "success": false,
    "message": "Token tidak ditemukan",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `401 Unauthorized` - Token tidak valid atau kedaluwarsa*

(header `WWW-Authenticate: Bearer error="invalid_token"`)

```json
{
    "success": false,
    "message": "Token tidak valid atau kedaluwarsa",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `409 Conflict` - NIM sudah terdaftar*

```json
{
    "success": false,
    "message": "NIM sudah terdaftar",
    "data": null,
    "errors": {
        "nim": "NIM sudah dipakai mahasiswa lain"
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `422 Unprocessable Entity` - NIM bukan 9 karakter*

```json
{
    "success": false,
    "message": "Data tidak lolos validasi",
    "data": null,
    "errors": {
        "nim": "Format NIM tidak valid, harus 9 digit"
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

**Contoh curl:**

```bash
curl -X POST "http://localhost/sistem-informasi-akademik/public/mahasiswa" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"nim": "220100101", "nama": "Andi Saputra", "prodi": "Teknik Informatika"}'
```

#### 6. `PUT /mahasiswa?id={id}`

- **Controller:** `MahasiswaController::update()`
- **Autentikasi:** **Wajib** - header `Authorization: Bearer <token>` (`Auth::requireAuth()`)
- **Fungsi:** Mengubah seluruh data mahasiswa (semua field wajib dikirim).

**Headers:**

| Header | Nilai | Keterangan |
|---|---|---|
| `Content-Type` | `application/json` | Body dikirim sebagai JSON |
| `Authorization` | `Bearer <token>` | Token dari `POST /login` (berlaku 5 menit) |

**Query parameter:**

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `id` | integer | Ya | ID data; angka positif (contoh `?id=1`) |

**Request body:**

| Field | Tipe | Wajib | Aturan |
|---|---|---|---|
| `nim` | string | Ya | Tepat 9 karakter; harus unik |
| `nama` | string | Ya | Maks 100 karakter |
| `prodi` | string | Ya | Maks 50 karakter |

```json
{
    "nim": "220100101",
    "nama": "Andi S",
    "prodi": "Teknik Informatika"
}
```

**Urutan pemeriksaan:**

1. `401` - token tidak ada / tidak valid / kedaluwarsa
2. `400` - `id` bukan angka positif
3. `404` - ID tidak ditemukan
4. `400` / `422` - body tidak valid (sama seperti POST)
5. `409` - NIM dipakai mahasiswa lain (NIM milik data itu sendiri boleh dikirim ulang)
6. `200` - data diperbarui

**Contoh response:**

*Sukses - `200 OK` - Data berhasil diperbarui*

```json
{
    "success": true,
    "message": "Data berhasil diperbarui",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `401 Unauthorized` - Tanpa token*

(header `WWW-Authenticate: Bearer`)

```json
{
    "success": false,
    "message": "Token tidak ditemukan",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `404 Not Found` - ID tidak ditemukan*

```json
{
    "success": false,
    "message": "Data tidak ditemukan",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `409 Conflict` - NIM dipakai mahasiswa lain*

```json
{
    "success": false,
    "message": "NIM sudah dipakai mahasiswa lain",
    "data": null,
    "errors": {
        "nim": "NIM sudah dipakai mahasiswa lain"
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

**Contoh curl:**

```bash
curl -X PUT "http://localhost/sistem-informasi-akademik/public/mahasiswa?id=1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"nim": "220100101", "nama": "Andi S", "prodi": "Teknik Informatika"}'
```

#### 7. `DELETE /mahasiswa?id={id}`

- **Controller:** `MahasiswaController::destroy()`
- **Autentikasi:** **Wajib** - header `Authorization: Bearer <token>` (`Auth::requireAuth()`)
- **Fungsi:** Menghapus mahasiswa berdasarkan ID.

**Headers:**

| Header | Nilai | Keterangan |
|---|---|---|
| `Authorization` | `Bearer <token>` | Token dari `POST /login` (berlaku 5 menit) |

**Query parameter:**

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `id` | integer | Ya | ID data; angka positif (contoh `?id=1`) |

**Urutan pemeriksaan:**

1. `401` - token tidak ada / tidak valid / kedaluwarsa
2. `400` - `id` bukan angka positif
3. `404` - ID tidak ditemukan
4. `200` - data dihapus

**Contoh response:**

*Sukses - `200 OK` - Data berhasil dihapus*

```json
{
    "success": true,
    "message": "Data berhasil dihapus",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `401 Unauthorized` - Tanpa token*

(header `WWW-Authenticate: Bearer`)

```json
{
    "success": false,
    "message": "Token tidak ditemukan",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `404 Not Found` - ID tidak ditemukan*

```json
{
    "success": false,
    "message": "Data tidak ditemukan",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

**Contoh curl:**

```bash
curl -X DELETE "http://localhost/sistem-informasi-akademik/public/mahasiswa?id=1" \
  -H "Authorization: Bearer <token>"
```

### 5.3 Buku

#### 8. `GET /buku`

- **Controller:** `BukuController::index()`
- **Autentikasi:** Publik (tidak perlu token)
- **Fungsi:** Menampilkan seluruh data buku.

**Headers:** tidak ada yang wajib.

**Urutan pemeriksaan:**

1. `200` - daftar data

**Contoh response:**

*Sukses - `200 OK` - Semua data (publik)*

```json
{
    "success": true,
    "message": "Data berhasil diambil",
    "data": [
        {
            "id": 1,
            "judul": "Belajar PHP Native",
            "penulis": "Budi Santoso",
            "tahun": 2023,
            "created_at": "2026-10-24 06:40:00"
        }
    ],
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

**Contoh curl:**

```bash
curl -X GET "http://localhost/sistem-informasi-akademik/public/buku"
```

#### 9. `GET /buku?id={id}`

- **Controller:** `BukuController::index()`
- **Autentikasi:** Publik (tidak perlu token)
- **Fungsi:** Menampilkan satu buku berdasarkan ID (method yang sama dengan daftar, dibedakan oleh `?id=`).

**Headers:** tidak ada yang wajib.

**Query parameter:**

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `id` | integer | Ya | ID data; angka positif (contoh `?id=1`) |

**Urutan pemeriksaan:**

1. `400` - `id` bukan angka positif
2. `404` - ID tidak ditemukan
3. `200` - data ditemukan

**Contoh response:**

*Sukses - `200 OK` - Satu data*

```json
{
    "success": true,
    "message": "Data berhasil diambil",
    "data": {
        "id": 1,
        "judul": "Belajar PHP Native",
        "penulis": "Budi Santoso",
        "tahun": 2023,
        "created_at": "2026-10-24 06:40:00"
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `404 Not Found` - ID tidak ditemukan*

```json
{
    "success": false,
    "message": "Data tidak ditemukan",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

**Contoh curl:**

```bash
curl -X GET "http://localhost/sistem-informasi-akademik/public/buku?id=1"
```

#### 10. `POST /buku`

- **Controller:** `BukuController::store()`
- **Autentikasi:** **Wajib** - header `Authorization: Bearer <token>` (`Auth::requireAuth()`)
- **Fungsi:** Menambahkan buku baru.

**Headers:**

| Header | Nilai | Keterangan |
|---|---|---|
| `Content-Type` | `application/json` | Body dikirim sebagai JSON |
| `Authorization` | `Bearer <token>` | Token dari `POST /login` (berlaku 5 menit) |

**Request body:**

| Field | Tipe | Wajib | Aturan |
|---|---|---|---|
| `judul` | string | Ya | Maks 150 karakter |
| `penulis` | string | Ya | Maks 100 karakter |
| `tahun` | integer | Ya | Angka 4 digit, 1000 sampai tahun depan |

```json
{
    "judul": "Belajar PHP Native",
    "penulis": "Budi Santoso",
    "tahun": 2023
}
```

**Urutan pemeriksaan:**

1. `401` - token tidak valid
2. `400` - body bukan JSON valid atau ada field kosong
3. `422` - tahun tidak valid, atau judul/penulis terlalu panjang
4. `201` - data disimpan

**Contoh response:**

*Sukses - `201 Created` - Data berhasil ditambahkan*

```json
{
    "success": true,
    "message": "Data berhasil ditambahkan",
    "data": {
        "id": 1
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `401 Unauthorized` - Tanpa token*

(header `WWW-Authenticate: Bearer`)

```json
{
    "success": false,
    "message": "Token tidak ditemukan",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `422 Unprocessable Entity` - Tahun tidak valid*

```json
{
    "success": false,
    "message": "Data tidak lolos validasi",
    "data": null,
    "errors": {
        "tahun": "Tahun harus berupa angka 4 digit yang valid"
    },
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

**Contoh curl:**

```bash
curl -X POST "http://localhost/sistem-informasi-akademik/public/buku" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"judul": "Belajar PHP Native", "penulis": "Budi Santoso", "tahun": 2023}'
```

#### 11. `PUT /buku?id={id}`

- **Controller:** `BukuController::update()`
- **Autentikasi:** **Wajib** - header `Authorization: Bearer <token>` (`Auth::requireAuth()`)
- **Fungsi:** Mengubah seluruh data buku.

**Headers:**

| Header | Nilai | Keterangan |
|---|---|---|
| `Content-Type` | `application/json` | Body dikirim sebagai JSON |
| `Authorization` | `Bearer <token>` | Token dari `POST /login` (berlaku 5 menit) |

**Query parameter:**

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `id` | integer | Ya | ID data; angka positif (contoh `?id=1`) |

**Request body:**

| Field | Tipe | Wajib | Aturan |
|---|---|---|---|
| `judul` | string | Ya | Maks 150 karakter |
| `penulis` | string | Ya | Maks 100 karakter |
| `tahun` | integer | Ya | Angka 4 digit, 1000 sampai tahun depan |

```json
{
    "judul": "Belajar PHP Native (Edisi 2)",
    "penulis": "Budi Santoso",
    "tahun": 2024
}
```

**Urutan pemeriksaan:**

1. `401` - token tidak valid
2. `400` - `id` tidak valid
3. `404` - ID tidak ditemukan
4. `400` / `422` - body tidak valid
5. `200` - data diperbarui

**Contoh response:**

*Sukses - `200 OK` - Data berhasil diperbarui*

```json
{
    "success": true,
    "message": "Data berhasil diperbarui",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `401 Unauthorized` - Tanpa token*

(header `WWW-Authenticate: Bearer`)

```json
{
    "success": false,
    "message": "Token tidak ditemukan",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `404 Not Found` - ID tidak ditemukan*

```json
{
    "success": false,
    "message": "Data tidak ditemukan",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

**Contoh curl:**

```bash
curl -X PUT "http://localhost/sistem-informasi-akademik/public/buku?id=1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"judul": "Belajar PHP Native (Edisi 2)", "penulis": "Budi Santoso", "tahun": 2024}'
```

#### 12. `DELETE /buku?id={id}`

- **Controller:** `BukuController::destroy()`
- **Autentikasi:** **Wajib** - header `Authorization: Bearer <token>` (`Auth::requireAuth()`)
- **Fungsi:** Menghapus buku berdasarkan ID.

**Headers:**

| Header | Nilai | Keterangan |
|---|---|---|
| `Authorization` | `Bearer <token>` | Token dari `POST /login` (berlaku 5 menit) |

**Query parameter:**

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `id` | integer | Ya | ID data; angka positif (contoh `?id=1`) |

**Urutan pemeriksaan:**

1. `401` - token tidak valid
2. `400` - `id` tidak valid
3. `404` - ID tidak ditemukan
4. `200` - data dihapus

**Contoh response:**

*Sukses - `200 OK` - Data berhasil dihapus*

```json
{
    "success": true,
    "message": "Data berhasil dihapus",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `401 Unauthorized` - Tanpa token*

(header `WWW-Authenticate: Bearer`)

```json
{
    "success": false,
    "message": "Token tidak ditemukan",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*Gagal - `404 Not Found` - ID tidak ditemukan*

```json
{
    "success": false,
    "message": "Data tidak ditemukan",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

**Contoh curl:**

```bash
curl -X DELETE "http://localhost/sistem-informasi-akademik/public/buku?id=1" \
  -H "Authorization: Bearer <token>"
```

## 6. Response Umum Router

*`405 Method Not Allowed` - Method tidak diizinkan (PATCH)* (`PATCH /mahasiswa?id=1`)

(header `Allow: GET, POST, PUT, DELETE`)

```json
{
    "success": false,
    "message": "Method tidak diizinkan",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

*`404 Not Found` - Endpoint tidak dikenal* (`GET /dosen`)

```json
{
    "success": false,
    "message": "Endpoint tidak ditemukan",
    "data": null,
    "timestamp": "2026-10-24T06:40:00+07:00"
}
```

## 7. Dokumentasi di Postman

Collection `API_Sistem_Informasi_Akademik_MVC.postman_collection.json` memuat setiap endpoint di atas. Tiap request berisi **Description** (Controller, method, kebutuhan autentikasi) dan **Examples** (contoh response sukses dan gagal). Postman menghasilkan tampilan dokumentasi dari keduanya lewat **View Documentation**.
