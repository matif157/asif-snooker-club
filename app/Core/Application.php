<?php

declare(strict_types=1);

namespace App\Core;

class Application
{
    private array $authExempt = ['/login', '/install', '/api/auth/login', '/portal', '/portal/login', '/portal/logout', '/portal/bookings'];

    private static string $logFilePath = '';

    public function run(): void
    {
        load_env(ROOT_PATH . '/.env');

        date_default_timezone_set((string) config('app.timezone', 'Asia/Karachi'));

        self::configureErrorHandling();
        self::sendSecurityHeaders();

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

        try {
            $router->dispatch($method, $uri);
        } catch (\Throwable $e) {
            self::renderErrorResponse($e);
        }
    }

    private static function configureErrorHandling(): void
    {
        error_reporting(E_ALL);

        if ((bool) config('app.debug')) {
            return;
        }

        self::$logFilePath = ROOT_PATH . '/storage/logs/app-' . date('Y-m-d') . '.log';
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        register_shutdown_function(function (): void {
            $err = error_get_last();
            if ($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::logLine(sprintf('[FATAL] %s in %s:%d', $err['message'], $err['file'], $err['line']));
                if (headers_sent()) {
                    return;
                }
                http_response_code(500);
                if (self::isJsonRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Something went wrong on the server.']);
                } else {
                    header('Content-Type: text/html; charset=utf-8');
                    echo self::errorPageHtml('500', 'Something went wrong', 'An unexpected error occurred on the server. Please try again.');
                }
            }
        });
    }

    private static function sendSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Frame-Options: DENY');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; img-src 'self' data: blob: http: https:; media-src 'self' blob: http: https:; connect-src 'self' ws: wss: http: https:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none';");
        if (self::isSecureRequest()) {
            header('Strict-Transport-Security: max-age=63072000');
        }
    }

    private static function renderErrorResponse(\Throwable $e): void
    {
        if ((bool) config('app.debug')) {
            throw $e;
        }

        self::logLine(sprintf('[%s] %s in %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
        http_response_code(500);
        if (self::isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Something went wrong on the server.']);
            return;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo self::errorPageHtml('500', 'Something went wrong', 'An unexpected error occurred on the server. Please try again.');
    }

    private static function isJsonRequest(): bool
    {
        return str_starts_with((string) ($_SERVER['REQUEST_URI'] ?? ''), '/api/')
            || Request::isAjax();
    }

    private static function isSecureRequest(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    private static function logLine(string $line): void
    {
        if (self::$logFilePath === '') {
            return;
        }
        @file_put_contents(self::$logFilePath, '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private static function errorPageHtml(string $code, string $title, string $message): string
    {
        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>' . $code . ' — ' . $title . '</title>'
            . '<style>body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;margin:0;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;height:100vh}'
            . '.box{text-align:center;padding:24px}.code{font-size:72px;font-weight:800;color:#f59e0b;line-height:1}.msg{font-size:18px}'
            . '.btn{display:inline-block;margin-top:20px;padding:10px 20px;background:#f59e0b;color:#0f172a;border-radius:8px;text-decoration:none;font-weight:600}</style></head>'
            . '<body><div class="box"><div class="code">' . $code . '</div><div class="msg">' . htmlspecialchars($title, ENT_QUOTES) . '</div>'
            . '<p>' . htmlspecialchars($message, ENT_QUOTES) . '</p><a class="btn" href="/">Back to dashboard</a></div></body></html>';
    }
}