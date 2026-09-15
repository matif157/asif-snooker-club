<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function index(): void
    {
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

    public function create(): void
    {
        $this->view('customers/create', []);
    }

    public function store(): void
    {
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
            'status'   => 'active',
        ]);

        if (Request::isAjax()) {
            Response::success(['id' => $id], 'Customer created');
        }
        Response::redirect('/customers');
    }

    public function show(int $id): void
    {
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
        $customer = Customer::find($id);
        if (!$customer) {
            Response::redirect('/customers');
        }
        $this->view('customers/create', ['customer' => $customer->toArray(), 'isEdit' => true]);
    }

    public function update(int $id): void
    {
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
        ]);

        Response::redirect('/customers/' . $id);
    }

    public function apiSearch(): void
    {
        $term = Request::get('q', '');
        $customers = Customer::search($term);
        Response::success($customers);
    }
}