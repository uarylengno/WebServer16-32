<?php
// Tugas mandiri no. 1: controller untuk resource 'buku'.
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
                $this->json(404, ['success' => false, 'message' => 'Data tidak ditemukan']);
            }
            $this->json(200, ['success' => true, 'data' => $data]);
        }

        $this->json(200, ['success' => true, 'data' => $this->model->getAll()]);
    }

    public function store(): void {
        $data = $this->validated($this->readJson());
        $id = $this->model->create($data);
        $this->json(201, ['success' => true, 'message' => 'Data berhasil ditambahkan', 'data' => ['id' => (int) $id]]);
    }

    public function update($id): void {
        $id = $this->requireId($id);
        if (!$this->model->find($id)) {
            $this->json(404, ['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $this->model->update($id, $this->validated($this->readJson()));
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

    /** Validasi judul, penulis (wajib) dan tahun (angka 4 digit, 1000 s.d. tahun depan). */
    private function validated(array $in): array {
        $judul   = isset($in['judul'])   && is_scalar($in['judul'])   ? trim((string) $in['judul'])   : '';
        $penulis = isset($in['penulis']) && is_scalar($in['penulis']) ? trim((string) $in['penulis']) : '';
        $tahun   = isset($in['tahun'])   && is_scalar($in['tahun'])   ? trim((string) $in['tahun'])   : '';

        if ($judul === '' || $penulis === '' || $tahun === '') {
            $this->json(400, ['success' => false, 'message' => 'Field judul, penulis, dan tahun wajib diisi']);
        }
        if (!ctype_digit($tahun) || (int) $tahun < 1000 || (int) $tahun > (int) date('Y') + 1) {
            $this->json(400, ['success' => false, 'message' => 'Tahun harus berupa angka 4 digit yang valid']);
        }
        if (mb_strlen($judul) > 150 || mb_strlen($penulis) > 100) {
            $this->json(400, ['success' => false, 'message' => 'Judul maksimal 150 karakter, penulis maksimal 100 karakter']);
        }

        return ['judul' => $judul, 'penulis' => $penulis, 'tahun' => (int) $tahun];
    }
}
