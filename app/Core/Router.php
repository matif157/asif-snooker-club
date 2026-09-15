<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    private array $middleware = [];

    public function get(string $path, callable|array $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }

    public function post(string $path, callable|array $handler): self
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }

    public function put(string $path, callable|array $handler): self
    {
        return $this->post($path, $handler);
    }

    public function delete(string $path, callable|array $handler): self
    {
        return $this->post($path, $handler);
    }

    public function middleware(callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function group(string $prefix, callable $callback): void
    {
        $existingRoutes = $this->routes;
        $router = new self();

        $callback($router);

        foreach ($router->routes as $method => $routes) {
            foreach ($routes as $path => $handler) {
                $this->routes[$method][$prefix . $path] = $handler;
            }
        }
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $method = strtoupper($method);

        // Support method override for PUT/DELETE from forms
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = rtrim($path, '/') ?: '/';

        // Handle /public prefix (for dev server runs from root)
        if (str_starts_with($path, '/public/')) {
            $path = substr($path, strlen('/public'));
            $path = rtrim($path, '/') ?: '/';
        }

        // Exact match
        if (isset($this->routes[$method][$path])) {
            return $this->call($this->routes[$method][$path]);
        }

        // Parameterized match
        foreach (($this->routes[$method] ?? []) as $route => $handler) {
            $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $route);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return $this->call($handler, $params);
            }
        }

        http_response_code(404);

        if (str_starts_with($path, '/api/')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            return null;
        }

        echo '<h1>404 — Page Not Found</h1>';
        echo '<p>The page you requested does not exist.</p>';
        echo '<p><a href="/">Go back to dashboard</a></p>';
        return null;
    }

    private function call(callable|array $handler, array $params = []): mixed
    {
        // Run middleware chain if registered
        foreach ($this->middleware as $middleware) {
            $result = $middleware();
            if ($result === false) {
                return null;
            }
        }

        // Cast numeric route params to native types
        $args = array_map(function (string $value): mixed {
            if (ctype_digit($value)) {
                return (int) $value;
            }
            if (is_numeric($value)) {
                return (float) $value;
            }
            return $value;
        }, $params);

        if (is_array($handler)) {
            [$class, $method] = $handler;

            if (!class_exists($class)) {
                throw new \RuntimeException("Controller class '{$class}' not found");
            }

            $controller = new $class();
            return $controller->{$method}(...array_values($args));
        }

        return $handler(...array_values($args));
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }
}