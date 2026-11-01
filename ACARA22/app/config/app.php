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
