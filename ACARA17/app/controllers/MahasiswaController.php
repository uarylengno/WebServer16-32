<?php
class MahasiswaController extends BaseController {
    private Mahasiswa $model;

    public function __construct() {
        $this->model = new Mahasiswa();
    }

    public function index(): void {
        $id = $_GET['id'] ?? null;

        if ($id !== null && $id !== '') {
            $data = $this->model->find($this->requireId($id));
            if (!$data) {
                $this->json(404, ['success' => false, 'message' => 'Data tidak ditemukan']);
            }
            $this->json(200, ['success' => true, 'data' => $data]);
        }

        $this->json(200, ['success' => true, 'data' => $this->model->getAll()]);
    }

    public function store(): void {
        $data = $this->clean($this->readJson());

        if ($data['nim'] === '' || $data['nama'] === '' || $data['prodi'] === '') {
            $this->json(400, ['success' => false, 'message' => 'Field nim, nama, dan prodi wajib diisi']);
        }
        if (strlen($data['nim']) > 15) {
            $this->json(400, ['success' => false, 'message' => 'NIM maksimal 15 karakter']);
        }
        // Tugas mandiri no. 2: NIM tidak boleh duplikat
        if ($this->model->nimExists($data['nim'])) {
            $this->json(409, ['success' => false, 'message' => 'NIM sudah terdaftar']);
        }

        $id = $this->model->create($data);
        $this->json(201, ['success' => true, 'message' => 'Data berhasil ditambahkan', 'data' => ['id' => (int) $id]]);
    }

    public function update($id): void {
        $id = $this->requireId($id);
        if (!$this->model->find($id)) {
            $this->json(404, ['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $data = $this->clean($this->readJson());
        if ($data['nim'] === '' || $data['nama'] === '' || $data['prodi'] === '') {
            $this->json(400, ['success' => false, 'message' => 'Field nim, nama, dan prodi wajib diisi']);
        }
        if (strlen($data['nim']) > 15) {
            $this->json(400, ['success' => false, 'message' => 'NIM maksimal 15 karakter']);
        }
        if ($this->model->nimExists($data['nim'], $id)) {
            $this->json(409, ['success' => false, 'message' => 'NIM sudah dipakai mahasiswa lain']);
        }

        $this->model->update($id, $data);
        $this->json(200, ['success' => true, 'message' => 'Data berhasil diperbarui']);
    }

    public function destroy($id): void {
        $id = $this->requireId($id);
        if (!$this->model->find($id)) {
            $this->json(404, ['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $this->model->delete($id);
        $this->json(200, ['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    /** Ambil hanya field yang dipakai dan rapikan spasi. */
    private function clean(array $in): array {
        $out = [];
        foreach (['nim', 'nama', 'prodi'] as $f) {
            $out[$f] = isset($in[$f]) && is_scalar($in[$f]) ? trim((string) $in[$f]) : '';
        }
        return $out;
    }
}
