<?php
/** @var int $days */
/** @var float $revenue, $outstanding */
/** @var int $sessionCount, $avgSession */
/** @var array $byHour, $byHourSessions, $utilization, $topCustomers, $categoryBreakdown, $bookingsByStatus, $trend */
/** @var string $currency */
$cur = $currency === 'Rs' ? 'Rs ' : ($currency . ' ');
$kpiIcon = fn(string $svg) => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">' . $svg . '</svg>';
?>

<div class="space-y-6 fade-in">

    <!-- Page Title -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Analytics</h1>
            <p class="text-sm text-slate-400 mt-1">Revenue, peak hours, utilization and customer insights over the last <?= (int) $days ?> days.</p>
        </div>
        <form method="GET" action="<?= e(url('/reports/analytics')) ?>" class="flex items-center gap-2">
            <select name="days" class="input !w-auto">
                <?php foreach ([7, 14, 30, 60, 90] as $d): ?>
                    <option value="<?= $d ?>" <?= $days === $d ? 'selected' : '' ?>><?= $d ?> days</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-secondary">View</button>
        </form>
    </div>

    <!-- KPI header -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="card p-4 sm:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/15 text-emerald-400 flex items-center justify-center">
                    <?= $kpiIcon('<rect x="2.5" y="7" width="19" height="12" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 11h19M16 13h4"/>') ?>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Revenue (<?= $days ?>d)</p>
                    <p class="text-lg font-bold text-white"><?= $cur ?><?= number_format($revenue) ?></p>
                </div>
            </div>
        </div>
        <div class="card p-4 sm:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-500/15 text-sky-400 flex items-center justify-center">
                    <?= $kpiIcon('<path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>') ?>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Sessions completed</p>
                    <p class="text-lg font-bold text-white"><?= number_format($sessionCount) ?></p>
                </div>
            </div>
        </div>
        <div class="card p-4 sm:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gold-500/15 text-gold-400 flex items-center justify-center">
                    <?= $kpiIcon('<path stroke-linecap="round" stroke-linejoin="round" d="M13 7l-2 13M8.5 10h9M10 6.5h4.5M4 16.5l4.5-8M20 16.5l-4.5-8"/>') ?>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Avg session value</p>
                    <p class="text-lg font-bold text-white"><?= $cur ?><?= number_format((int) $avgSession) ?></p>
                </div>
            </div>
        </div>
        <div class="card p-4 sm:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500/15 text-rose-400 flex items-center justify-center">
                    <?= $kpiIcon('<path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0M9 17a2 2 0 100-4M9 17h6M17 17a2 2 0 11-4 0M17 17a2 2 0 100-4m-8-2V7a1 1 0 011-1h6a1 1 0 011 1v4"/>') ?>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Outstanding balances</p>
                    <p class="text-lg font-bold text-white"><?= $cur ?><?= number_format($outstanding) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue + sessions by day -->
    <div class="card p-5 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-semibold text-white">Daily Revenue vs Expenses</h3>
                <p class="text-xs text-slate-500">Gross payments against approved expenses each day.</p>
            </div>
            <div class="flex items-center gap-4 text-xs text-slate-400">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></span> Revenue</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-gold-400"></span> Expenses</span>
            </div>
        </div>
        <div class="h-48"><canvas id="dailyChart"></canvas></div>
    </div>

    <!-- Peak hours -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-white mb-2">Revenue by Hour of Day</h3>
            <p class="text-xs text-slate-500 mb-4">Shows the busiest hours — plan staffing around peak time.</p>
            <div class="h-48"><canvas id="hoursChart"></canvas></div>
        </div>

        <div class="card p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-white mb-2">Sessions by Hour of Day</h3>
            <p class="text-xs text-slate-500 mb-4">How many sessions run at each hour of the day.</p>
            <div class="h-48"><canvas id="sessionHoursChart"></canvas></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Table utilization -->
        <div class="card p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-white mb-4">Table Utilization</h3>
            <div class="space-y-3">
                <?php $maxSessions = max(1, max(array_column($utilization, 'sessions'))); ?>
                <?php foreach ($utilization as $t): ?>
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-8 text-slate-400 font-mono shrink-0">#<?= e($t['number']) ?></span>
                        <div class="flex-1">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-slate-300 truncate"><?= e($t['name']) ?></span>
                                <span class="text-slate-500"><?= (int) $t['sessions'] ?> sessions · <?= (float) $t['hours'] ?> h · <?= $cur ?><?= number_format($t['revenue']) ?></span>
                            </div>
                            <div class="h-2 rounded-full bg-ink-800 overflow-hidden">
                                <?php $pct = min(100, round((int) $t['sessions'] / $maxSessions * 100)); ?>
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all duration-500"
                                     style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Category breakdown -->
        <div class="card p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-white mb-4">Customers by Category</h3>
            <?php if (empty(array_filter($categoryBreakdown, fn($r) => ((int) $r['customers']) > 0))): ?>
                <p class="text-sm text-slate-500">No customers yet.</p>
            <?php else: ?>
                <?php $maxCat = max(1, max(array_column($categoryBreakdown, 'customers'))); ?>
                <div class="space-y-3">
                    <?php foreach ($categoryBreakdown as $c): ?>
                        <div class="flex items-center gap-3 text-sm">
                            <span class="w-20 text-xs uppercase tracking-wide text-slate-400 capitalize shrink-0"><?= e($c['category']) ?></span>
                            <div class="flex-1">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="text-slate-300"><?= (int) $c['customers'] ?> customers</span>
                                    <span class="text-slate-500"><?= $cur ?><?= number_format($c['spent']) ?></span>
                                </div>
                                <div class="h-2 rounded-full bg-ink-800 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-gold-400 to-gold-500 transition-all duration-500"
                                         style="width: <?= min(100, round((int) $c['customers'] / $maxCat * 100)) ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top customers -->
    <div class="card p-5 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-semibold text-white">Top Customers</h3>
                <p class="text-xs text-slate-500">Regulars generating the most revenue in this period.</p>
            </div>
            <a href="<?= e(url('/customers')) ?>" class="text-xs text-emerald-400 hover:text-emerald-300 font-medium">All customers</a>
        </div>
        <?php if (empty($topCustomers)): ?>
            <p class="text-sm text-slate-500">No customer-linked sessions in this period.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach ($topCustomers as $i => $c): ?>
                    <a href="<?= e(url('/customers/' . (int) $c['id'])) ?>"
                       class="flex items-center gap-3 text-sm rounded-xl border border-white/[0.05] bg-white/[0.02] p-3 hover:border-emerald-500/40 transition">
                        <div class="w-7 h-7 rounded-lg bg-emerald-600/20 flex items-center justify-center text-emerald-400 text-xs font-bold shrink-0"><?= $i + 1 ?></div>
                        <div class="flex-1 min-w-0">
                            <span class="text-white truncate block"><?= e($c['name']) ?></span>
                            <span class="text-xs text-slate-500"><?= (int) $c['visits'] ?> visits · <?= (float) $c['hours'] ?> h</span>
                        </div>
                        <span class="font-semibold text-emerald-400 shrink-0"><?= $cur ?><?= number_format($c['spent']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Booking statuses -->
    <div class="card p-5 sm:p-6">
        <h3 class="text-sm font-semibold text-white mb-4">Booking Status Mix</h3>
        <?php if (empty($bookingsByStatus)): ?>
            <p class="text-sm text-slate-500">No bookings in this period.</p>
        <?php else: ?>
            <?php $totalBk = max(1, array_sum(array_column($bookingsByStatus, 'n'))); ?>
            <div class="flex flex-wrap gap-2 mb-4">
                <?php foreach ($bookingsByStatus as $b): ?>
                    <span class="rounded-lg bg-white/[0.04] border border-white/10 px-3 py-1.5 text-xs text-slate-300">
                        <span class="capitalize"><?= e(str_replace('_', ' ', $b['status'])) ?></span>
                        <span class="text-slate-400 ml-1"><?= (int) $b['n'] ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
            <div class="flex h-3 rounded-full overflow-hidden bg-ink-800">
                <?php $colors = ['requested' => '#f59e0b', 'confirmed' => '#38bdf8', 'arrived' => '#10b981', 'active' => '#22d3ee', 'completed' => '#34d399', 'cancelled' => '#64748b', 'no_show' => '#f43f5e', 'expired' => '#94a3b8', 'paid' => '#34d399']; ?>
                <?php foreach ($bookingsByStatus as $b): ?>
                    <div title="<?= e($b['status']) ?>" style="width: <?= round((int) $b['n'] / $totalBk * 100, 1) ?>%; background: <?= $colors[$b['status']] ?? '#64748b' ?>"></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const _aHex = getComputedStyle(document.documentElement).getPropertyValue('--a-500').trim() || '#10b981';
    const _aRgb = (al) => { const n = (_aHex.match(/[0-9a-f]{2}/gi) || ['10','b9','81']).map(x => parseInt(x, 16)); return `rgba(${n[0]},${n[1]},${n[2]},${al})`; };
    const _scales = {
        x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', font: { size: 9 }, maxTicksLimit: 12 } },
        y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', font: { size: 10 } } }
    };

    // Daily revenue vs expenses
    const dailyCtx = document.getElementById('dailyChart');
    if (dailyCtx) {
        const trend = <?= json_encode($trend) ?>;
        new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: trend.map(t => new Date(t.date + 'T00:00:00').toLocaleDateString('en', { month: 'short', day: 'numeric' })),
                datasets: [
                    { label: 'Revenue', data: trend.map(t => t.revenue), backgroundColor: _aRgb(0.55), borderColor: _aHex, borderWidth: 1, borderRadius: 3 },
                    { label: 'Expenses', data: trend.map(t => t.expenses), backgroundColor: 'rgba(251,191,36,0.45)', borderColor: '#fbbf24', borderWidth: 1, borderRadius: 3 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { backgroundColor: '#131824', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, callbacks: { label: c => (c.dataset.label + ': ' + c.parsed.y.toLocaleString()) } } },
                scales: { x: _scales.x, y: { ..._scales.y, ticks: { ..._scales.y.ticks, callback: v => 'Rs ' + v } } }
            }
        });
    }

    const _hourOptions = (yCb) => ({
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { backgroundColor: '#131824', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1 } },
        scales: { x: _scales.x, y: { ..._scales.y, ticks: { ..._scales.y.ticks, callback: yCb } } }
    });

    const hourLabels = Array.from({ length: 24 }, (_, h) => (h % 12 === 0 ? 12 : h % 12) + (h < 12 ? ' AM' : ' PM'));

    // Revenue by hour
    const hoursCtx = document.getElementById('hoursChart');
    if (hoursCtx) {
        new Chart(hoursCtx, {
            type: 'line',
            data: {
                labels: hourLabels,
                datasets: [{ label: 'Revenue', data: <?= json_encode(array_values($byHour)) ?>, borderColor: _aHex, backgroundColor: _aRgb(0.15), fill: true, tension: 0.35, pointRadius: 2, pointHoverRadius: 5 }]
            },
            options: _hourOptions(v => 'Rs ' + v)
        });
    }

    // Sessions by hour
    const shCtx = document.getElementById('sessionHoursChart');
    if (shCtx) {
        new Chart(shCtx, {
            type: 'bar',
            data: {
                labels: hourLabels,
                datasets: [{ label: 'Sessions', data: <?= json_encode(array_values($byHourSessions)) ?>, backgroundColor: _aRgb(0.4), borderColor: _aHex, borderWidth: 1, borderRadius: 3 }]
            },
            options: _hourOptions(v => v)
        });
    }
});
</script>