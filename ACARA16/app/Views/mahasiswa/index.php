<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 m-0">Data Mahasiswa</h1>
    <a href="<?= BASE_URL ?>/mahasiswa/create" class="btn btn-primary">+ Tambah Mahasiswa</a>
</div>

<form action="<?= BASE_URL ?>/mahasiswa" method="GET" class="d-flex gap-2 mb-3" style="max-width: 400px;">
    <input type="text" name="keyword" class="form-control" placeholder="Cari nama atau NIM..." value="<?= htmlspecialchars($keyword ?? '') ?>">
    <button type="submit" class="btn btn-secondary">Cari</button>
</form>

<table class="table table-bordered align-middle">
    <thead class="table-dark">
        <tr>
            <th>NIM</th>
            <th>Nama</th>
            <th>Email</th>
            <th>Prodi</th>
            <th>Angkatan</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($mahasiswa)): ?>
            <tr>
                <td colspan="6" class="text-center text-muted">Tidak ada data ditemukan.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($mahasiswa as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m->getNim()) ?></td>
                    <td><?= htmlspecialchars($m->getNama()) ?></td>
                    <td><?= htmlspecialchars($m->getEmail()) ?></td>
                    <td><?= htmlspecialchars($m->getProdiNama() ?? '-') ?></td>
                    <td><?= htmlspecialchars((string)$m->getAngkatan()) ?></td>
                    <td class="d-flex gap-2">
                        <a href="<?= BASE_URL ?>/mahasiswa/<?= $m->getId() ?>/edit" class="btn btn-sm btn-warning">Edit</a>
                        <form action="<?= BASE_URL ?>/mahasiswa/<?= $m->getId() ?>/delete" method="POST"
                              onsubmit="return confirm('Yakin mau menghapus data ini?');">
                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
