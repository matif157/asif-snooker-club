<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\ClubSession;
use App\Models\Table as TableModel;
use App\Services\SettingsService;

class TableController extends Controller
{
    public function index(): void
    {
        $tables = TableModel::activeTables();

        $canViewCctv = user_can('cctv.view') || user_can('cctv.manage');
        $streamMode  = (string) SettingsService::get('cctv_stream_mode', 'img');
        $serverUrl   = rtrim((string) SettingsService::get('cctv_server_url', 'http://127.0.0.1:1984'), '/');
        $cameraMap   = $canViewCctv ? $this->cameraByTable() : [];

        // Enrich with current sessions
        foreach ($tables as &$t) {
            $session = ClubSession::activeForTable((int) $t['id']);
            $t['current_session'] = $session;
            if ($session && $session['status'] === 'active') {
                $elapsed = time() - strtotime($session['start_time']);
                $elapsed -= (int) ($session['paused_total_sec'] ?? 0);
                $t['elapsed_seconds'] = max(0, $elapsed);
            } else {
                $t['elapsed_seconds'] = 0;
            }

            $cam = $cameraMap[(int) $t['id']] ?? null;
            $t['camera'] = $cam ? [
                'name'        => $cam['name'],
                'stream_name' => $cam['stream_name'],
                'hls_url'     => CameraController::hlsUrl($serverUrl, $cam['stream_name']),
                'player_url'  => CameraController::playerUrl($serverUrl, $cam['stream_name']),
            ] : null;
        }
        unset($t);

        // Prefill target for the "Start New Session" quick action (?start_session=1):
        // the first genuinely free table, so the modal never opens without a table.
        $startPrefill = null;
        foreach ($tables as $t) {
            if (($t['status'] ?? '') === 'available') {
                $startPrefill = [
                    'id'         => (int) $t['id'],
                    'number'     => $t['number'],
                    'hourly_rate'=> (float) $t['hourly_rate'],
                ];
                break;
            }
        }

        $activeSessions = ClubSession::activeSessions();

        $this->view('tables/index', [
            'tables'        => $tables,
            'activeSessions'=> $activeSessions,
            'startSession'  => Request::get('start_session') === '1',
            'startPrefill'  => $startPrefill,
            'canViewCctv'   => $canViewCctv,
            'streamMode'    => $streamMode,
            'serverUrl'     => $serverUrl,
        ]);
    }

    /**
     * First enabled camera bound to each table, keyed by table id.
     */
    private function cameraByTable(): array
    {
        $map = [];
        foreach (TableModel::camerasByTable() as $row) {
            $tableId = (int) $row['table_id'];
            if (!isset($map[$tableId])) {
                $map[$tableId] = $row;
            }
        }
        return $map;
    }

    public function create(): void
    {
        if (!user_can('tables.manage')) {
            $this->error('You do not have permission to manage tables.', 403);
        }

        $this->view('tables/create', []);
    }

    public function store(): void
    {
        if (!user_can('tables.manage')) {
            $this->error('You do not have permission to manage tables.', 403);
        }

        $data = Request::all();
        $errors = $this->validate($data, [
            'number'        => 'required|max:10',
            'name'          => 'required|max:120',
            'hourly_rate'   => 'required|numeric',
            'min_charge'    => 'required|numeric',
        ]);

        if (!empty($errors)) {
            $this->redirect('/tables');
        }

        TableModel::create([
            'number'      => $data['number'],
            'name'        => $data['name'],
            'type'        => $data['type'] ?? 'Standard',
            'hourly_rate' => (float) $data['hourly_rate'],
            'min_charge'  => (float) ($data['min_charge'] ?? 100),
            'status'      => 'available',
            'location'    => $data['location'] ?? null,
            'is_active'   => 1,
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
        ]);

        Response::redirect('/tables');
    }

    public function edit(int $id): void
    {
        if (!user_can('tables.manage')) {
            $this->error('You do not have permission to manage tables.', 403);
        }

        $table = TableModel::find($id);
        if (!$table) {
            Response::redirect('/tables');
        }

        $this->view('tables/edit', ['table' => $table->toArray()]);
    }

    public function update(int $id): void
    {
        if (!user_can('tables.manage')) {
            $this->error('You do not have permission to manage tables.', 403);
        }

        $table = TableModel::find($id);
        if (!$table) {
            Response::redirect('/tables');
        }

        $data = Request::all();
        $table->update([
            'name'        => $data['name'] ?? $table->name,
            'type'        => $data['type'] ?? $table->type,
            'hourly_rate' => (float) ($data['hourly_rate'] ?? $table->hourly_rate),
            'min_charge'  => (float) ($data['min_charge'] ?? $table->min_charge),
            'location'    => $data['location'] ?? $table->location,
            'sort_order'  => (int) ($data['sort_order'] ?? $table->sort_order),
        ]);

        Response::redirect('/tables');
    }

    public function destroy(int $id): void
    {
        if (!user_can('tables.manage')) {
            $this->error('You do not have permission to manage tables.', 403);
        }

        $table = TableModel::find($id);
        if ($table) {
            $table->delete();
        }

        Response::redirect('/tables');
    }

    public function toggleStatus(int $id): void
    {
        if (!user_can('tables.manage')) {
            $this->error('You do not have permission to manage tables.', 403);
        }

        $table = TableModel::find($id);
        if (!$table) {
            Response::error('Table not found', 404);
        }

        $newStatus = match($table->status) {
            'available'   => 'maintenance',
            'maintenance' => 'available',
            default       => 'available',
        };

        $table->update(['status' => $newStatus]);
        Response::success(['status' => $newStatus]);
    }

    public function apiList(): void
    {
        $tables = TableModel::activeTables();
        foreach ($tables as &$t) {
            $session = ClubSession::activeForTable((int) $t['id']);
            $t['current_session'] = $session;
            $t['elapsed_seconds'] = 0;
            if ($session && $session['status'] === 'active') {
                $elapsed = time() - strtotime($session['start_time']);
                $elapsed -= (int) ($session['paused_total_sec'] ?? 0);
                $t['elapsed_seconds'] = max(0, $elapsed);
            }
        }
        unset($t);
        Response::success($tables);
    }
}