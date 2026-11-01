<?php
// Front Controller: satu-satunya pintu masuk untuk seluruh request API.
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
