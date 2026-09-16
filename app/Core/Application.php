<?php

declare(strict_types=1);

namespace App\Core;

class Application
{
    private array $authExempt = ['/login', '/install', '/api/auth/login', '/portal', '/portal/login', '/portal/logout', '/portal/bookings'];

    public function run(): void
    {
        load_env(ROOT_PATH . '/.env');

        date_default_timezone_set((string) config('app.timezone', 'Asia/Karachi'));

        Session::start();

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $path   = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path   = rtrim($path, '/') ?: '/';

        // Auth check — skip exempt routes and assets
        if (!in_array($path, $this->authExempt) && !str_starts_with($path, '/assets/')) {
            if (!is_authenticated()) {
                if (str_starts_with($path, '/api/')) {
                    http_response_code(401);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Please login']);
                    return;
                }
                header('Location: ' . url('/login'));
                return;
            }
        }

        // CSRF check for POST
        if ($method === 'POST' && !str_starts_with($path, '/api/')) {
            $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!verify_csrf($token)) {
                http_response_code(419);
                if (Request::isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'CSRF token expired. Please refresh.']);
                    return;
                }
                echo 'Session expired. <a href="/">Go back</a>';
                return;
            }
        }

        $router = new Router();
        require ROOT_PATH . '/config/routes.php';

        $router->dispatch($method, $uri);
    }
}