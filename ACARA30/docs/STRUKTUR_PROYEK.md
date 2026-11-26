# Struktur Akhir Proyek MVC — Sistem Informasi Akademik (Acara 17 sampai 30)

## 1. Struktur folder (setelah deployment: `app/` di luar `public_html/`)
```
/home/NAMACPANEL/
├── app/                              <- TIDAK terjangkau browser
│   ├── .htaccess                     Require all denied (Acara 30)
│   ├── config/
│   │   ├── Database.php              koneksi PDO (kredensial server)
│   │   ├── app.php                   APP_ENV, zona waktu, display_errors Off + log (Acara 30)
│   │   └── secret.local.php          kunci JWT khusus server (tidak masuk Git)
│   ├── core/
│   │   ├── autoload.php              pemuat kelas otomatis
│   │   ├── Router.php                memetakan URL + method ke Controller
│   │   ├── BaseController.php        respons dan pembacaan input bersama
│   │   ├── Response.php              format JSON seragam {success, message, data, timestamp}
│   │   └── Auth.php                  JWT HS256, requireAuth, requireAdmin (RBAC)
│   ├── models/                       akses data (PDO prepared statement)
│   │   ├── Mahasiswa.php   ├── Buku.php   └── User.php
│   ├── controllers/                  logika request/response
│   │   ├── MahasiswaController.php   ├── BukuController.php   └── AuthController.php
│   └── logs/                         php_errors.log (Acara 30), dijaga .htaccess
└── public_html/                      <- document root (isi folder public/)
    ├── index.php                     Front Controller + CORS + preflight
    ├── .htaccess                     rewrite ke index.php, HTTPS, header keamanan
    ├── .user.ini                     batas upload, display_errors Off
    ├── uploads/                      foto mahasiswa (+ .htaccess penolak eksekusi skrip)
    └── views/                        View: HTML + Bootstrap + JavaScript (fetch)
        ├── login.html / login.js     ├── index.html / app.js (daftar publik)
        ├── dashboard.html / .js      ├── form_tambah.* / form_edit.* / form_umum.js
        └── common.js                 BASE_URL, Sesi, panggilApi, Notifikasi, Validasi, escapeHTML, retry
```

## 2. Alur satu request (MVC)
```
Browser (View)            Front Controller        Router            Controller            Model            MySQL
   |  fetch GET /mahasiswa      |                     |                  |                    |                |
   |--------------------------->| index.php           |                  |                    |                |
   |                            |-- CORS/preflight -->|                  |                    |                |
   |                            |                     |-- resolve ------>| MahasiswaController|                |
   |                            |                     |                  |-- Auth::require* ->|                |
   |                            |                     |                  |-- Model::all() --->|-- SELECT ----->|
   |                            |                     |                  |<-- data -----------|<---------------|
   |<-- JSON {success,data} ----|<--------------------|<-- Response ---- |                    |                |
```

## 3. Alur autentikasi (JWT)
```
1. POST /login {username,password}  -> AuthController -> User::findByUsername -> password_verify
2. Server: Auth::generateToken(id, role) -> JWT HS256 (exp 300 detik, kunci dari secret.local.php)
3. View: Sesi.simpan(token, role, username) di localStorage (role hanya untuk tampilan)
4. Setiap request: header Authorization: Bearer <token> (ditambahkan panggilApi)
5. Server: Auth::validateToken memeriksa tanda tangan dan exp -> 401 bila tidak sah
6. Aksi tulis (POST/PUT/DELETE mahasiswa): Auth::requireAdmin membaca role dari TOKEN (bukan dari browser) -> 403 bila bukan admin
7. 401 pada View -> sesi dihapus, pengguna dialihkan ke login
```

## 4. Peta acara
| Acara | Hasil utama |
|-------|-------------|
| 17-18 | Fondasi MVC, Front Controller, Router, API CRUD mahasiswa, Response seragam |
| 19-20 | Validasi, status code, resource buku, dokumentasi awal |
| 21-22 | Rancangan basis data, relasi dan migrasi |
| 23 | Pengujian blackbox (26 test case) dengan Postman |
| 24 | CORS, preflight, View dengan fetch |
| 25 | Autentikasi JWT, login, dashboard, RBAC |
| 26 | Upload foto, backup dan restore |
| 27 | Form Bootstrap, toast, validasi sisi View |
| 28 | Deployment cPanel: paket produksi, kunci JWT khusus server, SQL hosting |
| 29 | Loading state, escaping XSS, retry |
| 30 | HTTPS, display_errors Off + log, app/.htaccess, checklist akhir, disaster recovery |
