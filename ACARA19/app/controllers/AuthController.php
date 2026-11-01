<?php
/**
 * Controller pertama yang tidak mengikuti pola CRUD (index/store/update/destroy):
 * hanya berisi aksi register(). Aksi login() ditambahkan pada Acara 20.
 */
class AuthController extends BaseController {
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
}
