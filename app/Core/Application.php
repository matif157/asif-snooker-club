<?php

declare(strict_types=1);

namespace App\Core;

class Application
{
    public function run(): void
    {
        // Load environment variables
        load_env(ROOT_PATH . '/.env');

        // Set timezone
        date_default_timezone_set((string) config('app.timezone', 'Asia/Karachi'));

        // Start session
        Session::start();

        // Register routes
        $router = new Router();
        require ROOT_PATH . '/config/routes.php';

        // Dispatch
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';

        $router->dispatch($method, $uri);
    }
}