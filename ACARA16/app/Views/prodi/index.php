<h1 class="mb-4">Data Program Studi</h1>
<table class="table table-bordered align-middle">
    <thead class="table-dark">
        <tr>
            <th>Kode</th>
            <th>Nama Prodi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($prodi)): ?>
            <tr>
                <td colspan="2" class="text-center text-muted">Tidak ada data.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($prodi as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['kode'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['nama']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
