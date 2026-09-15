<?php

declare(strict_types=1);

namespace App\Middleware;

class AuthMiddleware
{
    public function __invoke(): bool
    {
        if (!is_authenticated()) {
            if (Request::isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Please login to continue']);
                return false;
            }

            header('Location: ' . url('/login'));
            return false;
        }

        return true;
    }
}