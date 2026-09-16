<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\ClubSession;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Table as TableModel;

class DashboardController extends Controller
{
    public function index(): void
    {
        $tables = TableModel::activeTables();

        $longRunMinutes = 90;

        // Mark tables ending soon based on active sessions
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
        }
        unset($t);

        $activeTables = array_filter($tables, fn($t) => in_array($t['status'], ['occupied', 'reserved']));
        $availableTables = array_filter($tables, fn($t) => $t['status'] === 'available');

        // Sessions stats
        $sessionStats = ClubSession::todayStats();

        // Payments
        $todayPayments = Payment::todayRevenueByMethod();
        $todayRevenue = array_sum(array_column($todayPayments, 'total'));

        // Outstanding
        $outstanding = Payment::outstandingCustomers(5);

        // Expenses
        $todayExpenses = Expense::todayTotal();

        // Upcoming bookings
        $upcomingBookings = \App\Models\Booking::upcoming(8);

        // Recent sessions
        $recentSessions = ClubSession::recent(10);

        // Active sessions for command center
        $activeSessions = ClubSession::activeSessions();

        // Estimate profit
        $estimatedProfit = $todayRevenue - $todayExpenses;

        // --- Overhaul widgets ------------------------------------------------

        $todayStart  = date('Y-m-d') . ' 00:00:00';
        $todayNext   = date('Y-m-d', strtotime('+1 day')) . ' 00:00:00';
        $yestStart   = date('Y-m-d', strtotime('-1 day')) . ' 00:00:00';
        $weekStart   = date('Y-m-d', strtotime('monday this week')) . ' 00:00:00';
        $weekEnd     = date('Y-m-d', strtotime('monday this week +7 days')) . ' 00:00:00';
        $prevWeekStart = date('Y-m-d', strtotime('monday last week')) . ' 00:00:00';

        // Yesterday vs today (revenue + sessions)
        $yesterdayRevenue = (float) (Database::query(
            "SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE status = 'paid' AND paid_at >= ? AND paid_at < ?",
            [$yestStart, $todayStart]
        )[0]['total'] ?? 0);

        $yesterdaySessions = (int) (Database::query(
            "SELECT COUNT(*) AS c FROM sessions WHERE start_time >= ? AND start_time < ? AND status NOT IN ('cancelled')",
            [$yestStart, $todayStart]
        )[0]['c'] ?? 0);

        // This week vs last week
        $weekRevenue = (float) (Database::query(
            "SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE status = 'paid' AND paid_at >= ? AND paid_at < ?",
            [$weekStart, $weekEnd]
        )[0]['total'] ?? 0);

        $prevWeekRevenue = (float) (Database::query(
            "SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE status = 'paid' AND paid_at >= ? AND paid_at < ?",
            [$prevWeekStart, $weekStart]
        )[0]['total'] ?? 0);

        $weekSessions = (int) (Database::query(
            "SELECT COUNT(*) AS c FROM sessions WHERE start_time >= ? AND start_time < ? AND status NOT IN ('cancelled')",
            [$weekStart, $weekEnd]
        )[0]['c'] ?? 0);

        $prevWeekSessions = (int) (Database::query(
            "SELECT COUNT(*) AS c FROM sessions WHERE start_time >= ? AND start_time < ? AND status NOT IN ('cancelled')",
            [$prevWeekStart, $weekStart]
        )[0]['c'] ?? 0);

        // Top tables today
        $topTablesToday = Database::query(
            "SELECT t.id, t.number, t.name,
                    COUNT(s.id) AS session_count,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time)) / 60, 0) AS hours,
                    COALESCE(SUM(s.amount), 0) AS revenue
             FROM sessions s
             JOIN tables t ON t.id = s.table_id
             WHERE s.start_time >= ? AND s.start_time < ? AND s.status IN ('active','paused','completed')
             GROUP BY s.table_id
             ORDER BY revenue DESC, hours DESC
             LIMIT 6",
            [$todayStart, $todayNext]
        );

        // Alerts
        $unpaidToday = Database::query(
            "SELECT COUNT(*) AS c, COALESCE(SUM(amount), 0) AS total
             FROM sessions
             WHERE status = 'completed' AND payment_status <> 'paid' AND start_time >= ? AND start_time < ?",
            [$todayStart, $todayNext]
        )[0] ?? ['c' => 0, 'total' => 0];

        $longRunning = array_values(array_filter(
            $activeSessions,
            function (array $s) use ($longRunMinutes): bool {
                $elapsed = time() - strtotime($s['start_time']);
                $elapsed -= (int) ($s['paused_total_sec'] ?? 0);
                return $elapsed >= $longRunMinutes * 60;
            }
        ));
        usort($longRunning, fn($a, $b) => strtotime($b['start_time']) <=> strtotime($a['start_time']));
        $longRunning = array_slice($longRunning, 0, 4);

        $arrivingSoon = Database::query(
            "SELECT b.id, b.customer_name, b.start_time, b.end_time, b.status,
                    t.number AS table_number
             FROM bookings b
             JOIN tables t ON t.id = b.table_id
             WHERE b.booking_date = CURDATE()
               AND b.status IN ('requested','confirmed','arrived')
               AND b.start_time BETWEEN TIME(NOW() - INTERVAL 5 MINUTE) AND TIME(NOW() + INTERVAL 60 MINUTE)
             ORDER BY b.start_time
             LIMIT 5"
        );

        $maintenanceCount = count(array_filter($tables, fn($t) => $t['status'] === 'maintenance'));

        // Bookings awaiting approval from the desk (e.g. portal requests)
        $pendingBookings = (int) (Database::query(
            "SELECT COUNT(*) AS c FROM bookings WHERE status = 'requested'"
        )[0]['c'] ?? 0);

        // Cameras assigned to tables (for command-center shortcuts)
        $tableCameras = [];
        foreach (TableModel::camerasByTable() as $row) {
            $tableCameras[(int) $row['table_id']] = $row;
        }

        $pct = fn(float $cur, float $prev): int => $prev > 0
            ? (int) round(($cur - $prev) / $prev * 100)
            : ($cur > 0 ? 100 : 0);

        $this->view('dashboard/index', [
            'tables'              => $tables,
            'activeTables'        => $activeTables,
            'availableTables'     => $availableTables,
            'sessionStats'        => $sessionStats,
            'todayPayments'       => $todayPayments,
            'todayRevenue'        => $todayRevenue,
            'outstanding'         => $outstanding,
            'todayExpenses'       => $todayExpenses,
            'upcomingBookings'    => $upcomingBookings,
            'recentSessions'      => $recentSessions,
            'activeSessions'      => $activeSessions,
            'estimatedProfit'     => $estimatedProfit,
            // Overhaul widgets
            'yesterdayRevenue'    => $yesterdayRevenue,
            'yesterdaySessions'   => $yesterdaySessions,
            'revenueDelta'        => $pct($todayRevenue, $yesterdayRevenue),
            'sessionsDelta'       => $pct((float) $sessionStats['count'], (float) $yesterdaySessions),
            'weekRevenue'         => $weekRevenue,
            'weekSessions'        => $weekSessions,
            'weekRevenueDelta'    => $pct($weekRevenue, $prevWeekRevenue),
            'weekSessionsDelta'   => $pct((float) $weekSessions, (float) $prevWeekSessions),
            'topTablesToday'      => $topTablesToday,
            'unpaidToday'         => (int) $unpaidToday['c'],
            'unpaidTodayTotal'    => (float) $unpaidToday['total'],
            'longRunning'         => $longRunning,
            'longRunMinutes'      => $longRunMinutes,
            'arrivingSoon'        => $arrivingSoon,
            'maintenanceCount'    => $maintenanceCount,
            'pendingBookings'     => $pendingBookings,
            'tableCameras'        => $tableCameras,
        ]);
    }

    public function apiStats(): void
    {
        $stats = ClubSession::todayStats();
        $payments = Payment::todayRevenueByMethod();
        $expenses = Expense::todayTotal();
        $tables  = TableModel::activeTables();
        $occupiedCount = count(array_filter($tables, fn($t) => in_array($t['status'], ['occupied', 'reserved'])));

        Response::success([
            'revenue'       => array_sum(array_column($payments, 'total')),
            'sessions'      => $stats['count'],
            'collected'     => $stats['collected'],
            'expenses'      => $expenses,
            'active_tables' => $occupiedCount,
            'total_tables'  => count($tables),
            'available'     => count($tables) - $occupiedCount,
            'profit'        => array_sum(array_column($payments, 'total')) - $expenses,
        ]);
    }

    public function apiActivity(): void
    {
        $sessions = ClubSession::recent(15);
        $upcoming = \App\Models\Booking::upcoming(5);
        $payments = Payment::recent(10);

        Response::success([
            'sessions'  => $sessions,
            'bookings'  => $upcoming,
            'payments'  => $payments,
        ]);
    }

    /**
     * Per-day revenue & expenses for the last N days → chart data.
     */
    public function apiRevenueTrend(): void
    {
        $days = min(90, max(7, (int) (Request::input('days') ?? 30)));

        $start = date('Y-m-d', strtotime("-{$days} days")) . ' 00:00:00';

        $payments = Database::query(
            "SELECT DATE(paid_at) AS d, COALESCE(SUM(amount), 0) AS total
             FROM payments
             WHERE paid_at >= ? AND status = 'paid'
             GROUP BY DATE(paid_at)",
            [$start]
        );

        $expenses = Database::query(
            "SELECT DATE(created_at) AS d, COALESCE(SUM(amount), 0) AS total
             FROM expenses
             WHERE status = 'approved'
               AND created_at >= ?
             GROUP BY DATE(created_at)",
            [$start]
        );

        $revenueByDay  = array_column($payments, 'total', 'd');
        $expenseByDay  = array_column($expenses, 'total', 'd');
        $labels = [];
        $revenue = [];
        $expense = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = $d;
            $revenue[]  = (float) ($revenueByDay[$d] ?? 0);
            $expense[]  = (float) ($expenseByDay[$d] ?? 0);
        }

        Response::success([
            'days'     => $days,
            'labels'   => $labels,
            'revenue'  => $revenue,
            'expenses' => $expense,
        ]);
    }
}