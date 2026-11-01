<?php
class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Tugas mandiri no. 1: format email divalidasi dengan filter_var(FILTER_VALIDATE_EMAIL).
     */
    public static function isValidEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validasi data registrasi (sebelum password di-hash).
     * Mengembalikan array kesalahan per field; array kosong berarti data valid.
     */
    public function validate(array $data): array {
        $errors = [];

        $username = (string) ($data['username'] ?? '');
        if (strlen($username) < 3 || strlen($username) > 50) {
            $errors['username'] = 'Username harus 3 sampai 50 karakter';
        } elseif (!preg_match('/^[A-Za-z0-9_.]+$/', $username)) {
            $errors['username'] = 'Username hanya boleh berisi huruf, angka, titik, dan garis bawah';
        }

        $email = (string) ($data['email'] ?? '');
        if (strlen($email) > 100) {
            $errors['email'] = 'Email maksimal 100 karakter';
        } elseif (!self::isValidEmail($email)) {
            $errors['email'] = 'Format email tidak valid';
        }

        $password = (string) ($data['password'] ?? '');
        if (strlen($password) < 8) {
            $errors['password'] = 'Password minimal 8 karakter';
        } elseif (strlen($password) > 72) {
            // bcrypt (PASSWORD_DEFAULT) hanya memakai 72 byte pertama; tolak agar tidak terpotong diam-diam.
            $errors['password'] = 'Password maksimal 72 karakter';
        }

        return $errors;
    }

    /**
     * Simpan pengguna baru. $data['password'] harus sudah berupa hash dari password_hash().
     * Kolom role tidak diisi dari input, sehingga selalu memakai default 'user'.
     *
     * @throws InvalidArgumentException jika email tidak valid (pengaman kedua sebelum data disimpan)
     */
    public function create(array $data): string {
        if (!self::isValidEmail((string) ($data['email'] ?? ''))) {
            throw new InvalidArgumentException('Format email tidak valid');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password) VALUES (:u, :e, :p)'
        );
        $stmt->execute([':u' => $data['username'], ':e' => $data['email'], ':p' => $data['password']]);
        return $this->db->lastInsertId();
    }

    public function findByUsername(string $username) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :u');
        $stmt->execute([':u' => $username]);
        return $stmt->fetch();
    }

    public function findByEmail(string $email) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :e');
        $stmt->execute([':e' => $email]);
        return $stmt->fetch();
    }
}
