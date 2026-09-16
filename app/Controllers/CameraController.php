<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuditService;
use App\Services\SettingsService;

/**
 * CCTV live grid. Cameras stream through a local go2rtc/media-streamer.
 */
class CameraController extends Controller
{
    public function index(): void
    {
        if (!user_can('cctv.view') && !user_can('cctv.manage')) {
            $this->error('You do not have permission to view CCTV.', 403);
        }

        $cameras = Database::query(
            "SELECT c.*, t.number AS table_number, t.name AS table_name, t.status AS table_status
             FROM cameras c
             LEFT JOIN tables t ON t.id = c.table_id
             ORDER BY c.sort_order ASC, c.id ASC"
        );

        $tables = user_can('cctv.manage')
            ? Database::query("SELECT id, number, name, status FROM tables ORDER BY number ASC, id ASC")
            : [];

        $this->view('cctv/index', [
            'cameras'       => $cameras,
            'tables'        => $tables,
            'canManage'     => user_can('cctv.manage'),
            'serverUrl'     => rtrim((string) SettingsService::get('cctv_server_url', 'http://127.0.0.1:1984'), '/'),
        ]);
    }

    public function store(): void
    {
        if (!user_can('cctv.manage')) {
            $this->error('You do not have permission to manage cameras.', 403);
        }
        if (!Request::csrf()) {
            Response::redirect('/cctv');
        }

        $errors = $this->validateCamera(Request::all());
        if ($errors !== []) {
            flash('cctv_errors', $errors);
            Response::redirect('/cctv');
        }

        Database::execute(
            'INSERT INTO cameras (name, location, rtsp_url, stream_name, table_id, enabled, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                trim((string) Request::input('name', '')),
                trim((string) Request::input('location', '')) ?: null,
                trim((string) Request::input('rtsp_url', '')) ?: null,
                trim((string) Request::input('stream_name', '')) ?: null,
                $this->validTableId(Request::input('table_id')) ?? null,
                (int) (bool) Request::input('enabled'),
                max(0, (int) (Request::input('sort_order') ?? 0)),
            ]
        );

        AuditService::log('camera_added', 'camera', null, null, ['name' => Request::input('name')]);
        flash('success', 'Camera added.');
        Response::redirect('/cctv');
    }

    public function update(int $id): void
    {
        if (!user_can('cctv.manage')) {
            $this->error('You do not have permission to manage cameras.', 403);
        }
        if (!Request::csrf()) {
            Response::redirect('/cctv');
        }

        $camera = Database::fetchOne('SELECT id, name FROM cameras WHERE id = ?', [$id]);
        if (!$camera) {
            flash('error', 'Camera not found.');
            Response::redirect('/cctv');
        }

        $errors = $this->validateCamera(Request::all());
        if ($errors !== []) {
            flash('cctv_errors', $errors);
            Response::redirect('/cctv');
        }

        Database::execute(
            'UPDATE cameras
             SET name = ?, location = ?, rtsp_url = ?, stream_name = ?, table_id = ?, enabled = ?, sort_order = ?
             WHERE id = ?',
            [
                trim((string) Request::input('name', '')),
                trim((string) Request::input('location', '')) ?: null,
                trim((string) Request::input('rtsp_url', '')) ?: null,
                trim((string) Request::input('stream_name', '')) ?: null,
                $this->validTableId(Request::input('table_id')) ?? null,
                (int) (bool) Request::input('enabled'),
                max(0, (int) (Request::input('sort_order') ?? 0)),
                $id,
            ]
        );

        AuditService::log('camera_updated', 'camera', $id, null, ['name' => Request::input('name')]);
        flash('success', 'Camera updated.');
        Response::redirect('/cctv');
    }

    public function destroy(int $id): void
    {
        if (!user_can('cctv.manage')) {
            $this->error('You do not have permission to manage cameras.', 403);
        }
        if (!Request::csrf()) {
            Response::redirect('/cctv');
        }

        $exists = Database::fetchOne('SELECT id, name FROM cameras WHERE id = ?', [$id]);
        if ($exists) {
            Database::execute('DELETE FROM cameras WHERE id = ?', [$id]);
            AuditService::log('camera_removed', 'camera', $id, null, ['name' => $exists['name']]);
            flash('success', 'Camera removed.');
        }

        Response::redirect('/cctv');
    }

    private function validateCamera(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors[] = 'Camera name is required';
        }
        $rtsp = trim((string) ($data['rtsp_url'] ?? ''));
        if ($rtsp !== '' && !preg_match('#^rtsp://#i', $rtsp)) {
            $errors[] = 'RTSP URL must start with rtsp://';
        }
        $stream = trim((string) ($data['stream_name'] ?? ''));
        if ($stream !== '' && !preg_match('/^[a-zA-Z0-9_-]{1,80}$/', $stream)) {
            $errors[] = 'Stream name may only contain letters, numbers, underscore and dash';
        }
        if (!empty($data['table_id']) && $this->validTableId($data['table_id']) === null) {
            $errors[] = 'Selected table does not exist';
        }

        return $errors;
    }

    private function validTableId(mixed $tableId): ?int
    {
        $id = (int) ($tableId ?? 0);
        if ($id <= 0) {
            return null;
        }
        $row = Database::fetchOne('SELECT id FROM tables WHERE id = ?', [$id]);
        return $row ? $id : null;
    }

    /**
     * Best-effort stream URL for the live tile. Falls back to null
     * when no stream name is configured (dev shows a placeholder).
     */
    public static function streamUrl(?string $serverUrl, ?string $streamName): ?string
    {
        if ($streamName === null || $streamName === '') {
            return null;
        }
        return rtrim($serverUrl ?? 'http://127.0.0.1:1984', '/') . '/stream/' . rawurlencode($streamName);
    }
}