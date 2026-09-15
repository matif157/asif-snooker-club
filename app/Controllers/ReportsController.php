<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class ReportsController extends Controller
{
    public function daily(): void
    {
        if (!user_can('reports.view') && !user_can('finance.view')) {
            $this->error('You do not have permission to view reports.');
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
             WHERE CAST(created_at AS DATE) = ?",
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
            $this->error('You do not have permission to view reports.');
        }

        $days = min(90, max(7, (int) ($_GET['days'] ?? 30)));

        // Revenue by hour of day (peak usage)
        $hours = Database::query(
            "SELECT HOUR(paid_at) AS hour, ROUND(SUM(amount), 0) AS revenue
             FROM payments
             WHERE paid_at >= ?
               AND status = 'paid'
             GROUP BY HOUR(paid_at)
             ORDER BY hour ASC",
            [date('Y-m-d', strtotime("-{$days} days")) . ' 00:00:00']
        );
        $byHour = array_fill(0, 24, 0.0);
        foreach ($hours as $r) {
            $byHour[(int) $r['hour']] = (float) $r['revenue'];
        }

        // Table utilization (sessions per table)
        $utilization = Database::query(
            "SELECT t.number, t.name, t.status, COUNT(s.id) AS sessions,
                    COALESCE(ROUND(SUM(s.amount), 0), 0) AS revenue
             FROM tables t
             LEFT JOIN sessions s ON s.table_id = t.id
                AND s.start_time >= ?
             GROUP BY t.id
             ORDER BY sessions DESC",
            [date('Y-m-d', strtotime("-{$days} days")) . ' 00:00:00']
        );

        // Top customers
        $topCustomers = Database::query(
            "SELECT c.id, c.name, c.category, c.phone,
                    COUNT(s.id) AS visits,
                    COALESCE(ROUND(SUM(s.amount), 0), 0) AS spent
             FROM customers c
             JOIN sessions s ON s.customer_id = c.id
                AND s.start_time >= ?
             GROUP BY c.id
             ORDER BY spent DESC
             LIMIT 10",
            [date('Y-m-d', strtotime("-{$days} days")) . ' 00:00:00']
        );

        // Revenue trend last N days (compact)
        $start = date('Y-m-d', strtotime("-{$days} days")) . ' 00:00:00';
        $trendBase = Database::query(
            "SELECT DATE(paid_at) AS d, COALESCE(SUM(amount), 0) AS total
             FROM payments
             WHERE paid_at >= ? AND status = 'paid'
             GROUP BY DATE(paid_at)",
            [$start]
        );
        $trend = [];
        $byDay = array_column($trendBase, 'total', 'd');
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $trend[] = ['date' => $d, 'revenue' => (float) ($byDay[$d] ?? 0)];
        }

        $this->view('reports/analytics', [
            'days'        => $days,
            'byHour'      => $byHour,
            'utilization' => $utilization,
            'topCustomers'=> $topCustomers,
            'trend'       => $trend,
        ]);
    }
}