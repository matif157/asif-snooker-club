<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Payment;

/**
 * Udhaar / loan ledger: customers who owe money and the sessions that
 * created those balances. Settlements are recorded through the normal
 * payments flow so revenue reporting stays consistent.
 */
class LoanController extends Controller
{
    public function index(): void
    {
        if (!user_can('payments.view')) {
            $this->error('You do not have permission to view loans.', 403);
        }

        $customers = Database::query(
            "SELECT c.id, c.name, c.phone, c.whatsapp, c.outstanding_balance, c.last_visit_at,
                    (SELECT COUNT(*) FROM sessions s
                      WHERE s.customer_id = c.id AND s.is_loan = 1 AND s.loan_amount > 0) AS loan_count
             FROM customers c
             WHERE c.outstanding_balance > 0
             ORDER BY c.outstanding_balance DESC"
        );

        $loans = Database::query(
            "SELECT s.*,
                    t.number AS table_number,
                    t.name   AS table_name,
                    c.name   AS customer_name,
                    c.phone  AS customer_phone,
                    COALESCE((
                        SELECT SUM(p.amount) FROM payments p
                         WHERE p.session_id = s.id AND p.status = 'paid'
                    ), 0) AS paid_total
             FROM sessions s
             JOIN tables t ON t.id = s.table_id
             LEFT JOIN customers c ON c.id = s.customer_id
             WHERE s.is_loan = 1 AND s.loan_amount > 0
             ORDER BY s.id DESC
             LIMIT 200"
        );

        $totalOutstanding = 0.0;
        foreach ($customers as $c) {
            $totalOutstanding += (float) $c['outstanding_balance'];
        }

        $openLoanSessions = 0;
        $openLoanAmount   = 0.0;
        foreach ($loans as $l) {
            $openLoanSessions++;
            $openLoanAmount += (float) $l['loan_amount'];
        }

        $this->view('loans/index', [
            'customers'        => $customers,
            'loans'            => $loans,
            'totalOutstanding' => $totalOutstanding,
            'openLoanSessions' => $openLoanSessions,
            'openLoanAmount'   => $openLoanAmount,
            'methods'          => Payment::METHODS,
        ]);
    }
}
