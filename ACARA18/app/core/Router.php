<?php
class Router {
    // Peta sederhana: nama resource pada URL => nama class Controller
    private array $map = [
        'mahasiswa' => 'MahasiswaController',
        'buku'      => 'BukuController',
    ];

    public function dispatch(): void {
        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $segments = $uri === '' ? [] : explode('/', $uri);

        // Cari nama resource dari ujung URL, sehingga tetap jalan di folder
        // mana pun proyek ditaruh (mis. /sistem-informasi-akademik/public/mahasiswa).
        $controllerName = null;
        $last = end($segments);
        if ($last !== false && isset($this->map[$last])) {
            $controllerName = $this->map[$last];
        }

        if (!$controllerName) {
            Response::json(404, false, 'Endpoint tidak ditemukan');
        }

        $controller = new $controllerName();

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':    $controller->index(); break;
            case 'POST':   $controller->store(); break;
            case 'PUT':    $controller->update($_GET['id'] ?? null); break;
            case 'DELETE': $controller->destroy($_GET['id'] ?? null); break;
            default:
                header('Allow: GET, POST, PUT, DELETE');
                Response::json(405, false, 'Method tidak diizinkan');
        }
    }
}
