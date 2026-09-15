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
             WHERE created_at >= ?
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