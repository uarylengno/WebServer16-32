<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SI Akademik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark navbar-expand">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL ?>/mahasiswa">SI Akademik</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="<?= BASE_URL ?>/mahasiswa">Mahasiswa</a>
                <a class="nav-link" href="<?= BASE_URL ?>/prodi">Prodi</a>
                <a class="nav-link" href="<?= BASE_URL ?>/matakuliah">Mata Kuliah</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php require __DIR__ . '/../partials/flash.php'; ?>
        <?php require $content; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
