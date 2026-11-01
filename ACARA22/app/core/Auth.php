<?php
/**
 * Auth: JWT HS256 buatan sendiri (tanpa Composer / pustaka eksternal) + middleware autentikasi.
 *
 * Token = base64url(header) . base64url(payload) . base64url(signature)
 * signature = HMAC-SHA256("header.payload", SECRET)
 */
class Auth {
    // Kunci penandatangan token. GANTI sebelum deployment / sebelum di-push ke GitHub publik.
    // Dapat ditimpa tanpa mengubah kode lewat environment variable JWT_SECRET.
    const SECRET = 'kunci_rahasia_workshop_2026';

    // Tugas mandiri no. 2: masa berlaku token diperpendek dari 3600 detik (1 jam) menjadi 300 detik (5 menit).
    const TOKEN_TTL = 300;

    private static function secret(): string {
        $env = getenv('JWT_SECRET');
        return ($env !== false && $env !== '') ? $env : self::SECRET;
    }

    private static function base64url(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /** Kebalikan base64url(): kembalikan '-_' ke '+/', tambahkan padding '=', lalu decode (false jika rusak). */
    private static function base64urlDecode(string $data) {
        $b64 = strtr($data, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad) {
            $b64 .= str_repeat('=', 4 - $pad);
        }
        return base64_decode($b64, true);
    }

    /**
     * Membuat token. $ttl (detik) opsional, default TOKEN_TTL; nilai negatif menghasilkan token
     * yang sudah kedaluwarsa (berguna untuk pengujian).
     */
    public static function generateToken(array $payload, ?int $ttl = null): string {
        $header = self::base64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + ($ttl ?? self::TOKEN_TTL);
        $payloadEnc = self::base64url(json_encode($payload));
        $signature  = self::base64url(hash_hmac('sha256', "$header.$payloadEnc", self::secret(), true));
        return "$header.$payloadEnc.$signature";
    }

    /** Mengembalikan payload (array) jika token sah dan belum kedaluwarsa, selain itu false. */
    public static function validateToken(string $token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        [$header, $payload, $signature] = $parts;

        // 1) Keaslian: tanda tangan dihitung ulang dan dibandingkan dengan hash_equals (tahan timing attack)
        $valid = self::base64url(hash_hmac('sha256', "$header.$payload", self::secret(), true));
        if (!hash_equals($valid, $signature)) {
            return false;
        }

        // 2) Algoritma pada header harus HS256
        $headerData = json_decode((string) self::base64urlDecode($header), true);
        if (!is_array($headerData) || ($headerData['alg'] ?? '') !== 'HS256') {
            return false;
        }

        // 3) Payload harus berupa array dan punya exp yang belum lewat
        $data = json_decode((string) self::base64urlDecode($payload), true);
        if (!is_array($data) || !isset($data['exp']) || !is_int($data['exp']) || $data['exp'] < time()) {
            return false;
        }

        return $data;
    }

    /** Membaca header Authorization (nama header tidak peka huruf besar/kecil). */
    private static function authorizationHeader(): string {
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    return (string) $value;
                }
            }
        }
        // Cadangan bila Apache/FastCGI tidak meneruskan lewat getallheaders() (lihat public/.htaccess)
        return (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    }

    /**
     * Middleware autentikasi: panggil di baris pertama method yang dilindungi.
     * Menghentikan proses dengan 401 jika token tidak ada / tidak valid / kedaluwarsa.
     * Jika lolos, mengembalikan payload token (mis. ['id' => 1, 'role' => 'user', ...]).
     */
    public static function requireAuth(): array {
        $authHeader = self::authorizationHeader();

        if (!preg_match('/^Bearer\s+(\S+)$/i', trim($authHeader), $m)) {
            header('WWW-Authenticate: Bearer');
            Response::json(401, false, 'Token tidak ditemukan');
        }

        $payload = self::validateToken($m[1]);
        if (!$payload) {
            header('WWW-Authenticate: Bearer error="invalid_token"');
            Response::json(401, false, 'Token tidak valid atau kedaluwarsa');
        }

        return $payload;
    }
}
