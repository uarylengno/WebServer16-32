<?php
// Tugas mandiri no. 2: konfigurasi mode aplikasi.
//   'development' -> response JSON dibuat rapi (JSON_PRETTY_PRINT) agar mudah dibaca saat debugging
//   'production'  -> response JSON ringkas (tanpa spasi/indentasi), lebih hemat bandwidth
//
// Ganti nilai default di bawah sebelum deployment (Acara 28 dan 30),
// atau set environment variable APP_ENV pada server.
// Zona waktu untuk field "timestamp" pada response (date('c')).
date_default_timezone_set('Asia/Jakarta');

if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'development');
}

// Acara 30 (Langkah 3): penguatan konfigurasi PHP.
// display_errors = Off : pesan error PHP (path server, potongan query) tidak pernah dikirim ke pengguna dan tidak
//                        merusak format JSON. log_errors = On : kesalahan tetap tercatat untuk ditelusuri pengembang.
// Berkas log ada di app/logs/ (di luar folder web; app/.htaccess dan app/logs/.htaccess menolak akses langsung).
// Bila folder itu tidak dapat ditulis, PHP memakai log bawaan server (mis. error_log di cPanel).
// Pengaturan yang sama dapat diberikan lewat cPanel > MultiPHP INI Editor (display_errors, log_errors, error_log);
// kode ini menjamin nilainya benar walaupun INI Editor tidak tersedia.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
$dirLogAplikasi = dirname(__DIR__) . '/logs';
if (!is_dir($dirLogAplikasi)) {
    @mkdir($dirLogAplikasi, 0750, true);
}
if (is_dir($dirLogAplikasi) && is_writable($dirLogAplikasi)) {
    ini_set('error_log', $dirLogAplikasi . '/php_errors.log');
}
unset($dirLogAplikasi);
