<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $pattern, array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function put(string $pattern, array $handler): void
    {
        $this->add('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, array $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    private function add(string $method, string $pattern, array $handler): void
    {
        $this->routes[$method][] = [
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $uri, string $method): void
    {
        $uri = '/' . trim($uri, '/');
        $method = strtoupper($method);

        // Dukungan method override untuk form HTML biasa (hanya kenal GET/POST)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper($_POST['_method']);
            if (in_array($override, ['PUT', 'DELETE'], true)) {
                $method = $override;
            }
        }

        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            if ($route['pattern'] === '/') {
                $regex = '#^/$#';
            } else {
                $regex = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $route['pattern']);
                $regex = '#^' . rtrim($regex, '/') . '$#';
            }

            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches);
                [$controllerInstance, $methodName] = $route['handler'];
                call_user_func_array([$controllerInstance, $methodName], $matches);
                return;
            }
        }

        http_response_code(404);
        echo '404 - Halaman Tidak Ditemukan';
    }
}
