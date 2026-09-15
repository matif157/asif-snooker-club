<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = [], string $message = 'Success', int $status = 200): void
    {
        self::json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    public static function error(string $message = 'Error', int $status = 400, mixed $data = null): void
    {
        self::json(['success' => false, 'message' => $message, 'data' => $data], $status);
    }

    public static function view(string $template, array $data = [], string $layout = 'app'): void
    {
        View::render($template, $data, $layout);
    }

    public static function redirect(string $path, int $status = 302): void
    {
        header("Location: " . url($path), true, $status);
        exit;
    }

    public static function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header("Location: {$referer}");
        exit;
    }
}