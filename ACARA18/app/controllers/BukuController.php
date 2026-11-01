<?php
// Resource 'buku' (tugas mandiri Acara 17), kini memakai Response (Acara 18).
class BukuController extends BaseController {
    private Buku $model;

    public function __construct() {
        $this->model = new Buku();
    }

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

    public function store(): void {
        $data = $this->validated($this->readJson());
        $id = $this->model->create($data);
        Response::json(201, true, 'Data berhasil ditambahkan', ['id' => (int) $id]);
    }

    public function update($id): void {
        $id = $this->requireId($id);
        if (!$this->model->find($id)) {
            Response::json(404, false, 'Data tidak ditemukan');
        }

        $this->model->update($id, $this->validated($this->readJson()));
        Response::json(200, true, 'Data berhasil diperbarui');
    }

    public function destroy($id): void {
        $id = $this->requireId($id);
        if (!$this->model->find($id)) {
            Response::json(404, false, 'Data tidak ditemukan');
        }

        $this->model->delete($id);
        Response::json(200, true, 'Data berhasil dihapus');
    }

    /**
     * 400 : field wajib kosong
     * 422 : tahun bukan angka 4 digit yang valid, atau judul/penulis terlalu panjang
     */
    private function validated(array $in): array {
        $data = [];
        foreach (['judul', 'penulis', 'tahun'] as $field) {
            $data[$field] = isset($in[$field]) && is_scalar($in[$field]) ? trim((string) $in[$field]) : '';
        }

        $missing = [];
        foreach ($data as $field => $value) {
            if ($value === '') {
                $missing[$field] = "Field $field wajib diisi";
            }
        }
        if (!empty($missing)) {
            Response::json(400, false, 'Field judul, penulis, dan tahun wajib diisi', null, $missing);
        }

        $errors = [];
        if (!ctype_digit($data['tahun']) || (int) $data['tahun'] < 1000 || (int) $data['tahun'] > (int) date('Y') + 1) {
            $errors['tahun'] = 'Tahun harus berupa angka 4 digit yang valid';
        }
        if (mb_strlen($data['judul']) > 150) {
            $errors['judul'] = 'Judul maksimal 150 karakter';
        }
        if (mb_strlen($data['penulis']) > 100) {
            $errors['penulis'] = 'Penulis maksimal 100 karakter';
        }
        if (!empty($errors)) {
            Response::json(422, false, 'Data tidak lolos validasi', null, $errors);
        }

        $data['tahun'] = (int) $data['tahun'];
        return $data;
    }
}
