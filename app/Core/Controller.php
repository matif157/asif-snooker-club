<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $template, array $data = [], ?string $layout = 'app'): void
    {
        View::render($template, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    protected function success(mixed $data = [], string $message = 'Success'): void
    {
        Response::success($data, $message);
    }

    protected function error(string $message = 'Error', int $status = 400): void
    {
        Response::error($message, $status);
    }

    protected function redirect(string $path): void
    {
        Response::redirect($path);
    }

    protected function back(): void
    {
        Response::back();
    }

    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            foreach (explode('|', $rule) as $singleRule) {
                $ruleParts = explode(':', $singleRule, 2);
                $ruleName = $ruleParts[0];
                $ruleParam = $ruleParts[1] ?? null;

                switch ($ruleName) {
                    case 'required':
                        if ($value === null || $value === '') {
                            $errors[$field][] = "The {$field} field is required.";
                        }
                        break;

                    case 'email':
                        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "The {$field} must be a valid email address.";
                        }
                        break;

                    case 'numeric':
                        if ($value !== null && $value !== '' && !is_numeric($value)) {
                            $errors[$field][] = "The {$field} must be a number.";
                        }
                        break;

                    case 'min':
                        if ($value !== null && strlen((string) $value) < (int) $ruleParam) {
                            $errors[$field][] = "The {$field} must be at least {$ruleParam} characters.";
                        }
                        break;

                    case 'max':
                        if ($value !== null && strlen((string) $value) > (int) $ruleParam) {
                            $errors[$field][] = "The {$field} must not exceed {$ruleParam} characters.";
                        }
                        break;

                    case 'phone':
                        // Normalize to digits only and check length
                        $digits = preg_replace('/\D+/', '', (string) $value);
                        if ($value !== null && $value !== '' && (strlen($digits) < 10 || strlen($digits) > 15)) {
                            $errors[$field][] = "The {$field} must be a valid phone number.";
                        }
                        break;
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['_errors'] = $errors;
            $_SESSION['_old_input'] = $data;
        }

        return $errors;
    }

    protected function errors(): array
    {
        $errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);
        return $errors;
    }
}