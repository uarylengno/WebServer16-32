<?php
/**
 * MahasiswaController.
 * Acara 26: upload foto (multipart/form-data) pada store() dan update(), foto lama dihapus dengan unlink()
 * saat diganti (tugas mandiri 1) atau saat data dihapus. Request JSON biasa (tanpa foto) tetap didukung.
 */
class MahasiswaController extends BaseController {
    /** Batas ukuran foto (modul: 2MB). Batas upload_max_filesize di php.ini juga harus minimal 2M. */
    private const FOTO_MAKS_BYTE = 2 * 1024 * 1024;
    /** Ekstensi yang diterima dari nama berkas asli. */
    private const FOTO_EKSTENSI = ['jpg', 'jpeg', 'png'];
    /** Tipe gambar yang diterima menurut isi berkas (signature), dan ekstensi yang dipakai untuk nama baru. */
    private const FOTO_TIPE = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];

    private Mahasiswa $model;
    /** Nama berkas yang sudah dipindahkan ke uploads/ tetapi belum dipastikan tersimpan di database. */
    private ?string $fotoBaru = null;

    public function __construct() {
        $this->model = new Mahasiswa();
        // Jika proses berhenti di tengah jalan (mis. insert gagal -> 409/500), berkas yatim ikut dihapus.
        register_shutdown_function([$this, 'bersihkanFotoYatim']);
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

    // POST, PUT, DELETE hanya untuk admin (Acara 25): Auth::requireAdmin() dipanggil pertama, sebelum membaca body / menyentuh database.
    // Tanpa token / token tidak valid -> 401. Token sah tetapi role bukan admin -> 403.
    public function store(): void {
        Auth::requireAdmin();

        $data = $this->validated($this->bacaInput());
        $foto = $this->validasiFoto();

        if ($this->model->nimExists($data['nim'])) {
            Response::json(409, false, 'NIM sudah terdaftar', null, ['nim' => 'NIM sudah dipakai mahasiswa lain']);
        }

        // Berkas baru dipindahkan setelah SEMUA validasi lolos, supaya request yang ditolak tidak meninggalkan berkas.
        if ($foto !== null) {
            $data['foto'] = $this->simpanFoto($foto);
        }

        $id = $this->model->create($data);
        $this->fotoBaru = null; // data sudah tersimpan di database, berkas bukan yatim
        Response::json(201, true, 'Data berhasil ditambahkan', ['id' => (int) $id, 'foto' => $data['foto'] ?? null]);
    }

    /**
     * Mengubah data. Dikirim sebagai JSON (PUT, tanpa foto) atau sebagai multipart lewat
     * POST /mahasiswa?id=N&_method=PUT (dengan atau tanpa foto baru).
     */
    public function update($id): void {
        Auth::requireAdmin();

        $id = $this->requireId($id);
        $lama = $this->model->find($id);
        if (!$lama) {
            Response::json(404, false, 'Data tidak ditemukan');
        }

        $data = $this->validated($this->bacaInput());
        $foto = $this->validasiFoto();

        if ($this->model->nimExists($data['nim'], $id)) {
            Response::json(409, false, 'NIM sudah dipakai mahasiswa lain', null, ['nim' => 'NIM sudah dipakai mahasiswa lain']);
        }

        if ($foto !== null) {
            $data['foto'] = $this->simpanFoto($foto);
        }

        $this->model->update($id, $data);
        $this->fotoBaru = null;

        // Tugas mandiri 1: foto lama dihapus dengan unlink() hanya setelah update ke database berhasil
        // dan hanya bila memang ada foto baru. Update tanpa foto baru membiarkan foto lama.
        if ($foto !== null) {
            $this->hapusBerkasFoto($lama['foto'] ?? null);
        }

        Response::json(200, true, 'Data berhasil diperbarui', [
            'id'   => $id,
            'foto' => $data['foto'] ?? ($lama['foto'] ?? null),
        ]);
    }

    public function destroy($id): void {
        Auth::requireAdmin();

        $id = $this->requireId($id);
        $lama = $this->model->find($id);
        if (!$lama) {
            Response::json(404, false, 'Data tidak ditemukan');
        }

        $this->model->delete($id);
        $this->hapusBerkasFoto($lama['foto'] ?? null); // data sudah hilang, berkas fotonya ikut dibersihkan
        Response::json(200, true, 'Data berhasil dihapus');
    }

    // ---------------------------------------------------------------- input

    /**
     * Sumber data request: multipart/form-data dibaca dari $_POST, selain itu dari body JSON.
     */
    private function bacaInput(): array {
        $tipe = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
        if (strpos($tipe, 'multipart/form-data') !== 0) {
            return $this->readJson();
        }

        // PHP tidak mengurai body multipart untuk method selain POST, sehingga isinya hilang begitu saja.
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            Response::json(
                400, false, 'Untuk mengirim berkas gunakan POST, mis. POST /mahasiswa?id=1&_method=PUT',
                null, ['foto' => 'Method PUT tidak dapat membawa berkas multipart']
            );
        }

        // Jika ukuran total request melebihi post_max_size, PHP membuang seluruh body ($_POST dan $_FILES kosong).
        if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            Response::json(422, false, 'Ukuran berkas maksimal 2MB', null, ['foto' => 'Ukuran berkas maksimal 2MB']);
        }

        return $_POST;
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

    // ---------------------------------------------------------------- foto

    private function folderUpload(): string {
        // Acara 28: PUBLIC_DIR (didefinisikan public/index.php) = folder yang benar-benar dilayani web server.
        // Lokal: .../public ; hosting cPanel: .../public_html (app/ sejajar dengan public_html, bukan dengan public/).
        // Tanpa ini, foto tersimpan di folder di luar document root dan tidak dapat ditampilkan.
        $dasar = defined('PUBLIC_DIR') ? PUBLIC_DIR : __DIR__ . '/../../public';
        return rtrim($dasar, '/\\') . '/uploads/';
    }

    /**
     * Memeriksa berkas $_FILES['foto'] tanpa memindahkannya.
     * Mengembalikan null bila tidak ada berkas yang dikirim, atau ['tmp' => ..., 'ext' => 'jpg|png'] bila valid.
     * Berkas tidak valid menghentikan request dengan 422.
     */
    private function validasiFoto(): ?array {
        if (!isset($_FILES['foto'])) {
            return null; // foto bersifat opsional
        }
        $file = $_FILES['foto'];

        $salahTipe = function (): void {
            Response::json(422, false, 'Berkas harus berupa gambar JPG/PNG yang valid', null, ['foto' => 'Berkas harus berupa gambar JPG/PNG yang valid']);
        };
        $terlaluBesar = function (): void {
            Response::json(422, false, 'Ukuran berkas maksimal 2MB', null, ['foto' => 'Ukuran berkas maksimal 2MB']);
        };

        // foto[] (banyak berkas) menghasilkan struktur array di dalam array; hanya satu berkas yang diterima.
        if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
            $salahTipe();
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return null; // input file dikirim tetapi kosong (tidak ada berkas dipilih)
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $terlaluBesar();
                break;
            case UPLOAD_ERR_PARTIAL:
                Response::json(400, false, 'Berkas tidak terunggah sempurna, silakan coba lagi', null, ['foto' => 'Unggahan terputus']);
                break;
            default: // folder sementara hilang / gagal menulis ke disk: masalah server, bukan kesalahan pengguna
                error_log('[Upload] kode error ' . $file['error']);
                Response::json(500, false, 'Terjadi kesalahan pada server');
        }

        // Berkas harus benar-benar hasil upload HTTP (bukan path sembarang yang disisipkan lewat request).
        if (!is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $salahTipe();
        }

        // Ekstensi saja tidak cukup (mudah dipalsukan): isi berkas diperiksa dengan exif_imagetype() (membaca signature).
        $ekstensi = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $tipe = @exif_imagetype($file['tmp_name']);
        if (!in_array($ekstensi, self::FOTO_EKSTENSI, true) || $tipe === false || !isset(self::FOTO_TIPE[$tipe])) {
            $salahTipe();
        }

        // Ukuran diperiksa dari berkas sebenarnya, bukan hanya angka yang dikirim client.
        if ($file['size'] > self::FOTO_MAKS_BYTE || filesize($file['tmp_name']) > self::FOTO_MAKS_BYTE) {
            $terlaluBesar();
        }

        // Ekstensi nama baru mengikuti isi berkas (jpeg disatukan menjadi jpg), bukan ekstensi kiriman pengguna.
        return ['tmp' => $file['tmp_name'], 'ext' => self::FOTO_TIPE[$tipe]];
    }

    /** Memindahkan berkas ke public/uploads/ dengan nama unik; mengembalikan nama berkas barunya. */
    private function simpanFoto(array $foto): string {
        $folder = $this->folderUpload();
        if (!is_dir($folder) && !@mkdir($folder, 0755, true) && !is_dir($folder)) {
            error_log('[Upload] folder uploads tidak dapat dibuat: ' . $folder);
            Response::json(500, false, 'Terjadi kesalahan pada server');
        }

        $namaBaru = uniqid('foto_', true) . '.' . $foto['ext']; // unik: berkas pengguna berbeda tidak saling menimpa
        if (!move_uploaded_file($foto['tmp'], $folder . $namaBaru)) {
            error_log('[Upload] move_uploaded_file gagal ke ' . $folder . $namaBaru);
            Response::json(500, false, 'Terjadi kesalahan pada server');
        }

        $this->fotoBaru = $namaBaru;
        return $namaBaru;
    }

    /**
     * unlink() berkas foto. Nama berkas berasal dari database, tetapi tetap divalidasi polanya
     * agar nilai yang tidak wajar (mis. '../../app/config/Database.php') tidak pernah menghapus berkas lain.
     */
    private function hapusBerkasFoto($nama): void {
        if (!is_string($nama) || !preg_match('/^foto_[A-Za-z0-9.]+\.(jpg|png)$/', $nama)) {
            return;
        }
        $path = $this->folderUpload() . $nama;
        if (is_file($path) && !@unlink($path)) {
            error_log('[Upload] unlink gagal: ' . $path);
        }
    }

    /** Dipanggil saat skrip berakhir: hapus berkas yang sudah dipindahkan tetapi datanya gagal disimpan. */
    public function bersihkanFotoYatim(): void {
        if ($this->fotoBaru !== null) {
            $this->hapusBerkasFoto($this->fotoBaru);
            $this->fotoBaru = null;
        }
    }
}
