<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\InstallerService;

/**
 * Web installer wizard (/install). Only reachable when the app is not installed.
 */
class SetupController extends Controller
{
    public function index(): void
    {
        if (InstallerService::isInstalled()) {
            Response::redirect('/login');
        }

        $this->view('setup/install', [
            'requirements' => InstallerService::requirements(),
            'errors'       => flash('flash_install_errors') ?? [],
        ], null);
    }

    public function install(): void
    {
        if (!Request::csrf()) {
            Response::redirect('/install');
        }

        $data = Request::all();

        $errors = [];
        foreach (['db', 'user', 'admin_name', 'admin_email', 'admin_password'] as $f) {
            if (trim((string) ($data[$f] ?? '')) === '') {
                $errors[] = ucfirst(str_replace('_', ' ', $f)) . ' is required';
            }
        }
        if (($data['admin_password'] ?? '') !== ($data['admin_password_confirm'] ?? '')) {
            $errors[] = 'Passwords do not match';
        }
        if (strlen((string) ($data['admin_password'] ?? '')) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        if (filter_var($data['admin_email'] ?? '', FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'A valid admin email is required';
        }

        if ($errors !== []) {
            flash('flash_install_errors', $errors);
            Response::redirect('/install');
        }

        $cfg = [
            'host'        => $data['host'] ?? '127.0.0.1',
            'port'        => (int) ($data['port'] ?? 3306),
            'db'          => $data['db'],
            'user'        => $data['user'],
            'pass'        => $data['pass'] ?? '',
            'club_name'   => $data['club_name'] ?? 'Asif Snooker Club',
            'club_phone'  => $data['club_phone'] ?? '',
            'club_address'=> $data['club_address'] ?? '',
            'admin_name'  => $data['admin_name'],
            'admin_email' => strtolower(trim($data['admin_email'])),
            'admin_password' => $data['admin_password'],
            'app_url'     => InstallerService::guessAppUrl(),
        ];

        try {
            InstallerService::install($cfg);
            flash('install_success', 'Installation complete! Sign in with your new admin account.');
            Response::redirect('/login');
        } catch (\Throwable $e) {
            flash('flash_install_errors', [$e->getMessage()]);
            Response::redirect('/install');
        }
    }
}