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
}