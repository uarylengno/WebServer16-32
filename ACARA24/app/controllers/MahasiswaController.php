<?php
class MahasiswaController extends BaseController {
    private Mahasiswa $model;

    public function __construct() {
        $this->model = new Mahasiswa();
    }

    // GET tetap publik (untuk kebutuhan pengujian pada acara berikutnya)
    public function index(): void {
        $id = $_GET['id'] ?? null;

        if ($id !== null && $id !== '') {
            $data = $this->model->find($this->requireId($id));
            if (!$data) {
                Response::json(404, false, 'Data tidak ditemukan');
            }
            Response::json(200, true, 'Data berhasil diambil', $data);
        }

        Response::json(200, true, 'Data berhasil diambil', $this->model->getAll());
    }

    // POST, PUT, DELETE dilindungi: Auth::requireAuth() dipanggil pertama, sebelum membaca body / menyentuh database.
    public function store(): void {
        Auth::requireAuth();

        $data = $this->validated($this->readJson());

        if ($this->model->nimExists($data['nim'])) {
            Response::json(409, false, 'NIM sudah terdaftar', null, ['nim' => 'NIM sudah dipakai mahasiswa lain']);
        }

        $id = $this->model->create($data);
        Response::json(201, true, 'Data berhasil ditambahkan', ['id' => (int) $id]);
    }

    public function update($id): void {
        Auth::requireAuth();

        $id = $this->requireId($id);
        if (!$this->model->find($id)) {
            Response::json(404, false, 'Data tidak ditemukan');
        }

        $data = $this->validated($this->readJson());

        if ($this->model->nimExists($data['nim'], $id)) {
            Response::json(409, false, 'NIM sudah dipakai mahasiswa lain', null, ['nim' => 'NIM sudah dipakai mahasiswa lain']);
        }

        $this->model->update($id, $data);
        Response::json(200, true, 'Data berhasil diperbarui');
    }

    public function destroy($id): void {
        Auth::requireAuth();

        $id = $this->requireId($id);
        if (!$this->model->find($id)) {
            Response::json(404, false, 'Data tidak ditemukan');
        }

        $this->model->delete($id);
        Response::json(200, true, 'Data berhasil dihapus');
    }

    /**
     * Validasi dua tahap:
     *  1) 400 Bad Request          : field wajib kosong / tidak dikirim
     *  2) 422 Unprocessable Entity : JSON lengkap, tetapi isinya gagal validasi (format NIM, panjang)
     */
    private function validated(array $in): array {
        $data = [];
        foreach (['nim', 'nama', 'prodi'] as $field) {
            $data[$field] = isset($in[$field]) && is_scalar($in[$field]) ? trim((string) $in[$field]) : '';
        }

        $missing = [];
        foreach ($data as $field => $value) {
            if ($value === '') {
                $missing[$field] = "Field $field wajib diisi";
            }
        }
        if (!empty($missing)) {
            Response::json(400, false, 'Field nim, nama, dan prodi wajib diisi', null, $missing);
        }

        $errors = [];
        // Modul: NIM harus tepat 9 karakter. Sengaja tidak dibatasi angka saja
        // karena NIM Polije dapat memuat huruf, mis. E41250317.
        if (strlen($data['nim']) !== 9) {
            $errors['nim'] = 'Format NIM tidak valid, harus 9 digit';
        }
        if (mb_strlen($data['nama']) > 100) {
            $errors['nama'] = 'Nama maksimal 100 karakter';
        }
        if (mb_strlen($data['prodi']) > 50) {
            $errors['prodi'] = 'Prodi maksimal 50 karakter';
        }
        if (!empty($errors)) {
            Response::json(422, false, 'Data tidak lolos validasi', null, $errors);
        }

        return $data;
    }
}
