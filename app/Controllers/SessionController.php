<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Table as TableModel;
use App\Models\Customer;
use App\Models\ClubSession;

class SessionController extends Controller
{
    public function index(): void
    {
        $this->view('sessions/index', []);
    }

    public function active(): void
    {
        $sessions = \App\Models\ClubSession::activeSessions();
        $this->view('sessions/active', ['sessions' => $sessions]);
    }

    public function store(): void
    {
        // Redirect to table-based start
        $this->redirect('/tables');
    }

    public function show(int $id): void
    {
        $this->view('sessions/active', []);
    }

    public function end(int $id): void
    {
        $this->redirect('/tables');
    }

    public function logout(int $id): void
    {
        $this->redirect('/tables');
    }

    public function apiStart(int $id): void
    {
        $table = TableModel::find($id);
        if (!$table) {
            Response::error('Table not found', 404);
        }

        if ($table->status === 'occupied') {
            Response::error('Table is already occupied');
        }

        if (in_array($table->status, ['maintenance', 'blocked', 'offline'])) {
            Response::error('Table is not available');
        }

        $customerId  = (int) (Request::input('customer_id') ?? 0);
        $playersCount = max(1, (int) (Request::input('players_count') ?? 1));
        $rateType    = Request::input('rate_type') ?? 'hourly';
        $notes       = Request::input('notes') ?? '';

        // Determine rate
        $rate = (float) $table->hourly_rate;
        if ($rateType === 'vip' && $table->vip_rate) {
            $rate = (float) $table->vip_rate;
        } elseif ($rateType === 'night' && $table->night_rate) {
            $rate = (float) $table->night_rate;
        }

        $sessionId = ClubSession::create([
            'table_id'       => (int) $table->id,
            'customer_id'    => $customerId > 0 ? $customerId : null,
            'players_count'  => $playersCount,
            'start_time'     => date('Y-m-d H:i:s'),
            'rate_type'      => $rateType,
            'rate'           => $rate,
            'status'         => 'active',
            'notes'          => $notes,
            'staff_id'       => current_user()?->id ?? null,
        ]);

        $table->update(['status' => 'occupied']);

        Response::success([
            'session_id' => $sessionId,
            'table'      => $table->toArray(),
        ], 'Session started');
    }

    public function apiEnd(int $id): void
    {
        $session = \App\Models\ClubSession::find($id);
        if (!$session || $session->status === 'completed') {
            Response::error('Session not found or already ended', 404);
        }

        $amount = $session->computeAmount();

        $session->update([
            'end_time'      => date('Y-m-d H:i:s'),
            'amount'        => $amount,
            'status'        => 'completed',
            'payment_status'=> 'unpaid',
        ]);

        $table = TableModel::find((int) $session->table_id);
        if ($table) {
            $table->update(['status' => 'available']);
        }

        Response::success([
            'session_id' => $session->id,
            'amount'     => $amount,
            'duration'   => $session->billedSeconds(),
        ], 'Session ended');
    }

    public function apiAddCharge(int $id): void
    {
        $session = \App\Models\ClubSession::find($id);
        if (!$session || $session->status === 'completed') {
            Response::error('Session not found');
        }

        $amount = (float) (Request::input('amount') ?? 0);
        if ($amount <= 0) {
            Response::error('Invalid charge amount');
        }

        $session->update([
            'extra_charges' => (float) $session->extra_charges + $amount,
        ]);

        Response::success(['extra_charges' => $session->extra_charges], 'Charge added');
    }

    public function apiDiscount(int $id): void
    {
        $session = \App\Models\ClubSession::find($id);
        if (!$session || $session->status === 'completed') {
            Response::error('Session not found');
        }

        $amount = (float) (Request::input('amount') ?? 0);
        if ($amount <= 0) {
            Response::error('Invalid discount amount');
        }

        $session->update([
            'discount' => (float) $session->discount + $amount,
        ]);

        Response::success(['discount' => $session->discount], 'Discount applied');
    }

    public function sseTables(): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        while (ob_get_level()) { ob_end_clean(); }

        $lastId = (int) ($_SERVER['HTTP_LAST_EVENT_ID'] ?? 0);

        while (true) {
            $tables = TableModel::activeTables();
            $data = [];
            foreach ($tables as $t) {
                $session = ClubSession::activeForTable((int) $t['id']);
                $elapsed = 0;
                if ($session && $session['status'] === 'active') {
                    $elapsed = time() - strtotime($session['start_time']);
                    $elapsed -= (int) ($session['paused_total_sec'] ?? 0);
                }
                $data[] = [
                    'id'         => $t['id'],
                    'number'     => $t['number'],
                    'status'     => $t['status'],
                    'elapsed'    => max(0, $elapsed),
                    'session_id' => $session['id'] ?? null,
                ];
            }

            $json = json_encode($data);
            echo "event: tables\ndata: {$json}\n\n";
            flush();

            sleep(3);

            if (connection_aborted()) { break; }
        }
    }

    public function sseActivity(): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        while (ob_get_level()) { ob_end_clean(); }

        while (true) {
            echo ": ping\n\n";
            flush();
            sleep(15);
            if (connection_aborted()) { break; }
        }
    }
}