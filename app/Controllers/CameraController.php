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
            'SELECT * FROM cameras ORDER BY sort_order ASC, id ASC'
        );

        $this->view('cctv/index', [
            'cameras'       => $cameras,
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

        $data   = Request::all();
        $errors = [];

        $name = trim((string) ($data['name'] ?? ''));
        $rtsp = trim((string) ($data['rtsp_url'] ?? ''));
        if ($name === '') {
            $errors[] = 'Camera name is required';
        }
        if ($rtsp !== '' && !preg_match('#^rtsp://#i', $rtsp)) {
            $errors[] = 'RTSP URL must start with rtsp://';
        }

        $stream = trim((string) ($data['stream_name'] ?? ''));
        if ($stream !== '' && !preg_match('/^[a-zA-Z0-9_-]{1,80}$/', $stream)) {
            $errors[] = 'Stream name may only contain letters, numbers, underscore and dash';
        }

        if ($errors !== []) {
            flash('cctv_errors', $errors);
            Response::redirect('/cctv');
        }

        Database::execute(
            'INSERT INTO cameras (name, location, rtsp_url, stream_name, enabled, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $name,
                trim((string) ($data['location'] ?? '')) ?: null,
                $stream === '' ? null : $rtsp,
                $stream ?: null,
                isset($data['enabled']) ? 1 : 0,
                max(0, (int) ($data['sort_order'] ?? 0)),
            ]
        );

        AuditService::log('camera_added', 'camera', null, null, ['name' => $name]);
        flash('success', 'Camera added.');
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