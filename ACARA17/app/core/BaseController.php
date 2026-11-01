<?php
/**
 * Helper kecil untuk semua Controller (response masih sederhana;
 * akan dirapikan menjadi class Response pada Acara 18).
 */
abstract class BaseController {
    protected function json(int $status, array $body): void {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($body, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Baca body JSON; berhenti dengan 400 jika kosong / bukan JSON object. */
    protected function readJson(): array {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        if (!is_array($data)) {
            $this->json(400, ['success' => false, 'message' => 'Body harus berupa JSON yang valid']);
        }
        return $data;
    }

    /** Validasi ID dari URL; berhenti dengan 400 jika tidak ada / bukan angka positif. */
    protected function requireId($id): int {
        if ($id === null || !ctype_digit((string) $id) || (int) $id < 1) {
            $this->json(400, ['success' => false, 'message' => 'Parameter id wajib berupa angka positif']);
        }
        return (int) $id;
    }
}
