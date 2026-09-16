<?php
/** @var int $days */
/** @var array $byHour */
/** @var array $utilization, $topCustomers, $trend */
?>

<div class="space-y-6 fade-in">

    <!-- Page Title -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Analytics</h1>
            <p class="text-sm text-slate-400 mt-1">Peak hours, table utilization and top customers over the last <?= (int) $days ?> days.</p>
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

    <!-- Peak hours chart -->
    <div class="card p-5 sm:p-6">
        <h3 class="text-sm font-semibold text-white mb-2">Revenue by Hour of Day</h3>
        <p class="text-xs text-slate-500 mb-4">Shows the busiest hours — plan staffing around peak time.</p>
        <div class="h-48"><canvas id="hoursChart"></canvas></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Table utilization -->
        <div class="card p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-white mb-4">Table Utilization</h3>
            <div class="space-y-3">
                <?php foreach ($utilization as $t): ?>
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-8 text-slate-400 font-mono shrink-0">#<?= e($t['number']) ?></span>
                        <div class="flex-1">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-slate-300 truncate"><?= e($t['name']) ?></span>
                                <span class="text-slate-500"><?= (int) $t['sessions'] ?> sessions · Rs <?= number_format($t['revenue']) ?></span>
                            </div>
                            <div class="h-2 rounded-full bg-ink-800 overflow-hidden">
                                <?php
                                    $maxUtil = max(1, max(array_column($utilization, 'sessions')));
                                    $pct = min(100, round((int) $t['sessions'] / $maxUtil * 100));
                                ?>
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all duration-500"
                                     style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Top customers -->
        <div class="card p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-white mb-4">Top Customers</h3>
            <?php if (empty($topCustomers)): ?>
                <p class="text-sm text-slate-500">No customer-linked sessions in this period.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($topCustomers as $i => $c): ?>
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-7 h-7 rounded-lg bg-emerald-600/20 flex items-center justify-center text-emerald-400 text-xs font-bold shrink-0"><?= $i + 1 ?></div>
                            <div class="flex-1 min-w-0">
                                <a href="<?= e(url('/customers/' . (int) $c['id'])) ?>" class="text-white hover:text-emerald-400 truncate block">
                                    <?= e($c['name']) ?>
                                </a>
                                <span class="text-xs text-slate-500"><?= (int) $c['visits'] ?> visits</span>
                            </div>
                            <span class="font-semibold text-emerald-400 shrink-0">Rs <?= number_format($c['spent']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Trend sparkline -->
    <div class="card p-5 sm:p-6">
        <h3 class="text-sm font-semibold text-white mb-4">Daily Revenue Trend</h3>
        <div class="h-40"><canvas id="trendChart"></canvas></div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const _aHex = getComputedStyle(document.documentElement).getPropertyValue('--a-500').trim() || '#10b981';
    const _aRgb = (al) => { const n = (_aHex.match(/[0-9a-f]{2}/gi) || ['10','b9','81']).map(x => parseInt(x, 16)); return `rgba(${n[0]},${n[1]},${n[2]},${al})`; };
    const hours = <?= json_encode(array_values($byHour)) ?>;
    const hoursCtx = document.getElementById('hoursChart');
    if (hoursCtx) {
        new Chart(hoursCtx, {
            type: 'line',
            data: {
                labels: Array.from({ length: 24 }, (_, h) => (h % 12 === 0 ? 12 : h % 12) + (h < 12 ? ' AM' : ' PM')),
                datasets: [{
                    label: 'Revenue',
                    data: hours,
                    borderColor: _aHex,
                    backgroundColor: _aRgb(0.15),
                    fill: true,
                    tension: 0.35,
                    pointRadius: 2,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#131824', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, callbacks: { label: c => ' Rs ' + c.parsed.y.toLocaleString() } }
                },
                scales: {
                    x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', font: { size: 9 }, maxTicksLimit: 12 } },
                    y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', font: { size: 10 }, callback: v => 'Rs ' + v } }
                }
            }
        });
    }

    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        const trend = <?= json_encode($trend) ?>;
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: trend.map(t => new Date(t.date + 'T00:00:00').toLocaleDateString('en', { month: 'short', day: 'numeric' })),
                datasets: [{
                    label: 'Revenue',
                    data: trend.map(t => t.revenue),
                    backgroundColor: _aRgb(0.5),
                    borderColor: _aHex,
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#131824', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, callbacks: { label: c => ' Rs ' + c.parsed.y.toLocaleString() } }
                },
                scales: {
                    x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', font: { size: 9 } } },
                    y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', font: { size: 10 }, callback: v => 'Rs ' + v } }
                }
            }
        });
    }
});
</script>