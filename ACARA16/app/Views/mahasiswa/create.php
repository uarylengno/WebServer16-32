<h1 class="mb-4">Tambah Mahasiswa</h1>
<form action="<?= BASE_URL ?>/mahasiswa/store" method="POST">
    <div class="mb-3">
        <label class="form-label">NIM</label>
        <input type="text" name="nim" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Nama</label>
        <input type="text" name="nama" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Prodi</label>
        <select name="prodi_id" class="form-select" required>
            <option value="">-- Pilih Prodi --</option>
            <?php foreach ($prodi as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Angkatan</label>
        <input type="number" name="angkatan" class="form-control" value="<?= date('Y') ?>" required>
    </div>
    <div class="d-flex justify-content-end gap-2">
        <a href="<?= BASE_URL ?>/mahasiswa" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </div>
</form>
