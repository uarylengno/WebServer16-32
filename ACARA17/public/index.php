<?php
// Front Controller: satu-satunya pintu masuk untuk seluruh request API.
require_once __DIR__ . '/../app/core/autoload.php';

// Jangan bocorkan error PHP mentah ke client (merusak format JSON).
ini_set('display_errors', '0');

try {
    (new Router())->dispatch();
} catch (Throwable $e) {
    // Mis. query gagal karena pelanggaran constraint; detail tidak ditampilkan ke client.
    error_log($e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server']);
}
