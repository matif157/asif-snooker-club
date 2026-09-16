<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Customer;
use App\Services\SettingsService;

class CustomerController extends Controller
{
    public function index(): void
    {
        if (!user_can('customers.view')) {
            $this->error('You do not have permission to view customers.', 403);
        }

        $customers = Database::query(
            "SELECT * FROM customers WHERE status = 'active' ORDER BY last_visit_at DESC, name ASC"
        );
        $totalCustomers = Customer::count("status = 'active'");
        $vipCount       = Customer::count("status = 'active' AND category = 'vip'");
        $memberCount    = Customer::count("status = 'active' AND category = 'member'");
        $outstanding    = Database::fetchOne("SELECT COALESCE(SUM(outstanding_balance),0) AS total FROM customers WHERE outstanding_balance > 0");

        $this->view('customers/index', [
            'customers'      => $customers,
            'totalCustomers' => $totalCustomers,
            'vipCount'       => $vipCount,
            'memberCount'    => $memberCount,
            'totalOutstanding'=> $outstanding['total'] ?? 0,
        ]);
    }

    public function broadcast(): void
    {
        if (!user_can('customers.manage')) {
            $this->error('You do not have permission to manage customers.', 403);
        }

        $audience = Request::get('audience', 'active');
        $message  = Request::get('message', SettingsService::whatsappTemplate());
        $submitted = Request::get('preview') === '1';

        $customers = Customer::audience($audience);

        $prepared = [];
        foreach ($customers as $c) {
            $text = str_replace('{name}', $c['name'], $message);
            $prepared[] = [
                'name'    => $c['name'],
                'phone'   => $c['phone'],
                'outstanding' => (float) ($c['outstanding_balance'] ?? 0),
                'lastVisit'   => $c['last_visit_at'] ?? null,
                'message'     => $text,
                'waLink'      => Customer::whatsappLink($c['phone'], $text),
            ];
        }

        $this->view('customers/broadcast', [
            'audience'  => $audience,
            'message'   => $message,
            'customers' => $prepared,
            'submitted' => $submitted,
        ]);
    }

    public function create(): void
    {
        if (!user_can('customers.manage')) {
            $this->error('You do not have permission to manage customers.', 403);
        }

        $this->view('customers/create', []);
    }

    public function store(): void
    {
        if (!user_can('customers.manage')) {
            $this->error('You do not have permission to manage customers.', 403);
        }

        $data = Request::all();
        $errors = $this->validate($data, [
            'name'  => 'required|max:160',
            'phone' => 'phone',
        ]);

        if (!empty($errors)) {
            $this->redirect('/customers');
        }

        $phone    = Customer::normalizePhone($data['phone'] ?? '');
        $whatsapp = Customer::normalizePhone($data['whatsapp'] ?? $phone);

        $id = Customer::create([
            'name'     => $data['name'],
            'phone'    => $phone,
            'whatsapp' => $whatsapp,
            'email'    => $data['email'] ?? null,
            'category' => $data['category'] ?? 'regular',
            'notes'    => $data['notes'] ?? null,
            'cf_1'     => $data['cf_1'] ?? null,
            'cf_2'     => $data['cf_2'] ?? null,
            'cf_3'     => $data['cf_3'] ?? null,
            'cf_4'     => $data['cf_4'] ?? null,
            'cf_5'     => $data['cf_5'] ?? null,
            'status'   => 'active',
        ]);

        $pin = trim((string) ($data['portal_pin'] ?? ''));
        if (preg_match('/^\d{4}$/', $pin)) {
            Customer::setPortalPin($id, $pin);
            flash('success', 'Customer created with portal PIN — share it with them.');
        }

        if (Request::isAjax()) {
            Response::success(['id' => $id], 'Customer created');
        }
        Response::redirect('/customers');
    }

    public function show(int $id): void
    {
        if (!user_can('customers.view')) {
            $this->error('You do not have permission to view customers.', 403);
        }

        $customer = Customer::find($id);
        if (!$customer) {
            Response::redirect('/customers');
        }

        $sessions  = $customer->sessions();
        $payments  = $customer->payments();
        $bookings  = $customer->bookings();

        $this->view('customers/show', [
            'customer' => $customer->toArray(),
            'sessions' => $sessions,
            'payments' => $payments,
            'bookings' => $bookings,
        ]);
    }

    public function edit(int $id): void
    {
        if (!user_can('customers.manage')) {
            $this->error('You do not have permission to manage customers.', 403);
        }

        $customer = Customer::find($id);
        if (!$customer) {
            Response::redirect('/customers');
        }
        $this->view('customers/create', ['customer' => $customer->toArray(), 'isEdit' => true]);
    }

    public function update(int $id): void
    {
        if (!user_can('customers.manage')) {
            $this->error('You do not have permission to manage customers.', 403);
        }

        $customer = Customer::find($id);
        if (!$customer) {
            Response::redirect('/customers');
        }

        $data = Request::all();
        $phone = Customer::normalizePhone($data['phone'] ?? '');

        $customer->update([
            'name'     => $data['name'] ?? $customer->name,
            'phone'    => $phone,
            'whatsapp' => Customer::normalizePhone($data['whatsapp'] ?? $phone),
            'email'    => $data['email'] ?? $customer->email,
            'category' => $data['category'] ?? $customer->category,
            'notes'    => $data['notes'] ?? $customer->notes,
            'cf_1'     => $data['cf_1'] ?? $customer->cf_1,
            'cf_2'     => $data['cf_2'] ?? $customer->cf_2,
            'cf_3'     => $data['cf_3'] ?? $customer->cf_3,
            'cf_4'     => $data['cf_4'] ?? $customer->cf_4,
            'cf_5'     => $data['cf_5'] ?? $customer->cf_5,
        ]);

        $pin = trim((string) ($data['portal_pin'] ?? ''));
        if (preg_match('/^\d{4}$/', $pin)) {
            Customer::setPortalPin($id, $pin);
            flash('success', 'Portal PIN updated — share the new PIN with the customer.');
        }

        Response::redirect('/customers/' . $id);
    }

    public function apiSearch(): void
    {
        if (!user_can('customers.view')) {
            Response::error('Forbidden', 403);
        }

        $q = trim((string) (Request::get('term') ?? Request::get('q') ?? ''));
        if ($q === '') {
            Response::success([]);
        }

        $rows = Database::query(
            "SELECT id, name, phone, whatsapp, category, status
             FROM customers
             WHERE status = 'active'
               AND (name LIKE ? OR phone LIKE ? OR whatsapp LIKE ?)
             ORDER BY name ASC LIMIT 15",
            ["%{$q}%", "%{$q}%", "%{$q}%"]
        );

        Response::success($rows);
    }

    /**
     * Export active customers as CSV download.
     */
    public function export(): void
    {
        if (!user_can('customers.manage')) {
            $this->error('You do not have permission to export customers.', 403);
        }

        $audience = Request::get('audience', 'active');
        if ($audience === 'all') {
            $selected = Database::query(
                "SELECT name, phone, whatsapp, email, category, total_visits,
                        total_hours, total_spent, outstanding_balance, last_visit_at, notes
                 FROM customers
                 ORDER BY name ASC"
            );
        } else {
            $list = Customer::audience($audience, 500);
            $selected = [];
            foreach ($list as $c) {
                $full = Database::fetchOne(
                    'SELECT name, phone, whatsapp, email, category, total_visits,
                            total_hours, total_spent, outstanding_balance, last_visit_at, notes
                     FROM customers WHERE id = ?',
                    [$c['id']]
                );
                if ($full) {
                    $selected[] = $full;
                }
            }
        }
        $rows = $selected;

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="customers-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Name', 'Phone', 'WhatsApp', 'Email', 'Category', 'Visits', 'Hours', 'Total Spent', 'Outstanding', 'Last Visit', 'Notes'], ',', '"', '\\');
        foreach ($rows as $r) {
            fputcsv($out, array_values($r), ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    /**
     * Import customers from an uploaded CSV (Name, Phone, WhatsApp, Email, Category, Notes).
     */
    public function import(): void
    {
        if (!user_can('customers.manage')) {
            $this->error('You do not have permission to import customers.', 403);
        }

        if (!isset($_FILES['csv_file']) || ($_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Please select a CSV file to import.');
            Response::redirect('/customers');
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if ($handle === false) {
            flash('error', 'Could not read the uploaded file.');
            Response::redirect('/customers');
        }

        $header = fgetcsv($handle);
        $created = 0;
        $skipped = 0;
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            $data = array_combine(array_map('strtolower', $header ?? []), $row);
            $name = trim((string) ($data['name'] ?? ''));
            $phone = trim((string) ($data['phone'] ?? ''));
            if ($name === '' && $phone === '') {
                continue;
            }

            $phoneNormalized = $phone !== '' ? Customer::normalizePhone($phone) : '';
            if ($phoneNormalized !== '' && Customer::findBy('phone', $phoneNormalized)) {
                $skipped++;
                continue;
            }

            $category = strtolower(trim((string) ($data['category'] ?? 'regular')));
            if (!in_array($category, ['regular', 'vip', 'member', 'tournament', 'inactive'])) {
                $category = 'regular';
            }

            Customer::create([
                'name'     => $name !== '' ? $name : ($phoneNormalized !== '' ? $phoneNormalized : 'Imported Row ' . $rowNum),
                'phone'    => $phoneNormalized !== '' ? $phoneNormalized : null,
                'whatsapp' => $phoneNormalized !== '' ? $phoneNormalized : null,
                'email'    => trim((string) ($data['email'] ?? '')) ?: null,
                'category' => $category,
                'notes'    => trim((string) ($data['notes'] ?? '')) ?: null,
            ]);
            $created++;
        }

        fclose($handle);

        \App\Services\AuditService::log('customers_imported', 'customer', null, null, ['created' => $created, 'skipped' => $skipped]);

        flash('success', "Imported {$created} customer(s)" . ($skipped > 0 ? ", skipped {$skipped} duplicate(s)." : '.'));
        Response::redirect('/customers');
    }
}