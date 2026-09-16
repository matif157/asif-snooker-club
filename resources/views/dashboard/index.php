<?php
/** @var array $tables, $activeTables, $availableTables, $sessionStats, $todayPayments */
/** @var float $todayRevenue, $todayExpenses, $estimatedProfit, $yesterdayRevenue, $weekRevenue */
/** @var int $yesterdaySessions, $weekSessions, $revenueDelta, $sessionsDelta, $weekRevenueDelta, $weekSessionsDelta */
/** @var array $outstanding, $upcomingBookings, $recentSessions, $activeSessions, $topTablesToday, $longRunning, $arrivingSoon */
/** @var int $unpaidToday, $maintenanceCount, $pendingBookings */
/** @var array $monthOverBudget */
/** @var float $unpaidTodayTotal */
/** @var int $longRunMinutes */
/** @var array $tableCameras */

$tableCount = count($tables);
$occupiedCount = count($activeTables);
$availableCount = count($availableTables);
$reservedCount = count(array_filter($tables, fn($t) => $t['status'] === 'reserved'));
$maintenanceCount = $maintenanceCount ?? count(array_filter($tables, fn($t) => $t['status'] === 'maintenance'));

$deltaBadge = fn(int $pct, string $good = 'emerald', string $bad = 'rose'): string => match (true) {
    $pct > 0   => '<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md bg-emerald-500/10 text-emerald-400 text-[11px] font-semibold">▲ ' . $pct . '%</span>',
    $pct < 0   => '<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md bg-rose-500/10 text-rose-400 text-[11px] font-semibold">▼ ' . abs($pct) . '%</span>',
    default    => '<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md bg-white/5 text-slate-400 text-[11px] font-semibold">± 0%</span>',
};

$quickActions = [];
if (user_can('sessions.manage')) $quickActions[] = ['/tables', 'Start Session', 'play'];
if (user_can('bookings.manage')) $quickActions[] = ['/bookings', 'New Booking', 'calendar'];
if (user_can('payments.manage')) $quickActions[] = ['/payments', 'Record Payment', 'cash'];
if (user_can('expenses.manage')) $quickActions[] = ['/expenses', 'Add Expense', 'trend'];
if (user_can('customers.manage')) $quickActions[] = ['/customers/create', 'New Customer', 'user'];
?>

<div class="space-y-6 fade-in">

    <!-- Page Title + Quick Actions -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Dashboard</h1>
            <p class="text-sm text-slate-400 mt-1">
                Real-time overview of your club ·
                <span class="text-emerald-400 font-semibold"><?= date('l, M j, Y') ?></span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2 text-xs text-slate-500 mr-2">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Synced <span id="last-sync">just now</span>
            </div>
            <?php if ($quickActions): ?>
                <div class="hidden md:flex items-center gap-2">
                    <?php foreach ($quickActions as $i => $qa): ?>
                        <?php if ($i === 0): ?>
                            <a href="<?= $qa[0] ?>" class="btn btn-accent"><?= $qa[1] ?></a>
                        <?php else: ?>
                            <a href="<?= $qa[0] ?>" class="btn btn-ghost"><?= $qa[1] ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 fade-in-stagger">
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
            <p class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Rs <span data-kpi="revenue"><?= number_format($todayRevenue) ?></span></p>
            <div class="mt-3 flex items-center gap-2 text-xs">
                <?= $deltaBadge($revenueDelta) ?>
                <span class="text-slate-500">vs yesterday</span>
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
            <p class="text-2xl sm:text-3xl font-bold text-white tracking-tight"><span data-kpi="sessions"><?= $sessionStats['count'] ?></span></p>
            <div class="mt-3 flex items-center gap-2 text-xs">
                <?= $deltaBadge($sessionsDelta) ?>
                <span class="text-slate-500">vs yesterday</span>
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
            <p class="text-2xl sm:text-3xl font-bold text-white tracking-tight"><span data-kpi="active_tables"><?= $occupiedCount ?></span> <span class="text-lg text-slate-500 font-normal">/ <?= $tableCount ?></span></p>
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-400">
                <span class="inline-flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <?= $availableCount ?> available
                </span>
                <?php if ($reservedCount > 0): ?>
                    <span class="inline-flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-violet-400"></span>
                        <?= $reservedCount ?> reserved
                    </span>
                <?php endif; ?>
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
                Rs <span data-kpi="profit"><?= number_format($estimatedProfit) ?></span>
            </p>
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-400">
                Revenue − Expenses
            </div>
        </div>
    </div>

    <!-- Week Strip -->
    <div class="card px-5 py-4 flex flex-wrap items-center gap-x-8 gap-y-3">
        <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <div>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">This Week</p>
                <p class="text-sm font-semibold text-white">Rs <?= number_format($weekRevenue) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-xs">
            <?= $deltaBadge($weekRevenueDelta) ?>
            <span class="text-slate-500">vs last week</span>
        </div>
        <div class="w-px h-8 bg-white/10 hidden sm:block"></div>
        <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Sessions</p>
                <p class="text-sm font-semibold text-white"><?= $weekSessions ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-xs">
            <?= $deltaBadge($weekSessionsDelta) ?>
            <span class="text-slate-500">vs last week</span>
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
                    $cam = $tableCameras[(int) $table['id']] ?? null;
                ?>
                <a href="/tables" class="table-tile <?= $class ?> block">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold text-white">#<?= e($table['number']) ?></span>
                        <span class="flex items-center gap-1.5">
                            <?php if ($cam): ?>
                                <a href="/cctv" title="<?= e($cam['name']) ?> — live camera"
                                   class="p-1 rounded-md bg-sky-500/10 text-sky-400 hover:bg-sky-500/20 transition" onclick="event.stopPropagation()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </a>
                            <?php endif; ?>
                            <span class="status-dot flex-shrink-0"></span>
                        </span>
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

    <!-- Charts + Alerts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Revenue Chart -->
        <div class="lg:col-span-2 card p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-sm font-semibold text-white uppercase tracking-wider">Revenue Overview</h3>
                <select id="chartRange" class="bg-ink-800 border border-white/10 rounded-lg text-xs text-slate-300 px-3 py-1.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                    <option value="7">Last 7 Days</option>
                    <option value="30" selected>Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                </select>
            </div>
            <canvas id="revenueChart" height="220"></canvas>
        </div>

        <!-- Right column: Alerts + Upcoming Bookings -->
        <div class="space-y-5">
            <!-- Alerts -->
            <div class="card p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider">Needs Attention</h3>
                    <span class="text-xs text-slate-500" id="alert-count"></span>
                </div>
                <?php $alertCount = ($unpaidToday > 0 ? 1 : 0) + count($arrivingSoon) + count($longRunning) + ($maintenanceCount > 0 ? 1 : 0) + ($pendingBookings > 0 ? 1 : 0) + (empty($monthOverBudget) ? 0 : 1); ?>
                <script>document.getElementById('alert-count').textContent = '<?= $alertCount ?> alert<?= $alertCount === 1 ? '' : 's' ?>';</script>
                <?php if ($alertCount === 0): ?>
                    <div class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <p class="text-xs text-slate-500">All clear — nothing needs attention.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php if ($pendingBookings > 0): ?>
                    <a href="/bookings" class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg bg-sky-500/10 border border-sky-500/20 hover:bg-sky-500/15 transition">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                            <div>
                                <p class="text-xs font-medium text-white"><?= $pendingBookings ?> booking request<?= $pendingBookings === 1 ? '' : 's' ?> needs approval</p>
                                <p class="text-[11px] text-slate-500">E.g. from the member portal — confirm to lock the table</p>
                            </div>
                        </div>
                        <span class="text-xs text-sky-400 font-semibold">Approve →</span>
                    </a>
                <?php endif; ?>

                <?php if ($unpaidToday > 0): ?>
                            <a href="/payments" class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg bg-rose-500/10 border border-rose-500/20 hover:bg-rose-500/15 transition">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                                    <div>
                                        <p class="text-xs font-medium text-white"><?= $unpaidToday ?> completed session<?= $unpaidToday === 1 ? '' : 's' ?> unpaid</p>
                                        <p class="text-[11px] text-slate-500">Rs <?= number_format($unpaidTodayTotal) ?> outstanding</p>
                                    </div>
                                </div>
                                <span class="text-xs text-rose-400 font-semibold">Collect →</span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($monthOverBudget)): ?>
                            <a href="/expenses" class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg bg-rose-500/10 border border-rose-500/20 hover:bg-rose-500/15 transition">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                                    <div>
                                        <p class="text-xs font-medium text-white">Over budget this month</p>
                                        <p class="text-[11px] text-slate-500">
                                            <?php $obFirst = array_key_first($monthOverBudget); $obRow = $monthOverBudget[$obFirst]; ?>
                                            <?= e(ucfirst(\App\Models\Expense::CATEGORIES[$obFirst] ?? $obFirst)) ?>: Rs <?= number_format($obRow['spent']) ?> / <?= number_format($obRow['budget']) ?><?= count($monthOverBudget) > 1 ? ' +' . (count($monthOverBudget) - 1) . ' more' : '' ?>
                                        </p>
                                    </div>
                                </div>
                                <span class="text-xs text-rose-400 font-semibold">Review →</span>
                            </a>
                        <?php endif; ?>

                        <?php foreach ($arrivingSoon as $b): ?>
                            <div class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg bg-amber-500/10 border border-amber-500/20">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                    <div>
                                        <p class="text-xs font-medium text-white"><?= e($b['customer_name'] ?? 'Walk-in') ?></p>
                                        <p class="text-[11px] text-slate-500">Table #<?= e($b['table_number']) ?> · <?= date('g:i A', strtotime($b['start_time'])) ?></p>
                                    </div>
                                </div>
                                <span class="badge badge-amber shrink-0">Arriving</span>
                            </div>
                        <?php endforeach; ?>

                        <?php foreach ($longRunning as $s): ?>
                            <a href="/tables" class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg bg-amber-500/10 border border-amber-500/20 hover:bg-amber-500/15 transition">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                    <div>
                                        <p class="text-xs font-medium text-white">Table #<?= e($s['table_number']) ?></p>
                                        <p class="text-[11px] text-slate-500">Running <?= format_duration(time() - strtotime($s['start_time']) - (int) ($s['paused_total_sec'] ?? 0)) ?></p>
                                    </div>
                                </div>
                                <span class="badge badge-amber shrink-0">Long run</span>
                            </a>
                        <?php endforeach; ?>

                        <?php if ($maintenanceCount > 0): ?>
                            <div class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg bg-slate-500/10 border border-white/10">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                    <p class="text-xs font-medium text-white"><?= $maintenanceCount ?> table<?= $maintenanceCount === 1 ? '' : 's' ?> in maintenance</p>
                                </div>
                                <a href="/tables" class="text-xs text-slate-400 hover:text-white transition">View →</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

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
        </div>
    </div>

    <!-- Sessions + Side widgets Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Recent Sessions -->
        <?php if (!empty($recentSessions)): ?>
        <div class="lg:col-span-2 card p-6">
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

        <!-- Right column: Payments by method + Top tables + Outstanding -->
        <div class="space-y-5">
            <!-- Payments by Method -->
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Payments Today</h3>
                <?php if (empty($todayPayments)): ?>
                    <p class="text-xs text-slate-500 py-6 text-center">No payments recorded yet today</p>
                <?php else: ?>
                    <div class="flex items-center gap-5">
                        <div class="relative w-28 h-28">
                            <canvas id="methodsChart"></canvas>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="space-y-1.5">
                                <?php foreach ($todayPayments as $m): ?>
                                    <div class="flex items-center justify-between gap-2 text-xs">
                                        <span class="inline-flex items-center gap-1.5 text-slate-400 truncate">
                                            <span class="w-2 h-2 rounded-full method-dot shrink-0"></span>
                                            <?= e(ucfirst($m['method'])) ?>
                                        </span>
                                        <span class="font-semibold text-white">Rs <?= number_format((float) $m['total']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3 pt-3 border-t border-white/10 flex items-center justify-between text-xs">
                                <span class="text-slate-500">Total</span>
                                <span class="font-bold text-emerald-400">Rs <?= number_format($todayRevenue) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Top Tables Today -->
            <?php if (!empty($topTablesToday)): ?>
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Top Tables Today</h3>
                <div class="space-y-2.5">
                    <?php foreach ($topTablesToday as $i => $t): ?>
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-white/[0.02] border border-white/[0.04]">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-6 h-6 rounded-md <?= $i === 0 ? 'bg-amber-500/15 text-amber-400' : 'bg-white/5 text-slate-500' ?> flex items-center justify-center text-[11px] font-bold shrink-0"><?= $i + 1 ?></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-white truncate">#<?= e($t['number']) ?> <?= e($t['name']) ?></p>
                                    <p class="text-[11px] text-slate-500"><?= (int) $t['session_count'] ?> session<?= (int) $t['session_count'] === 1 ? '' : 's' ?> · <?= number_format((float) $t['hours'], 1) ?> hr</p>
                                </div>
                            </div>
                            <span class="text-sm font-bold text-emerald-400 shrink-0">Rs <?= number_format((float) $t['revenue']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

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

</div>

<!-- Dashboard Scripts -->
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const _aHex = getComputedStyle(document.documentElement).getPropertyValue('--a-500').trim() || '#10b981';
const _aRgb = (al) => { const n = (_aHex.match(/[0-9a-f]{2}/gi) || ['10','b9','81']).map(x => parseInt(x, 16)); return `rgba(${n[0]},${n[1]},${n[2]},${al})`; };

    const palette = ['#10b981', '#38bdf8', '#f59e0b', '#a78bfa', '#f43f5e', '#94a3b8'];

    const methodsChart = document.getElementById('methodsChart');
    if (methodsChart) {
        const labels = <?= json_encode(array_column($todayPayments, 'method')) ?>.map(m => m.charAt(0).toUpperCase() + m.slice(1));
        const values = <?= json_encode(array_column($todayPayments, 'total')) ?>;
        const colors = labels.map((_, i) => i === 0 ? _aHex : palette[(i - 1) % palette.length]);
        document.querySelectorAll('.method-dot').forEach((el, i) => {
            el.style.background = colors[i % colors.length];
        });
        new Chart(methodsChart, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{ data: values, backgroundColor: colors, borderColor: '#131824', borderWidth: 3 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#131824',
                        titleColor: '#f8fafc',
                        bodyColor: '#cbd5e1',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: { label: c => ' ' + c.label + ': Rs ' + c.parsed.toLocaleString() }
                    }
                }
            }
        });
    }

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
                    backgroundColor: _aRgb(0.5),
                    borderColor: _aHex,
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

    async function loadChart(days) {
        try {
            const res = await apiGet('/api/dashboard/revenue-trend?days=' + days);
            if (!res.success) return;
            const labels = res.data.labels.map(d => {
                const parts = d.split('-');
                return new Date(parts[0], parts[1] - 1, parts[2]).toLocaleDateString('en', { month: 'short', day: 'numeric' });
            });
            chart.data.labels = labels;
            chart.data.datasets[0].data = res.data.revenue;
            chart.data.datasets[1].data = res.data.expenses;
            chart.update();
        } catch (e) {}
    }

    loadChart(30);

    document.getElementById('chartRange')?.addEventListener('change', (e) => {
        loadChart(parseInt(e.target.value));
    });
});
</script>