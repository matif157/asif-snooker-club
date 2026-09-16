<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
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

        $lockUntil = (int) Session::get('_login_lock_until', 0);
        if ($lockUntil > time()) {
            $errors['general'] = 'Too many failed attempts. Try again in ' . ceil(($lockUntil - time()) / 60) . ' minute(s).';
        } elseif (!Request::csrf()) {
            $errors['general'] = 'Session expired. Please try again.';
        } elseif ($email === '' || $password === '') {
            $errors['general'] = 'Please enter your email and password.';
        } elseif (Auth::attempt($email, $password)) {
            Session::regenerate();
            Session::remove('_login_fails');
            Session::remove('_login_lock_until');
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
            $fails = (int) Session::get('_login_fails', 0) + 1;
            Session::set('_login_fails', $fails);
            if ($fails >= 5) {
                Session::set('_login_lock_until', time() + 900);
                Session::set('_login_fails', 0);
                $errors['general'] = 'Too many failed attempts. Please try again in 15 minutes.';
            } else {
                $errors['general'] = 'Invalid email or password. (' . $fails . '/5)';
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