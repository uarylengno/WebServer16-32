<?php
// Front Controller: satu-satunya pintu masuk untuk seluruh request API.

// Acara 24: header CORS dipasang satu kali di sini, sebelum Router dijalankan,
// sehingga berlaku otomatis untuk seluruh resource (mahasiswa, buku, login, register),
// termasuk response error (404, 405, 500) karena header sudah terpasang lebih dulu.
// Tanda '*' mengizinkan semua origin: cukup untuk workshop. Saat deployment, ganti dengan origin frontend
// yang sebenarnya, mis. 'https://frontend.contoh.ac.id'.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight: sebelum PUT/DELETE, atau request ber-header Authorization / Content-Type: application/json,
// dari origin lain, browser lebih dulu mengirim OPTIONS. Jawab 204 tanpa body dan berhenti di sini,
// supaya tidak sampai ke Router (yang akan membalas 405 sehingga browser membatalkan request aslinya).
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Max-Age: 600');
    http_response_code(204);
    exit;
}

header_remove('X-Powered-By'); // jangan membuka versi PHP
define('PUBLIC_DIR', __DIR__); // folder document root; dipakai untuk menentukan lokasi uploads/
require_once __DIR__ . '/../app/core/autoload.php';
require_once __DIR__ . '/../app/config/app.php'; // mendefinisikan APP_ENV

// Jangan bocorkan error PHP mentah ke client (merusak format JSON).
ini_set('display_errors', '0');

try {
    (new Router())->dispatch();
} catch (Throwable $e) {
    // Mis. query gagal; detail hanya ke log, client mendapat pesan umum (graceful failure).
    error_log($e->getMessage());
    Response::json(500, false, 'Terjadi kesalahan pada server');
}
