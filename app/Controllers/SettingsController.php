<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\Expense;
use App\Services\AuditService;
use App\Services\BackupService;
use App\Services\SettingsService;

class SettingsController extends Controller
{
    public function index(): void
    {
        if (!user_can('settings.manage')) {
            $this->error('You do not have permission to manage settings.');
        }

        $settings = SettingsService::all();
        $users = \App\Core\Database::query('SELECT id, name, email, phone, role, status, last_login_at FROM users ORDER BY id ASC');
        $audit = AuditService::recent(20);
        $backups = BackupService::list();

        $this->view('settings/index', [
            'settings' => $settings,
            'users'    => $users,
            'audit'    => $audit,
            'backups'  => $backups,
        ]);
    }

    public function backup(): void
    {
        $path = BackupService::create();
        $bytes = filesize($path);

        AuditService::log('backup_created', 'backup', null, null, [
            'name' => basename($path),
            'size' => $bytes,
        ]);

        flash('success', 'Database backup created: ' . basename($path));
        Response::redirect('/settings#backups');
    }

    public function downloadBackup(string $name): void
    {
        $safe = basename($name);
        $path = BackupService::backupDir() . '/' . $safe;

        if (!preg_match('/^backup-\d{8}-\d{6}\.sql$/', $safe) || !file_exists($path)) {
            Response::error('Backup not found', 404);
        }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $safe . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function update(): void
    {
        if (!user_can('settings.manage')) {
            $this->error('You do not have permission to manage settings.');
        }

        if (!Request::csrf()) {
            Response::redirect('/settings');
        }

        $allowed = [
            'club_name', 'club_phone', 'club_address', 'currency',
            'business_hours_open', 'business_hours_close',
            'default_hourly_rate', 'default_min_charge', 'whatsapp_template',
            'peak_enabled', 'peak_start', 'peak_end', 'peak_rate_multiplier',
            'night_start', 'night_end',
        ];

        foreach ($allowed as $key) {
            if (Request::has($key)) {
                $value = Request::post($key, '');
                SettingsService::set($key, trim((string) $value));
            }
        }

        AuditService::log('settings_updated', 'settings', null, null, $allowed);

        Response::redirect('/settings');
    }

    public function updateUser(int $id): void
    {
        if (!user_can('staff.manage')) {
            $this->error('You do not have permission to manage staff.');
        }

        $user = User::find($id);
        if (!$user) {
            Response::redirect('/settings');
        }

        $data = Request::all();
        $user->update([
            'name'  => $data['name'] ?? $user->name,
            'phone' => $data['phone'] ?? $user->phone,
            'role'  => $data['role'] ?? $user->role,
            'status'=> $data['status'] ?? $user->status,
        ]);

        AuditService::log('user_updated', 'user', $user->id, null, $data);

        Response::redirect('/settings');
    }

    public function createUser(): void
    {
        if (!user_can('staff.manage')) {
            $this->error('You do not have permission to manage staff.');
        }

        $data = Request::all();
        if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            Response::redirect('/settings');
        }

        $existing = User::findByEmail($data['email']);
        if ($existing) {
            Response::redirect('/settings');
        }

        $password = ($data['password'] ?? '') !== '' ? $data['password'] : bin2hex(random_bytes(4));

        User::create([
            'name'          => $data['name'] ?? 'Staff',
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?? null,
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
            'role'          => $data['role'] ?? 'staff',
            'status'        => 'active',
        ]);

        flash('success', 'Staff account created.');
        AuditService::log('user_created', 'user', null, null, ['email' => $data['email']]);

        Response::redirect('/settings');
    }
}