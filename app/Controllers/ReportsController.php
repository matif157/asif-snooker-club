<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\AuditService;
use App\Services\SettingsService;

class ReportsController extends Controller
{
    public function daily(): void
    {
        if (!user_can('reports.view') && !user_can('finance.view')) {
            $this->error('You do not have permission to view reports.', 403);
        }

        $date = $_GET['date'] ?? date('Y-m-d');
        $rows = Database::query(
            "SELECT p.method,
                    COUNT(DISTINCT p.id) AS txns,
                    SUM(p.amount) AS method_total
             FROM payments p
             WHERE p.paid_at LIKE ?
               AND p.status = 'paid'
             GROUP BY p.method",
            [$date . '%']
        );
        $methods = [];
        $collected = 0;
        foreach ($rows as $r) {
            $methods[$r['method']] = (float) $r['method_total'];
            $collected += (float) $r['method_total'];
        }

        $expenses = Database::fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS total
             FROM expenses
             WHERE status = 'approved'
               AND CAST(created_at AS DATE) = ?",
            [$date]
        );
        $expenseTotal = (float) ($expenses['total'] ?? 0);

        $sessions = Database::fetchOne(
            "SELECT COUNT(*) AS count,
                    COALESCE(SUM(amount), 0) AS billed,
                    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) AS paid
             FROM sessions
             WHERE CAST(created_at AS DATE) = ?",
            [$date]
        );

        $outstanding = Database::fetchOne(
            "SELECT COALESCE(SUM(p.amount - COALESCE(p.amount_paid, 0)), 0) AS total
             FROM (
                 SELECT s.id, s.amount, 0 AS amount_paid
                 FROM sessions s
                 WHERE s.payment_status NOT IN ('paid', 'cancelled')
             ) p"
        );

        $this->view('reports/daily', [
            'date'        => $date,
            'methods'     => $methods,
            'collected'   => $collected,
            'expenseTotal'=> $expenseTotal,
            'sessionStats'=> $sessions,
            'outstanding' => $outstanding['total'] ?? 0,
            'activity'    => \App\Services\AuditService::recent(15),
        ]);
    }

    public function analytics(): void
    {
        if (!user_can('reports.view') && !user_can('finance.view')) {
            $this->error('You do not have permission to view reports.', 403);
        }

        $days = min(90, max(7, (int) ($_GET['days'] ?? 30)));
        $start = date('Y-m-d', strtotime("-{$days} days")) . ' 00:00:00';

        // ── KPI header ────────────────────────────────────────────────
        $kpi = Database::fetchOne(
            "SELECT
                COALESCE((SELECT ROUND(SUM(amount),0) FROM payments WHERE paid_at >= ? AND status='paid'), 0) AS revenue,
                COALESCE((SELECT COUNT(*) FROM sessions WHERE start_time >= ? AND status='completed'), 0) AS sessions,
                COALESCE((SELECT ROUND(SUM(amount),0) FROM sessions WHERE end_time IS NOT NULL AND end_time <= NOW() AND payment_status != 'paid'), 0) AS outstanding",
            [$start, $start]
        );
        $revenue     = (float) ($kpi['revenue'] ?? 0);
        $sessionCount= (int) ($kpi['sessions'] ?? 0);
        $outstanding = (float) ($kpi['outstanding'] ?? 0);

        // ── Revenue by hour of day (peak usage, revenue + sessions) ───
        $peak = Database::query(
            "SELECT HOUR(paid_at) AS hour, ROUND(SUM(amount), 0) AS revenue
             FROM payments WHERE paid_at >= ? AND status = 'paid'
             GROUP BY HOUR(paid_at) ORDER BY hour ASC",
            [$start]
        );
        $peakSessions = Database::query(
            "SELECT HOUR(start_time) AS hour, COUNT(*) AS n
             FROM sessions WHERE start_time >= ? GROUP BY HOUR(start_time)",
            [$start]
        );
        $byHour = array_fill(0, 24, 0.0);
        foreach ($peak as $r) {
            $byHour[(int) $r['hour']] = (float) $r['revenue'];
        }
        $byHourSessions = array_fill(0, 24, 0);
        foreach ($peakSessions as $r) {
            $byHourSessions[(int) $r['hour']] = (int) $r['n'];
        }

        // ── Table utilization (sessions, hours, revenue per table) ────
        $utilization = Database::query(
            "SELECT t.number, t.name, t.status,
                    COUNT(s.id) AS sessions,
                    COALESCE(ROUND(SUM(TIMESTAMPDIFF(MINUTE, s.start_time, COALESCE(s.end_time, NOW())) / 60), 1), 0) AS hours,
                    COALESCE(ROUND(SUM(s.amount), 0), 0) AS revenue
             FROM tables t
             LEFT JOIN sessions s ON s.table_id = t.id AND s.start_time >= ? AND s.status != 'cancelled'
             GROUP BY t.id
             ORDER BY sessions DESC",
            [$start]
        );

        // ── Top customers ─────────────────────────────────────────────
        $topCustomers = Database::query(
            "SELECT c.id, c.name, c.category, c.phone,
                    COUNT(s.id) AS visits,
                    COALESCE(ROUND(SUM(TIMESTAMPDIFF(MINUTE, s.start_time, COALESCE(s.end_time, NOW())) / 60), 1), 0) AS hours,
                    COALESCE(ROUND(SUM(s.amount), 0), 0) AS spent
             FROM customers c
             JOIN sessions s ON s.customer_id = c.id AND s.start_time >= ? AND s.status != 'cancelled'
             GROUP BY c.id
             ORDER BY spent DESC
             LIMIT 10",
            [$start]
        );

        // ── Customers by category (all-time spend, period visits) ─────
        $categoryBreakdown = Database::query(
            "SELECT c.category,
                    COUNT(DISTINCT c.id) AS customers,
                    COALESCE(ROUND(SUM(s.amount), 0), 0) AS spent
             FROM customers c
             LEFT JOIN sessions s ON s.customer_id = c.id AND s.start_time >= ? AND s.status != 'cancelled'
             GROUP BY c.category
             ORDER BY spent DESC",
            [$start]
        );

        // ── Bookings by status (period) ────────────────────────────────
        $bookingsByStatus = Database::query(
            "SELECT status, COUNT(*) AS n FROM bookings
             WHERE created_at >= ? OR booking_date >= DATE(?) 
             GROUP BY status ORDER BY n DESC",
            [$start, $start]
        );

        // ── Daily revenue vs expenses + session count (chart) ─────────
        $revDays = Database::query(
            "SELECT DATE(paid_at) AS d, COALESCE(SUM(amount), 0) AS total
             FROM payments WHERE paid_at >= ? AND status='paid'
             GROUP BY DATE(paid_at)", [$start]
        );
        $expDays = Database::query(
            "SELECT expense_date AS d, COALESCE(SUM(amount), 0) AS total
             FROM expenses WHERE expense_date >= DATE(?) AND status='approved'
             GROUP BY expense_date", [$start]
        );
        $sesDays = Database::query(
            "SELECT DATE(start_time) AS d, COUNT(*) AS n
             FROM sessions WHERE start_time >= ? AND status='completed'
             GROUP BY DATE(start_time)", [$start]
        );
        $revByDay  = array_column($revDays, 'total', 'd');
        $expByDay  = array_column($expDays, 'total', 'd');
        $sesByDay  = array_column($sesDays, 'n', 'd');

        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $trend[] = [
                'date'     => $d,
                'revenue'  => (float) ($revByDay[$d] ?? 0),
                'expenses' => (float) ($expByDay[$d] ?? 0),
                'sessions' => (int) ($sesByDay[$d] ?? 0),
            ];
        }

        $this->view('reports/analytics', [
            'days'          => $days,
            'revenue'       => $revenue,
            'sessionCount'  => $sessionCount,
            'avgSession'    => $sessionCount > 0 ? round($revenue / $sessionCount) : 0,
            'outstanding'   => $outstanding,
            'byHour'        => $byHour,
            'byHourSessions'=> $byHourSessions,
            'utilization'   => $utilization,
            'topCustomers'  => $topCustomers,
            'categoryBreakdown' => $categoryBreakdown,
            'bookingsByStatus'  => $bookingsByStatus,
            'trend'         => $trend,
            'currency'      => (string) SettingsService::get('currency', 'Rs'),
        ]);
    }

    public function pnl(): void
    {
        if (!user_can('reports.view') && !user_can('finance.view')) {
            $this->error('You do not have permission to view reports.', 403);
        }

        $month = Request::get('month');
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $month) ? $month : date('Y-m');
        [$y, $m] = array_map('intval', explode('-', $month));
        $monthStart = sprintf('%04d-%02d-01 00:00:00', $y, $m);
        $daysInMonth = (int) date('t', strtotime(substr($monthStart, 0, 10)));
        $monthEnd   = sprintf('%04d-%02d-%02d 23:59:59', $y, $m, $daysInMonth);

        // Revenue
        $revenueRow = Database::fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS total, COUNT(*) AS txns
             FROM payments WHERE paid_at BETWEEN ? AND ? AND status = 'paid'",
            [$monthStart, $monthEnd]
        );
        $revenue = (float) ($revenueRow['total'] ?? 0);
        $txns    = (int) ($revenueRow['txns'] ?? 0);

        $byMethod = Database::query(
            "SELECT method, COALESCE(SUM(amount), 0) AS total, COUNT(*) AS count
             FROM payments WHERE paid_at BETWEEN ? AND ? AND status = 'paid'
             GROUP BY method ORDER BY total DESC",
            [$monthStart, $monthEnd]
        );

        // Expenses (approved only)
        $expenseRow = Database::fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS total, COUNT(*) AS count
             FROM expenses WHERE expense_date BETWEEN ? AND ? AND status = 'approved'",
            [substr($monthStart, 0, 10), substr($monthEnd, 0, 10)]
        );
        $expenses = (float) ($expenseRow['total'] ?? 0);
        $expCount = (int) ($expenseRow['count'] ?? 0);

        $byCategory = Database::query(
            "SELECT category, COALESCE(SUM(amount), 0) AS total, COUNT(*) AS count
             FROM expenses WHERE expense_date BETWEEN ? AND ? AND status = 'approved'
             GROUP BY category ORDER BY total DESC",
            [substr($monthStart, 0, 10), substr($monthEnd, 0, 10)]
        );

        // Sessions billed in month (for comparison)
        $sessionRow = Database::fetchOne(
            "SELECT COUNT(*) AS count, COALESCE(SUM(amount), 0) AS billed
             FROM sessions WHERE start_time BETWEEN ? AND ? AND status = 'completed'",
            [$monthStart, $monthEnd]
        );

        // Outstanding balance at period end
        $outstanding = (float) (
            Database::fetchOne(
                "SELECT COALESCE(SUM(amount), 0) AS t FROM sessions
                 WHERE end_time IS NOT NULL AND end_time <= ? AND payment_status != 'paid'",
                [$monthEnd]
            )['t'] ?? 0
        );

        // Per-day revenue vs expenses for chart
        $revDays = Database::query(
            "SELECT DATE(paid_at) AS d, COALESCE(SUM(amount), 0) AS total
             FROM payments WHERE paid_at BETWEEN ? AND ? AND status = 'paid'
             GROUP BY DATE(paid_at)",
            [$monthStart, $monthEnd]
        );
        $expDays = Database::query(
            "SELECT expense_date AS d, COALESCE(SUM(amount), 0) AS total
             FROM expenses WHERE expense_date BETWEEN ? AND ? AND status = 'approved'
             GROUP BY expense_date",
            [substr($monthStart, 0, 10), substr($monthEnd, 0, 10)]
        );
        $revByDay = array_column($revDays, 'total', 'd');
        $expByDay = array_column($expDays, 'total', 'd');

        $days = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $d = sprintf('%04d-%02d-%02d', $y, $m, $i);
            $days[] = [
                'date'    => $d,
                'revenue' => (float) ($revByDay[$d] ?? 0),
                'expense' => (float) ($expByDay[$d] ?? 0),
            ];
        }

        $this->view('reports/pnl', [
            'month'       => $month,
            'display'     => date('F Y', strtotime(substr($monthStart, 0, 10))),
            'prevMonth'   => date('Y-m', strtotime(substr($monthStart, 0, 10) . ' -1 month')),
            'nextMonth'   => date('Y-m', strtotime(substr($monthStart, 0, 10) . ' +1 month')),
            'revenue'     => $revenue,
            'txns'        => $txns,
            'byMethod'    => $byMethod,
            'expenses'    => $expenses,
            'expCount'    => $expCount,
            'byCategory'  => $byCategory,
            'sessionCount'=> (int) ($sessionRow['count'] ?? 0),
            'sessionBilled' => (float) ($sessionRow['billed'] ?? 0),
            'outstanding' => $outstanding,
            'net'         => $revenue - $expenses,
            'days'        => $days,
        ]);
    }

    public function followup(): void
    {
        if (!user_can('reports.view') && !user_can('finance.view')) {
            $this->error('You do not have permission to view reports.', 403);
        }

        $outstanding = \App\Models\Payment::outstandingCustomers(30);

        $noShows = Database::query(
            "SELECT b.*, t.number AS table_number,
                    c.name AS customer_linked_name,
                    c.phone AS linked_phone
             FROM bookings b
             JOIN tables t ON t.id = b.table_id
             LEFT JOIN customers c ON c.id = b.customer_id
             WHERE b.status IN ('no_show','cancelled','expired')
               AND (b.booking_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY))
             ORDER BY b.booking_date DESC
             LIMIT 40"
        );

        $messageReminder = SettingsService::get(
            'reminder_template',
            'Assalam o Alaikum {name}! This is a friendly reminder from ' . SettingsService::clubName() . ' that your balance of Rs {amount} is due. Please settle at your earliest convenience. Thank you!'
        );
        $messageNoShow = SettingsService::get(
            'no_show_template',
            'Assalam o Alaikum {name}! You missed your scheduled booking at ' . SettingsService::clubName() . '. Let us know if you would like to rebook. Thank you!'
        );

        $outstandingPrepared = [];
        foreach ($outstanding as $c) {
            $text = str_replace(
                ['{name}', '{amount}'],
                [$c['name'], number_format((float) $c['outstanding_balance'])],
                $messageReminder
            );
            $outstandingPrepared[] = [
                'name'    => $c['name'],
                'phone'   => $c['phone'],
                'balance' => (float) $c['outstanding_balance'],
                'lastVisit' => $c['last_visit_at'] ?? null,
                'message' => $text,
                'waLink'  => \App\Models\Customer::whatsappLink($c['phone'], $text),
            ];
        }

        $noShowPrepared = [];
        foreach ($noShows as $n) {
            $name = $n['customer_linked_name'] ?? $n['customer_name'] ?? 'Walk-in';
            $phone = $n['linked_phone'] ?? $n['customer_phone'] ?? '';
            $text = str_replace(['{name}', '{date}'], [$name, $n['booking_date']], $messageNoShow);
            $noShowPrepared[] = [
                'name'    => $name,
                'phone'   => $phone,
                'date'    => $n['booking_date'],
                'table'   => $n['table_number'],
                'status'  => $n['status'],
                'message' => $text,
                'waLink'  => $phone ? \App\Models\Customer::whatsappLink($phone, $text) : null,
            ];
        }

        $this->view('reports/followup', [
            'outstanding' => $outstandingPrepared,
            'noShows'     => $noShowPrepared,
            'reminderTemplate' => $messageReminder,
            'noShowTemplate'   => $messageNoShow,
        ]);
    }

    public function audit(): void
    {
        if (!user_can('reports.view') && !user_can('finance.view')) {
            $this->error('You do not have permission to view reports.', 403);
        }

        $page   = max(1, (int) (Request::get('page') ?: 1));
        $perPage = 60;
        $result = AuditService::search(
            Request::get('action') ?: null,
            Request::get('entity') ?: null,
            Request::get('from') ?: null,
            Request::get('to') ?: null,
            ($page - 1) * $perPage,
            $perPage
        );

        $this->view('reports/audit', [
            'rows'     => $result['rows'],
            'total'    => $result['total'],
            'page'     => $page,
            'perPage'  => $result['perPage'],
            'filters'  => [
                'action' => Request::get('action') ?: '',
                'entity' => Request::get('entity') ?: '',
                'from'   => Request::get('from') ?: '',
                'to'     => Request::get('to') ?: '',
            ],
            'actions'  => AuditService::actionList(),
            'entities' => AuditService::entityList(),
        ]);
    }
}