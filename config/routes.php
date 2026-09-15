<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\CustomerController;
use App\Controllers\DashboardController;
use App\Controllers\ExpenseController;
use App\Controllers\PaymentController;
use App\Controllers\SessionController;
use App\Controllers\TableController;

/** @var App\Core\Router $router */

// ── Auth routes ────────────────────────────────────────────────────────
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// ── Authenticated web routes ───────────────────────────────────────────
$router->middleware(function () {
    if (!is_authenticated()) {
        http_response_code(401);
        header('Location: /login');
        return false;
    }
    return true;
});

$router->get('/', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

// Tables
$router->get('/tables', [TableController::class, 'index']);
$router->get('/tables/create', [TableController::class, 'create']);
$router->post('/tables', [TableController::class, 'store']);
$router->get('/tables/{id}/edit', [TableController::class, 'edit']);
$router->post('/tables/{id}', [TableController::class, 'update']);
$router->post('/tables/{id}/delete', [TableController::class, 'destroy']);
$router->post('/tables/{id}/toggle', [TableController::class, 'toggleStatus']);

// Customers
$router->get('/customers', [CustomerController::class, 'index']);
$router->get('/customers/create', [CustomerController::class, 'create']);
$router->post('/customers', [CustomerController::class, 'store']);
$router->get('/customers/{id}', [CustomerController::class, 'show']);
$router->get('/customers/{id}/edit', [CustomerController::class, 'edit']);
$router->post('/customers/{id}', [CustomerController::class, 'update']);

// Sessions
$router->get('/sessions', [SessionController::class, 'index']);
$router->get('/sessions/active', [SessionController::class, 'active']);
$router->post('/sessions', [SessionController::class, 'store']);
$router->get('/sessions/{id}', [SessionController::class, 'show']);
$router->post('/sessions/{id}/end', [SessionController::class, 'end']);
$router->post('/sessions/{id}/logout', [SessionController::class, 'logout']);

// Bookings
$router->get('/bookings', [BookingController::class, 'index']);
$router->post('/bookings', [BookingController::class, 'store']);
$router->post('/bookings/{id}/status', [BookingController::class, 'updateStatus']);

// Payments
$router->get('/payments', [PaymentController::class, 'index']);
$router->post('/payments', [PaymentController::class, 'store']);

// Expenses
$router->get('/expenses', [ExpenseController::class, 'index']);
$router->post('/expenses', [ExpenseController::class, 'store']);

// ── API / AJAX routes ──────────────────────────────────────────────────
$router->get('/api/tables', [TableController::class, 'apiList']);
$router->post('/api/tables/{id}/start', [SessionController::class, 'apiStart']);
$router->post('/api/sessions/{id}/end', [SessionController::class, 'apiEnd']);
$router->post('/api/sessions/{id}/charge', [SessionController::class, 'apiAddCharge']);
$router->post('/api/sessions/{id}/discount', [SessionController::class, 'apiDiscount']);
$router->post('/api/sessions/{id}/pay', [PaymentController::class, 'apiPay']);
$router->get('/api/customers/search', [CustomerController::class, 'apiSearch']);
$router->get('/api/dashboard/stats', [DashboardController::class, 'apiStats']);
$router->get('/api/activity-feed', [DashboardController::class, 'apiActivity']);