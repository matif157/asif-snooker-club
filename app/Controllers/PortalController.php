<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Customer;

/**
 * Public self-service portal — customers look themselves up by phone number
 * to see their balance, visits and payment history.
 */
class PortalController extends Controller
{
    public function index(): void
    {
        $customer = null;
        $sessions = [];
        $payments = [];
        $verified = false;
        $error = null;

        $phone = trim((string) ($_GET['phone'] ?? ''));
        if ($phone !== '') {
            $customer = Customer::findByPhone($phone);
            $verified = true;

            if ($customer === null) {
                $error = 'No account found for that number. Call the club to confirm, or try the number you registered with.';
            } else {
                $sessions = Database::query(
                    "SELECT s.*, t.number AS table_number, t.name AS table_name,
                            u.name AS staff_name
                     FROM sessions s
                     JOIN tables t ON t.id = s.table_id
                     LEFT JOIN users u ON u.id = s.staff_id
                     WHERE s.customer_id = ?
                     ORDER BY s.id DESC LIMIT 6",
                    [(int) $customer['id']]
                );
                $payments = Database::query(
                    "SELECT * FROM payments
                     WHERE customer_id = ?
                     ORDER BY id DESC LIMIT 6",
                    [(int) $customer['id']]
                );
            }
        }

        $this->view('portal/index', [
            'customer' => $customer,
            'sessions' => $sessions,
            'payments' => $payments,
            'phone'    => $phone,
            'verified' => $verified,
            'error'    => $error,
        ], 'portal');
    }
}