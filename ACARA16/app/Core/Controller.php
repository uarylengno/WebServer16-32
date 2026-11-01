<?php

namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data);
        $content = __DIR__ . '/../Views/' . $view . '.php';
        require __DIR__ . '/../Views/layouts/' . $layout . '.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . $path);
        exit;
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }
}
