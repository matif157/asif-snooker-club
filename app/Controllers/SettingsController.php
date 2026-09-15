<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\Expense;
use App\Services\AuditService;
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

        $this->view('settings/index', [
            'settings' => $settings,
            'users'    => $users,
            'audit'    => $audit,
        ]);
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