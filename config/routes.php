<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\CustomerController;
use App\Controllers\DashboardController;
use App\Controllers\ExpenseController;
use App\Controllers\PaymentController;
use App\Controllers\ReportsController;
use App\Controllers\SessionController;
use App\Controllers\SettingsController;
use App\Controllers\TableController;

/** @var App\Core\Router $router */

// ── Auth routes ────────────────────────────────────────────────────────
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// ── Authenticated web routes ───────────────────────────────────────────
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
$router->get('/customers/export', [CustomerController::class, 'export']);
$router->post('/customers/import', [CustomerController::class, 'import']);
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
$router->get('/bookings/calendar', [BookingController::class, 'calendar']);
$router->post('/bookings', [BookingController::class, 'store']);
$router->post('/bookings/{id}/status', [BookingController::class, 'updateStatus']);

// Payments
$router->get('/payments', [PaymentController::class, 'index']);
$router->post('/payments', [PaymentController::class, 'store']);
$router->get('/payments/{id}/receipt', [PaymentController::class, 'receipt']);

// Expenses
$router->get('/expenses', [ExpenseController::class, 'index']);
$router->post('/expenses', [ExpenseController::class, 'store']);
$router->post('/expenses/{id}/status', [ExpenseController::class, 'setStatus']);

// Reports
$router->get('/reports/daily', [ReportsController::class, 'daily']);
$router->get('/reports/pnl', [ReportsController::class, 'pnl']);
$router->get('/reports/analytics', [ReportsController::class, 'analytics']);
$router->get('/reports/audit', [ReportsController::class, 'audit']);

// Settings & Staff
$router->get('/settings', [SettingsController::class, 'index']);
$router->post('/settings', [SettingsController::class, 'update']);
$router->post('/settings/backup', [SettingsController::class, 'backup']);
$router->get('/settings/backups/{name}', [SettingsController::class, 'downloadBackup']);
$router->post('/settings/users/create', [SettingsController::class, 'createUser']);
$router->post('/settings/users/{id}/update', [SettingsController::class, 'updateUser']);

// ── API / AJAX routes ──────────────────────────────────────────────────
$router->get('/api/tables', [TableController::class, 'apiList']);
$router->post('/api/tables/{id}/start', [SessionController::class, 'apiStart']);
$router->post('/api/sessions/{id}/end', [SessionController::class, 'apiEnd']);
$router->post('/api/sessions/{id}/charge', [SessionController::class, 'apiAddCharge']);
$router->post('/api/sessions/{id}/discount', [SessionController::class, 'apiDiscount']);
$router->post('/api/sessions/{id}/pay', [PaymentController::class, 'apiPay']);
$router->get('/api/customers/search', [CustomerController::class, 'apiSearch']);
$router->get('/api/dashboard/stats', [DashboardController::class, 'apiStats']);
$router->get('/api/dashboard/revenue-trend', [DashboardController::class, 'apiRevenueTrend']);
$router->get('/api/activity-feed', [DashboardController::class, 'apiActivity']);
$router->get('/api/sse/tables', [SessionController::class, 'sseTables']);
$router->get('/api/sse/activity', [SessionController::class, 'sseActivity']);