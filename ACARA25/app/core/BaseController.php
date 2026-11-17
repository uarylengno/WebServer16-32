<?php
/**
 * Helper kecil untuk semua Controller.
 * Pembentukan response sepenuhnya didelegasikan ke class Response (Acara 18).
 */
abstract class BaseController {

    /** Baca body JSON; berhenti dengan 400 jika kosong / bukan JSON object. */
    protected function readJson(): array {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        if (!is_array($data)) {
            Response::json(400, false, 'Body harus berupa JSON yang valid');
        }
        return $data;
    }

    /** Validasi ID dari URL; berhenti dengan 400 jika tidak ada / bukan angka positif. */
    protected function requireId($id): int {
        // is_string: ?id[]=1 membuat $id berupa array; tanpa pemeriksaan ini (string) $id memicu PHP Warning (Acara 23, TC26).
        if (!is_string($id) || $id === '' || !ctype_digit($id) || (int) $id < 1) {
            Response::json(
                400, false, 'Parameter id wajib berupa angka positif',
                null, ['id' => 'ID harus berupa angka positif, contoh: ?id=1']
            );
        }
        return (int) $id;
    }
}
