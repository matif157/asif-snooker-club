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
use App\Services\CsvService;

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

        $sessions = $this->querySessions($from, $to, $tableId, $status, 500);

        $this->view('sessions/index', [
            'sessions'  => $sessions,
            'tables'    => TableModel::all(),
            'from'      => $from,
            'to'        => $to,
            'tableId'   => $tableId,
            'status'    => $status,
        ]);
    }

    public function export(): void
    {
        if (!user_can('sessions.view')) {
            $this->error('You do not have permission to export sessions.', 403);
        }

        $from    = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $to      = $_GET['to'] ?? date('Y-m-d');
        $tableId = (int) ($_GET['table_id'] ?? 0);
        $status  = $_GET['status'] ?? '';

        $rows = $this->querySessions($from, $to, $tableId, $status, 10000);

        CsvService::sendHeaders('sessions-' . $from . '-to-' . $to . '.csv');
        CsvService::download('', [
            'id'               => '#',
            'date'             => 'Date',
            'start_time'       => 'Start',
            'end_time'         => 'End',
            'duration_minutes' => 'Duration (min)',
            'table_number'     => 'Table No',
            'table_name'       => 'Table Name',
            'customer_name'    => 'Customer',
            'players_count'    => 'Players',
            'staff_name'       => 'Staff',
            'rate'             => 'Rate (Rs/hr)',
            'discount'         => 'Discount',
            'extra_charges'    => 'Extras',
            'amount'           => 'Amount',
            'payment_status'   => 'Payment Status',
            'payment_method'   => 'Method',
            'notes'            => 'Notes',
        ], $rows, [
            'date' => fn($r) => date('Y-m-d', strtotime($r['start_time'])),
        ]);
    }

    private function querySessions(string $from, string $to, int $tableId, string $status, int $limit): array
    {
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

        return Database::query(
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
             LIMIT {$limit}",
            $params
        );
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

        // Hard guard: never allow two live sessions on the same table (protects
        // against stale table.status or upsell/hold edge cases).
        $live = Database::query(
            'SELECT COUNT(*) AS c FROM sessions WHERE table_id = ? AND status = ?',
            [(int) $table->id, 'active']
        )[0]['c'] ?? 0;
        if ((int) $live > 0) {
            Response::error('Table already has a live session');
        }

        if (in_array($table->status, ['maintenance', 'blocked', 'offline'])) {
            Response::error('Table is not available');
        }

        $customerId   = (int) (Request::input('customer_id') ?? 0);
        $playersCount = max(1, (int) (Request::input('players_count') ?? 1));
        $rateType     = Request::input('rate_type') ?? 'hourly';
        $notes        = trim((string) (Request::input('notes') ?? ''));

        $winner = trim((string) (Request::input('player_winner') ?? ''));
        $loser  = trim((string) (Request::input('player_loser') ?? ''));

        // Charge mode: live timer vs an agreed fixed amount.
        $chargeType = (string) (Request::input('charge_type') ?? ClubSession::CHARGE_TIMER);
        if (!in_array($chargeType, [ClubSession::CHARGE_TIMER, ClubSession::CHARGE_FIXED], true)) {
            $chargeType = ClubSession::CHARGE_TIMER;
        }
        $fixedAmount = round((float) (Request::input('fixed_amount') ?? 0), 2);
        if ($chargeType === ClubSession::CHARGE_FIXED && $fixedAmount <= 0) {
            Response::error('Fixed amount is required when charge type is fixed.');
        }

        $expectedEnd = $this->normaliseDateTime((string) (Request::input('expected_end_time') ?? ''));

        $method  = \App\Models\Payment::normalizeMethod(Request::input('payment_method') ?? 'cash');
        $payMode = (string) (Request::input('pay_mode') ?? 'later');
        if (!in_array($payMode, ['now', 'later'], true)) {
            $payMode = 'later';
        }

        // Resolve the customer / client identity. A pay-later balance must be
        // traceable, so a phone number lets us reuse or create the customer.
        $clientName    = trim((string) (Request::input('client_name') ?? ''));
        $customerName  = trim((string) (Request::input('customer_name') ?? ''));
        $customerPhone = trim((string) (Request::input('customer_phone') ?? ''));

        if ($customerId <= 0 && $customerPhone !== '') {
            $existing = Customer::findByPhone($customerPhone);
            if ($existing) {
                $customerId = (int) $existing['id'];
            } else {
                $customerId = Customer::create([
                    'name'       => $customerName !== '' ? $customerName : ('Walk-in ' . $customerPhone),
                    'phone'      => Customer::normalizePhone($customerPhone),
                    'category'   => 'regular',
                    'status'     => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        if ($customerId > 0) {
            $customer = Customer::find($customerId);
            if ($customer) {
                if ($clientName === '') {
                    $clientName = (string) $customer->name;
                }
                if ($customerPhone === '') {
                    $customerPhone = (string) ($customer->phone ?? '');
                }
            } else {
                $customerId = 0;
            }
        }

        if ($clientName === '') {
            $clientName = $winner !== '' ? $winner : ($loser !== '' ? $loser : 'Walk-in');
        }

        // Paying now only makes sense when the amount is already known.
        if ($payMode === 'now' && $chargeType !== ClubSession::CHARGE_FIXED) {
            $payMode = 'later';
        }
        // Loan/udhaar needs somebody to bill.
        if ($payMode === 'later' && $chargeType === ClubSession::CHARGE_FIXED && $fixedAmount > 0 && $customerId <= 0) {
            Response::error('Pay-later (udhaar) ke liye customer ka naam aur phone zaroori hai.');
        }

        // Auto-resolve rate band (peak/off-peak/night) unless manually overridden
        $resolved = \App\Services\RateService::resolveRate($rateType, $table->toArray());
        $rateType = $resolved['rate_type'];
        $rate     = $resolved['rate'];

        $payNow = $payMode === 'now' && $chargeType === ClubSession::CHARGE_FIXED && $fixedAmount > 0;

        $sessionId = ClubSession::create([
            'table_id'          => (int) $table->id,
            'customer_id'       => $customerId > 0 ? $customerId : null,
            'player_winner'     => $winner !== '' ? $winner : null,
            'player_loser'      => $loser !== '' ? $loser : null,
            'client_name'       => $clientName !== '' ? $clientName : null,
            'players_count'     => $playersCount,
            'start_time'        => date('Y-m-d H:i:s'),
            'expected_end_time' => $expectedEnd,
            'rate_type'         => $rateType,
            'charge_type'       => $chargeType,
            'fixed_amount'      => $chargeType === ClubSession::CHARGE_FIXED ? $fixedAmount : null,
            'rate'              => $rate,
            'payment_method'    => $method,
            'payment_status'    => $payNow ? 'paid' : 'unpaid',
            'is_loan'           => ($payMode === 'later' && $chargeType === ClubSession::CHARGE_FIXED) ? 1 : 0,
            'status'            => 'active',
            'notes'             => $notes !== '' ? $notes : null,
            'staff_id'          => current_user()?->id ?? null,
        ]);

        // Immediate payment for a fixed-charge booking.
        if ($payNow) {
            \App\Models\Payment::create([
                'session_id'  => $sessionId,
                'customer_id' => $customerId > 0 ? $customerId : null,
                'amount'      => $fixedAmount,
                'method'      => $method,
                'status'      => 'paid',
                'notes'       => 'Paid at session start',
                'paid_at'     => date('Y-m-d H:i:s'),
                'accepted_by' => current_user()?->id ?? null,
            ]);
        }

        $table->update(['status' => 'occupied']);

        // Auto-activate any of today's booking for this table
        $activatedBooking = \App\Models\Booking::activateForTable((int) $table->id);

        \App\Services\AuditService::log('session_started', 'session', $sessionId, null, [
            'table'        => (int) $table->id,
            'customer'     => $customerId ?: null,
            'client'       => $clientName,
            'winner'       => $winner ?: null,
            'loser'        => $loser ?: null,
            'charge_type'  => $chargeType,
            'fixed_amount' => $chargeType === ClubSession::CHARGE_FIXED ? $fixedAmount : null,
            'rate'         => $rate,
            'method'       => $method,
            'pay_mode'     => $payMode,
            'booking'      => $activatedBooking,
        ]);

        Response::success([
            'session_id' => $sessionId,
            'table'      => $table->toArray(),
            'booking_activated' => $activatedBooking,
        ], 'Session started');
    }

    /**
     * Edit the winner / loser (and client label) of a live session.
     */
    public function apiUpdatePlayers(int $id): void
    {
        if (!user_can('sessions.manage')) {
            Response::error('Forbidden', 403);
        }

        $session = ClubSession::find($id);
        if (!$session || in_array($session->status, ['completed', 'cancelled'], true)) {
            Response::error('Session not found or already closed', 404);
        }

        $patch = [];
        foreach (['player_winner' => 'player_winner', 'player_loser' => 'player_loser', 'client_name' => 'client_name'] as $field => $key) {
            if (Request::input($field) !== null) {
                $val = trim((string) Request::input($field));
                $patch[$key] = $val !== '' ? $val : null;
            }
        }

        if ($patch === []) {
            Response::error('Nothing to update');
        }

        $session->update($patch);

        \App\Services\AuditService::log('session_players_updated', 'session', $id, null, $patch);

        Response::success(['session' => $session->toArray()], 'Players updated');
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

        $amount  = $session->computeAmount();
        $elapsed = $session->billedSeconds();
        $hours   = $elapsed / 3600.0;

        $method = \App\Models\Payment::normalizeMethod(
            Request::input('method') ?? $session->payment_method ?? 'cash'
        );

        // Optional settlement collected right at the end of the session.
        $payAmount = round((float) (Request::input('pay_amount') ?? 0), 2);
        if ($payAmount > 0) {
            \App\Models\Payment::create([
                'session_id'  => (int) $session->id,
                'customer_id' => $session->customer_id ? (int) $session->customer_id : null,
                'amount'      => $payAmount,
                'method'      => $method,
                'status'      => 'paid',
                'notes'       => 'Payment at session end',
                'paid_at'     => date('Y-m-d H:i:s'),
                'accepted_by' => current_user()?->id ?? null,
            ]);
        }

        $paid      = ClubSession::paidTotal((int) $session->id);
        $remaining = max(0, round($amount - $paid, 2));

        $paymentStatus = $remaining <= 0.009
            ? 'paid'
            : ($paid > 0 ? 'partial' : 'unpaid');

        // A leftover balance on a linked customer becomes a loan (udhaar).
        $isLoan     = 0;
        $loanAmount = 0.0;
        if ($remaining > 0 && $session->customer_id) {
            $isLoan     = 1;
            $loanAmount = $remaining;
        }

        $session->update([
            'end_time'       => date('Y-m-d H:i:s'),
            'amount'         => $amount,
            'status'         => 'completed',
            'payment_status' => $paymentStatus,
            'payment_method' => $method,
            'is_loan'        => $isLoan,
            'loan_amount'    => $loanAmount,
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

        // Update customer stats if linked — only the unpaid remainder becomes
        // outstanding, so paying in full never creates a phantom loan.
        if ($session->customer_id) {
            Customer::incrementStats((int) $session->customer_id, $hours, $amount, $loanAmount);
        }

        \App\Services\AuditService::log('session_ended', 'session', $session->id, null, [
            'table'          => (int) $session->table_id,
            'amount'         => $amount,
            'paid'           => $paid,
            'remaining'      => $remaining,
            'payment_status' => $paymentStatus,
            'loan'           => $loanAmount,
        ]);

        Response::success([
            'session_id'     => $session->id,
            'amount'         => $amount,
            'paid'           => $paid,
            'remaining'      => $remaining,
            'payment_status' => $paymentStatus,
            'loan_amount'    => $loanAmount,
            'duration'       => $elapsed,
        ], 'Session ended');
    }

    /**
     * Parse a browser datetime-local value into a MySQL DATETIME (or null).
     */
    private function normaliseDateTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $ts = strtotime($value);
        return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
    }

    public function apiAddCharge(int $id): void
    {
        if (!user_can('sessions.manage')) {
            Response::error('Forbidden', 403);
        }

        $session = \App\Models\ClubSession::find($id);
        if (!$session || in_array($session->status, ['completed', 'cancelled'], true)) {
            Response::error('Session not found');
        }

        $amount = (float) (Request::input('amount') ?? 0);
        if ($amount <= 0) {
            Response::error('Invalid charge amount');
        }

        $label = trim((string) Request::input('label', ''));
        $extra = (float) $session->extra_charges + $amount;

        $session->update([
            'extra_charges' => $extra,
            'notes'         => trim(($session->notes ? $session->notes . "\n" : '') . 'Extra ' . number_format($amount, 2) . ' — ' . ($label !== '' ? $label : 'charge')),
        ]);

        \App\Services\AuditService::log('session_extra_charge', 'session', $session->id, null, [
            'amount' => $amount,
            'label'  => $label,
            'table'  => (int) $session->table_id,
        ]);

        Response::success(['extra_charges' => $extra], 'Charge added');
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