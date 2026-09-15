<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }

        $this->view('auth/login', ['errors' => [], 'old' => []], null);
    }

    public function login(): void
    {
        $email    = trim((string) Request::post('email', ''));
        $password = (string) Request::post('password', '');
        $errors   = [];

        if (!Request::csrf()) {
            $errors['general'] = 'Session expired. Please try again.';
        } elseif ($email === '' || $password === '') {
            $errors['general'] = 'Please enter your email and password.';
        } else {
            if (Auth::attempt($email, $password)) {
                $user = Auth::user();
                if ($user?->status === 'inactive') {
                    Auth::logout();
                    $errors['general'] = 'This account is disabled. Contact the owner.';
                } else {
                    if ($user) {
                        User::updateLastLogin((int) $user->id);
                    }
                    Response::redirect('/');
                }
            } else {
                $errors['general'] = 'Invalid email or password.';
            }
        }

        $this->view('auth/login', [
            'errors'  => $errors,
            'old'     => ['email' => $email],
        ], null);
    }

    public function logout(): void
    {
        if (!Request::csrf()) {
            Response::redirect('/login');
        }

        Auth::logout();
        Response::redirect('/login');
    }
}