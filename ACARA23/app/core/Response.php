<?php
/**
 * Response: satu-satunya tempat pembentukan response JSON API.
 *
 * Format (response envelope) yang seragam:
 * {
 *   "success":   true/false,
 *   "message":   "pesan untuk manusia",
 *   "data":      ... atau null,
 *   "errors":    { "field": "pesan" }   <- hanya muncul bila ada kesalahan validasi
 *   "timestamp": "2026-10-22T18:20:00+07:00"
 * }
 */
class Response {

    /**
     * @param int    $httpCode kode status HTTP (200, 201, 400, 404, 422, ...)
     * @param bool   $success  true jika berhasil
     * @param string $message  pesan ringkas
     * @param mixed  $data     data hasil (array/objek) atau null
     * @param array  $errors   daftar kesalahan per field, mis. ['nim' => 'NIM wajib diisi'] (tugas mandiri no. 1)
     */
    public static function json(int $httpCode, bool $success, string $message, $data = null, array $errors = []): void {
        $body = [
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ];

        // Field errors hanya disertakan bila ada, supaya response sukses tetap ringkas.
        if (!empty($errors)) {
            $body['errors'] = $errors;
        }

        $body['timestamp'] = date('c');

        // Tugas mandiri no. 2: pretty print hanya pada mode development.
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if (self::isDevelopment()) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $json = json_encode($body, $flags);

        // Graceful failure: jika data tidak bisa di-encode (mis. UTF-8 rusak), jangan kirim body kosong.
        if ($json === false) {
            $httpCode = 500;
            $json = json_encode([
                'success'   => false,
                'message'   => 'Gagal menyusun response JSON',
                'data'      => null,
                'timestamp' => date('c'),
            ], $flags);
        }

        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo $json;
        exit;
    }

    private static function isDevelopment(): bool {
        return defined('APP_ENV') && APP_ENV === 'development';
    }
}
