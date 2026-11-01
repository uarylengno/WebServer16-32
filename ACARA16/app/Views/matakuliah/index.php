<h1 class="mb-4">Data Mata Kuliah</h1>
<table class="table table-bordered align-middle">
    <thead class="table-dark">
        <tr>
            <th>Kode</th>
            <th>Nama Mata Kuliah</th>
            <th>SKS</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($matakuliah)): ?>
            <tr>
                <td colspan="3" class="text-center text-muted">Tidak ada data.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($matakuliah as $mk): ?>
                <tr>
                    <td><?= htmlspecialchars($mk['kode'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($mk['nama'] ?? '-') ?></td>
                    <td><?= htmlspecialchars((string)($mk['sks'] ?? '-')) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
