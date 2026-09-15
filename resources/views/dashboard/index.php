<?php
/** @var array $tables, $activeTables, $availableTables, $sessionStats, $todayPayments */
/** @var float $todayRevenue, $todayExpenses, $estimatedProfit */
/** @var array $outstanding, $upcomingBookings, $recentSessions, $activeSessions */

$tableCount = count($tables);
$occupiedCount = count($activeTables);
$availableCount = count($availableTables);
$reservedCount = count(array_filter($tables, fn($t) => $t['status'] === 'reserved'));
$maintenanceCount = count(array_filter($tables, fn($t) => $t['status'] === 'maintenance'));
?>

<div class="space-y-6 fade-in">

    <!-- Page Title -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Dashboard</h1>
            <p class="text-sm text-slate-400 mt-1">
                Real-time overview of your club ·
                <span class="text-emerald-400 font-semibold"><?= date('l, M j, Y') ?></span>
            </p>
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            Synced <span id="last-sync">just now</span>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Revenue -->
        <div class="stat-card">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/15 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="text-right flex-1">
                    <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Revenue</p>
                    <p class="text-xs text-slate-500 mt-0.5">Today</p>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Rs <?= number_format($todayRevenue) ?></p>
            <div class="mt-3 flex items-center gap-2 text-xs">
                <span class="text-slate-400">Expenses: <span class="text-rose-400">Rs <?= number_format($todayExpenses) ?></span></span>
            </div>
        </div>

        <!-- Sessions -->
        <div class="stat-card">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-sky-500/15 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="text-right flex-1">
                    <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Sessions</p>
                    <p class="text-xs text-slate-500 mt-0.5">Today</p>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-white tracking-tight"><?= $sessionStats['count'] ?></p>
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-400">
                Collected: <span class="text-emerald-400">Rs <?= number_format($sessionStats['collected']) ?></span>
            </div>
        </div>

        <!-- Active Tables -->
        <div class="stat-card">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/15 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </div>
                <div class="text-right flex-1">
                    <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Active</p>
                    <p class="text-xs text-slate-500 mt-0.5">Tables</p>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-white tracking-tight"><?= $occupiedCount ?> <span class="text-lg text-slate-500 font-normal">/ <?= $tableCount ?></span></p>
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-400">
                <span class="inline-flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <?= $availableCount ?> available
                </span>
            </div>
        </div>

        <!-- Profit -->
        <div class="stat-card">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-violet-500/15 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <div class="text-right flex-1">
                    <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Est. Profit</p>
                    <p class="text-xs text-slate-500 mt-0.5">Today</p>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-bold <?= $estimatedProfit >= 0 ? 'text-emerald-400' : 'text-rose-400' ?> tracking-tight">
                Rs <?= number_format($estimatedProfit) ?>
            </p>
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-400">
                Revenue − Expenses
            </div>
        </div>
    </div>

    <!-- Table Command Center -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Table Command Center</h2>
            <a href="/tables" class="text-xs text-emerald-400 hover:text-emerald-300 transition font-medium">View all →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3" id="tables-grid">
            <?php foreach ($tables as $table): ?>
                <?php
                    $status = $table['status'];
                    $class = match($status) {
                        'available'   => 'table-tile--available status-available',
                        'occupied'    => 'table-tile--occupied status-occupied',
                        'reserved'    => 'table-tile--reserved status-reserved',
                        'maintenance' => 'table-tile--maintenance status-maintenance',
                        'blocked'     => 'table-tile--blocked',
                        default       => 'table-tile--available',
                    };
                    $label = match($status) {
                        'occupied'    => 'Occupied',
                        'reserved'    => 'Reserved',
                        'maintenance' => 'Maintenance',
                        'blocked'     => 'Blocked',
                        'offline'     => 'Offline',
                        default       => 'Available',
                    };
                    $session = $table['current_session'] ?? null;
                    $elapsed = $table['elapsed_seconds'] ?? 0;
                ?>
                <a href="/tables/<?= (int) $table['id'] ?>" class="table-tile <?= $class ?> block">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold text-white">#<?= e($table['number']) ?></span>
                        <span class="status-dot flex-shrink-0"></span>
                    </div>
                    <p class="text-xs text-slate-400 mb-2"><?= e($table['name']) ?></p>

                    <?php if ($status === 'occupied' && $elapsed > 0): ?>
                        <div class="flex items-center gap-1.5 mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-xs font-mono text-emerald-400 font-semibold timer-display"
                                  data-start="<?= e($session['start_time'] ?? '') ?>"
                                  data-paused="<?= e((string)($session['paused_total_sec'] ?? 0)) ?>"
                                  data-status="<?= e($session['status'] ?? '') ?>">
                                <?= format_duration($elapsed) ?>
                            </span>
                        </div>
                        <?php if (!empty($session['customer_name'])): ?>
                            <p class="text-[11px] text-slate-400 truncate"> <?= e($session['customer_name']) ?></p>
                        <?php endif; ?>
                    <?php elseif ($status === 'reserved'): ?>
                        <?php
                            $booking = \App\Core\Database::fetchOne(
                                "SELECT * FROM bookings WHERE table_id = ? AND booking_date = CURDATE() AND status IN ('requested','confirmed','arrived') ORDER BY start_time LIMIT 1",
                                [(int) $table['id']]
                            );
                        ?>
                        <?php if ($booking): ?>
                            <p class="text-xs text-violet-400 font-medium">
                                <?= date('g:i A', strtotime($booking['start_time'])) ?>
                                — <?= date('g:i A', strtotime($booking['end_time'])) ?>
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-xs text-slate-500">Rs <?= number_format((float) $table['hourly_rate']) ?>/hr</p>
                    <?php endif; ?>

                    <div class="mt-3">
                        <span class="badge badge-<?= match($status) {
                            'available' => 'emerald',
                            'occupied'  => 'emerald',
                            'reserved'  => 'violet',
                            default     => 'rose',
                        } ?>"><?= $label ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Revenue Chart -->
        <div class="lg:col-span-2 card p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-sm font-semibold text-white uppercase tracking-wider">Revenue Overview</h3>
                <select id="chartRange" class="bg-ink-800 border border-white/10 rounded-lg text-xs text-slate-300 px-3 py-1.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                    <option value="7">Last 7 Days</option>
                    <option value="30" selected>Last 30 Days</option>
                </select>
            </div>
            <canvas id="revenueChart" height="220"></canvas>
        </div>

        <!-- Bookings + Outstanding -->
        <div class="space-y-5">
            <!-- Upcoming Bookings -->
            <div class="card p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider">Upcoming Bookings</h3>
                    <a href="/bookings" class="text-xs text-emerald-400 hover:text-emerald-300 transition">View all →</a>
                </div>
                <?php if (empty($upcomingBookings)): ?>
                    <p class="text-xs text-slate-500 py-6 text-center">No upcoming bookings</p>
                <?php else: ?>
                    <div class="space-y-2.5">
                        <?php foreach (array_slice($upcomingBookings, 0, 5) as $booking): ?>
                            <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-white/[0.02] border border-white/[0.04]">
                                <div>
                                    <p class="text-xs font-medium text-white">
                                        <?= e($booking['customer_linked_name'] ?? $booking['customer_name'] ?? 'Walk-in') ?>
                                    </p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Table #<?= e($booking['table_number']) ?> ·
                                        <?= date('g:i A', strtotime($booking['start_time'])) ?> — <?= date('g:i A', strtotime($booking['end_time'])) ?>
                                    </p>
                                </div>
                                <span class="badge badge-<?= \App\Models\Booking::STATUS_COLORS[$booking['status']] ?? 'slate' ?>">
                                    <?= \App\Models\Booking::STATUS_LABELS[$booking['status']] ?? $booking['status'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Outstanding Balances -->
            <?php if (!empty($outstanding)): ?>
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Outstanding Balances</h3>
                <div class="space-y-2.5">
                    <?php foreach ($outstanding as $c): ?>
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-white/[0.02] border border-white/[0.04]">
                            <div>
                                <p class="text-xs font-medium text-white"><?= e($c['name']) ?></p>
                                <?php if ($c['phone']): ?>
                                    <a href="<?= e(\App\Models\Customer::telLink($c['phone'])) ?>" class="text-[11px] text-slate-500 hover:text-emerald-400 transition">
                                        <?= e($c['phone']) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <span class="text-sm font-bold text-rose-400">Rs <?= number_format((float) $c['outstanding_balance']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Sessions -->
    <?php if (!empty($recentSessions)): ?>
    <div class="card p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-sm font-semibold text-white uppercase tracking-wider">Recent Sessions</h3>
            <a href="/sessions" class="text-xs text-emerald-400 hover:text-emerald-300 transition font-medium">View all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Customer</th>
                        <th>Started</th>
                        <th>Status</th>
                        <th class="text-right">Amount</th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($recentSessions, 0, 8) as $sess): ?>
                        <tr>
                            <td class="font-medium text-white">#<?= e($sess['table_number']) ?></td>
                            <td><?= e($sess['customer_name'] ?? 'Walk-in') ?></td>
                            <td class="text-slate-400"><?= date('M j, g:i A', strtotime($sess['start_time'])) ?></td>
                            <td>
                                <span class="badge badge-<?= match($sess['status']) {
                                    'active' => 'emerald',
                                    'completed' => 'slate',
                                    'paused' => 'amber',
                                    default => 'rose',
                                } ?>"><?= ucfirst($sess['status']) ?></span>
                            </td>
                            <td class="text-right font-medium text-white">Rs <?= number_format((float) $sess['amount']) ?></td>
                            <td>
                                <span class="badge badge-<?= match($sess['payment_status']) {
                                    'paid' => 'emerald',
                                    'partial' => 'amber',
                                    'unpaid' => 'rose',
                                    default => 'slate',
                                } ?>"><?= ucfirst($sess['payment_status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Revenue Chart Script -->
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;

    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Revenue',
                    data: [],
                    backgroundColor: 'rgba(16,185,129,0.5)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 6,
                    barPercentage: 0.6
                },
                {
                    label: 'Expenses',
                    data: [],
                    backgroundColor: 'rgba(239,68,68,0.25)',
                    borderColor: '#ef4444',
                    borderWidth: 1,
                    borderRadius: 6,
                    barPercentage: 0.6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, labels: { color: '#94a3b8', boxWidth: 12, font: { size: 11 } } },
                tooltip: {
                    backgroundColor: '#131824',
                    titleColor: '#f8fafc',
                    bodyColor: '#cbd5e1',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 10,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.dataset.label + ': Rs ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', font: { size: 11 } } },
                y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', font: { size: 11 }, callback: v => 'Rs ' + v.toLocaleString() } }
            }
        }
    });

    // Load chart data
    async function loadChart(days) {
        // Mock data for now — will be real API endpoint later
        const today = new Date();
        const labels = [];
        const revenue = [];
        const expenses = [];

        for (let i = days - 1; i >= 0; i--) {
            const d = new Date(today);
            d.setDate(d.getDate() - i);
            labels.push(d.toLocaleDateString('en', { month: 'short', day: 'numeric' }));
            revenue.push(Math.floor(Math.random() * 5000 + 2000));
            expenses.push(Math.floor(Math.random() * 1500 + 300));
        }

        chart.data.labels = labels;
        chart.data.datasets[0].data = revenue;
        chart.data.datasets[1].data = expenses;
        chart.update();
    }

    loadChart(30);

    document.getElementById('chartRange')?.addEventListener('change', (e) => {
        loadChart(parseInt(e.target.value));
    });
});
</script>