<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Table as TableModel;
use App\Models\Customer;
use App\Models\ClubSession;

class SessionController extends Controller
{
    public function index(): void
    {
        if (!user_can('sessions.view')) {
            $this->error('You do not have permission to view sessions.', 403);
        }

        $from    = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $to      = $_GET['to'] ?? date('Y-m-d');
        $tableId = (int) ($_GET['table_id'] ?? 0);
        $status  = $_GET['status'] ?? '';

        $where = [
            "DATE(s.start_time) BETWEEN ? AND ?",
            "s.status = 'completed'",
        ];
        $params = [$from, $to];

        if ($tableId > 0) {
            $where[] = 's.table_id = ?';
            $params[] = $tableId;
        }
        if ($status !== '') {
            $where[] = 's.payment_status = ?';
            $params[] = $status;
        }

        $sessions = Database::query(
            "SELECT s.*,
                    t.number AS table_number,
                    t.name AS table_name,
                    c.name AS customer_name,
                    u.name AS staff_name
             FROM sessions s
             JOIN tables t ON t.id = s.table_id
             LEFT JOIN customers c ON c.id = s.customer_id
             LEFT JOIN users u ON u.id = s.staff_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY s.id DESC
             LIMIT 500",
            $params
        );

        $this->view('sessions/index', [
            'sessions'  => $sessions,
            'tables'    => TableModel::all(),
            'from'      => $from,
            'to'        => $to,
            'tableId'   => $tableId,
            'status'    => $status,
        ]);
    }

    public function active(): void
    {
        if (!user_can('sessions.manage')) {
            $this->error('You do not have permission to manage sessions.', 403);
        }

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
        $session = \App\Models\ClubSession::find($id);
        if (!$session) {
            Response::error('Session not found', 404);
        }
        $this->redirect('/sessions');
    }

    public function invoice(int $id): void
    {
        if (!user_can('sessions.view')) {
            $this->error('You do not have permission to view sessions.', 403);
        }

        $session = ClubSession::withDetails($id);
        if (!$session) {
            Response::error('Session not found', 404);
        }

        $this->view('sessions/invoice', [
            's'        => $session,
            'settings' => \App\Services\SettingsService::all(),
            'operator' => $session['staff_name'] ?? (current_user()?->name ?? ''),
        ], 'blank');
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
        if (!user_can('sessions.manage')) {
            Response::error('Forbidden', 403);
        }

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

        // Auto-resolve rate band (peak/off-peak/night) unless manually overridden
        $resolved = \App\Services\RateService::resolveRate($rateType, $table->toArray());
        $rateType = $resolved['rate_type'];
        $rate     = $resolved['rate'];

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

        // Auto-activate any of today's booking for this table
        $activatedBooking = \App\Models\Booking::activateForTable((int) $table->id);

        \App\Services\AuditService::log('session_started', 'session', $sessionId, null, [
            'table'   => (int) $table->id,
            'customer'=> $customerId ?: null,
            'rate'    => $rate,
            'booking' => $activatedBooking,
        ]);

        Response::success([
            'session_id' => $sessionId,
            'table'      => $table->toArray(),
            'booking_activated' => $activatedBooking,
        ], 'Session started');
    }

    public function apiEnd(int $id): void
    {
        if (!user_can('sessions.manage')) {
            Response::error('Forbidden', 403);
        }

        $session = \App\Models\ClubSession::find($id);
        if (!$session || $session->status === 'completed') {
            Response::error('Session not found or already ended', 404);
        }

        $amount = $session->computeAmount();
        $elapsed = $session->billedSeconds();
        $hours = $elapsed / 3600.0;

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

        // Complete any active booking tied to this table
        $completedBooking = \App\Core\Database::execute(
            "UPDATE bookings SET status = 'completed'
             WHERE table_id = ? AND booking_date = CURDATE() AND status = 'active'",
            [(int) $session->table_id]
        );

        // Update customer stats if linked
        if ($session->customer_id) {
            Customer::incrementStats((int) $session->customer_id, $hours, $amount, $amount);
        }

        \App\Services\AuditService::log('session_ended', 'session', $session->id, null, [
            'table'  => (int) $session->table_id,
            'amount' => $amount,
        ]);

        Response::success([
            'session_id' => $session->id,
            'amount'     => $amount,
            'duration'   => $elapsed,
        ], 'Session ended');
    }

    public function apiAddCharge(int $id): void
    {
        if (!user_can('sessions.manage')) {
            Response::error('Forbidden', 403);
        }

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
        if (!user_can('sessions.manage')) {
            Response::error('Forbidden', 403);
        }

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