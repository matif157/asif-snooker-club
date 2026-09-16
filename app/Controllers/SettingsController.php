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
            $this->error('You do not have permission to manage settings.', 403);
        }

        $settings = SettingsService::all();
        $users = \App\Core\Database::query('SELECT id, name, email, phone, role, status, last_login_at FROM users ORDER BY id ASC');
        $audit = AuditService::recent(20);
        $backups = BackupService::list();

        $this->view('settings/index', [
            'settings'    => $settings,
            'users'       => $users,
            'audit'       => $audit,
            'backups'     => $backups,
            'permissions' => \App\Core\Database::query('SELECT id, name, description FROM permissions ORDER BY name ASC'),
            'rolePerms'   => \App\Core\Database::query('SELECT role, permission_id FROM role_permissions'),
            'userTheme'   => \App\Services\ThemeService::userTheme(),
        ]);
    }

    /**
     * Persist the granular permission matrix for non-superuser roles.
     */
    public function updateRoles(): void
    {
        if (!user_can('settings.manage')) {
            $this->error('You do not have permission to manage settings.', 403);
        }

        if (!Request::csrf()) {
            Response::redirect('/settings');
        }

        $allowedRoles = ['eco', 'counter', 'staff', 'auditor'];
        $posted = Request::all();

        // Force-keep "settings.manage" on the current user's own role so an
        // operator can never lock themselves (or the club) out of Settings.
        $selfRole = current_user()?->role;
        if (in_array($selfRole, $allowedRoles, true)) {
            $posted['perms'][$selfRole][] = 'settings.manage';
        }

        $validNames = array_column(
            \App\Core\Database::query('SELECT name FROM permissions'),
            'name'
        );

        $db = \App\Core\Database::connection();
        $db->beginTransaction();
        try {
            foreach ($allowedRoles as $role) {
                $names = array_values(array_unique((array) ($posted['perms'][$role] ?? [])));
                $names = array_values(array_intersect($validNames, $names));

                // Owner/admin keep full access via the code-level bypass;
                // their stored rows are never touched here.
                if (in_array($role, ['owner', 'admin'], true)) {
                    continue;
                }

                \App\Core\Database::execute('DELETE FROM role_permissions WHERE role = ?', [$role]);
                foreach ($names as $name) {
                    \App\Core\Database::execute(
                        'INSERT INTO role_permissions (role, permission_id)
                         SELECT ?, id FROM permissions WHERE name = ?',
                        [$role, $name]
                    );
                }
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('error', 'Could not save roles: ' . $e->getMessage());
            Response::redirect('/settings#roles');
        }

        AuditService::log('role_permissions_updated', 'role', null, null, $posted);

        flash('success', 'Role permissions updated.');
        Response::redirect('/settings#roles');
    }

    public function backup(): void
    {
        if (!user_can('settings.manage')) {
            $this->error('You do not have permission to manage settings.', 403);
        }

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
        if (!user_can('settings.manage')) {
            $this->error('You do not have permission to manage settings.', 403);
        }

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
            $this->error('You do not have permission to manage settings.', 403);
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
            'accent_color',
        ];

        foreach ($allowed as $key) {
            if (!Request::has($key)) {
                continue;
            }
            $value = Request::post($key, '');
            $value = trim((string) $value);

            if ($key === 'accent_color' && !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                continue;
            }

            SettingsService::set($key, $value);
        }

        AuditService::log('settings_updated', 'settings', null, null, $allowed);

        Response::redirect('/settings');
    }

    public function updateUser(int $id): void
    {
        if (!user_can('staff.manage')) {
            $this->error('You do not have permission to manage staff.', 403);
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

    /**
     * Persist the current user's theme preference (dark / light / auto).
     */
    public function theme(): void
    {
        $user = current_user();
        if (!$user) {
            $this->error('Unauthenticated.', 401);
        }

        $theme = Request::post('theme', 'dark');
        if (!in_array($theme, ['dark', 'light', 'auto'], true)) {
            $this->error('Invalid theme.', 422);
        }

        \App\Core\Database::execute(
            'UPDATE users SET theme = ? WHERE id = ?',
            [$theme, (int) $user->id]
        );

        $this->success(['theme' => $theme], 'Theme updated.');
    }

    public function createUser(): void
    {
        if (!user_can('staff.manage')) {
            $this->error('You do not have permission to manage staff.', 403);
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