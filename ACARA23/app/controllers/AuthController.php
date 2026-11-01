<?php
/**
 * Controller autentikasi: register() (Acara 19) dan login() (Acara 20).
 * Bukan pola CRUD standar, dipanggil Router langsung per aksi.
 */
class AuthController extends BaseController {
    // Hash palsu: dipakai agar waktu respons login tetap mirip saat username tidak ada,
    // sehingga penyerang tidak bisa menebak username mana yang terdaftar dari lamanya respons.
    private const DUMMY_HASH = '$2y$10$4l0vGNYd7btt.0ZrJAmehOe04DF9pPXBhxoUp15vOu6kJ5w.FQToa';

    private User $model;

    public function __construct() {
        $this->model = new User();
    }

    public function register(): void {
        $in = $this->readJson();

        $data = [];
        foreach (['username', 'email', 'password'] as $field) {
            $value = $in[$field] ?? '';
            // password tidak di-trim: spasi di awal/akhir bisa jadi bagian dari password
            $data[$field] = is_scalar($value) ? ($field === 'password' ? (string) $value : trim((string) $value)) : '';
        }

        // 400: field wajib kosong / tidak dikirim
        $missing = [];
        foreach ($data as $field => $value) {
            if ($value === '') {
                $missing[$field] = "Field $field wajib diisi";
            }
        }
        if (!empty($missing)) {
            Response::json(400, false, 'Username, email, dan password wajib diisi', null, $missing);
        }

        // 422: format data tidak valid (termasuk email via filter_var di Model User)
        $errors = $this->model->validate($data);
        if (!empty($errors)) {
            Response::json(422, false, 'Data tidak lolos validasi', null, $errors);
        }

        // 409: username / email sudah terdaftar
        $conflict = [];
        if ($this->model->findByUsername($data['username'])) {
            $conflict['username'] = 'Username sudah dipakai';
        }
        if ($this->model->findByEmail($data['email'])) {
            $conflict['email'] = 'Email sudah terdaftar';
        }
        if (!empty($conflict)) {
            Response::json(409, false, 'Username atau email sudah terdaftar', null, $conflict);
        }

        // Password TIDAK PERNAH disimpan sebagai teks biasa
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        try {
            $id = $this->model->create($data);
        } catch (PDOException $e) {
            // Dua request bersamaan dengan data sama: constraint UNIQUE di database menolak yang kedua.
            if ($e->getCode() === '23000') {
                Response::json(409, false, 'Username atau email sudah terdaftar');
            }
            throw $e;
        }

        // Hash password tidak ikut dikembalikan ke client. Role selalu 'user' (default tabel).
        Response::json(201, true, 'Registrasi berhasil', [
            'id'       => (int) $id,
            'username' => $data['username'],
            'email'    => $data['email'],
            'role'     => 'user',
        ]);
    }

    public function login(): void {
        $in = $this->readJson();

        $username = isset($in['username']) && is_scalar($in['username']) ? trim((string) $in['username']) : '';
        $password = isset($in['password']) && is_scalar($in['password']) ? (string) $in['password'] : '';

        // 400: field tidak dikirim / kosong
        $missing = [];
        if ($username === '') {
            $missing['username'] = 'Field username wajib diisi';
        }
        if ($password === '') {
            $missing['password'] = 'Field password wajib diisi';
        }
        if (!empty($missing)) {
            Response::json(400, false, 'Username dan password wajib diisi', null, $missing);
        }

        $user = $this->model->findByUsername($username);

        // password_verify selalu dijalankan (pada hash palsu bila user tidak ada).
        $valid = password_verify($password, $user['password'] ?? self::DUMMY_HASH);

        // 401: pesan sengaja sama untuk "user tidak ada" dan "password salah"
        if (!$user || !$valid) {
            Response::json(401, false, 'Username atau password salah');
        }

        $token = Auth::generateToken(['id' => (int) $user['id'], 'role' => $user['role']]);

        Response::json(200, true, 'Login berhasil', [
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::TOKEN_TTL,
            'role'       => $user['role'],
        ]);
    }
}
