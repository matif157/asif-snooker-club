<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

class CsrfMiddleware
{
    public function __invoke(): bool
    {
        // Only verify on POST requests
        if (!Request::isPost()) {
            return true;
        }

        if (!Request::csrf()) {
            http_response_code(419);
            if (Request::isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'CSRF token mismatch. Please refresh the page.']);
                return false;
            }

            echo 'CSRF token mismatch. <a href="/">Go back</a>';
            return false;
        }

        return true;
    }
}