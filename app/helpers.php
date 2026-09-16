<?php

declare(strict_types=1);

// Simple .env file parser
function load_env(string $file): void
{
    if (!file_exists($file)) {
        return;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Strip surrounding quotes if present
        $value = trim($value, "\"'");

        // Don't overwrite existing real environment variables
        if (getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    return match (strtolower($value)) {
        'true', '(true)'  => true,
        'false', '(false)' => false,
        'null', '(null)'  => null,
        default           => $value,
    };
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function config(string $key, mixed $default = null): mixed
{
    static $items = [];

    $parts = explode('.', $key);
    $file  = array_shift($parts);

    if (!isset($items[$file])) {
        $path = ROOT_PATH . "/config/{$file}.php";
        $items[$file] = file_exists($path) ? require $path : [];
    }

    $value = $items[$file];
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }

    return $value;
}

function url(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
        $configured = rtrim((string) config('app.url'), '/');
        $configuredHost = (string) parse_url($configured, PHP_URL_HOST);

        // Tunnels (ngrok, reverse proxies) forward the real public host
        // while the server sees a different one — derive the base from the
        // forwarded host so generated links work for the visitor too.
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '';
        $host = trim(explode(',', (string) $host)[0]);
        $host = preg_replace('/[^a-zA-Z0-9.:\[\]-]/', '', $host);

        if ($host !== '' && strcasecmp($host, $configuredHost) !== 0) {
            $scheme = (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
                || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                ? 'https'
                : 'http';
            $configured = $scheme . '://' . $host;
        }

        $base = $configured;
    }

    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path, int $status = 302): void
{
    http_response_code($status);
    header("Location: " . url($path));
    exit;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old_input'][$key] ?? $default;
}

function flash(string $key, mixed $value = null): mixed
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $message;
}

function currency(mixed $amount): string
{
    return config('app.currency') . ' ' . number_format((float) $amount ?? 0, 0);
}

function format_duration(int $seconds): string
{
    $h = intdiv($seconds, 3600);
    $m = intdiv($seconds % 3600, 60);
    $s = $seconds % 60;

    return sprintf('%02d:%02d:%02d', $h, $m, $s);
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function verify_csrf(?string $token): bool
{
    return isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], (string) $token);
}

function current_user(): ?App\Models\User
{
    return App\Core\Auth::user();
}

function is_authenticated(): bool
{
    return App\Core\Auth::check();
}

function user_can(string $permission): bool
{
    return App\Core\Auth::can($permission);
}