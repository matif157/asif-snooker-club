<?php
/** @var array $tables — each with 'current_session', 'elapsed_seconds' */
/** @var array $activeSessions */
/** @var bool $startSession */

$occupiedCount = count(array_filter($tables, fn($t) => $t['status'] === 'occupied'));
$availableCount = count(array_filter($tables, fn($t) => $t['status'] === 'available'));
$reservedCount = count(array_filter($tables, fn($t) => $t['status'] === 'reserved'));
$maintenanceCount = count(array_filter($tables, fn($t) => $t['status'] === 'maintenance'));
?>

<div class="space-y-6 fade-in" id="tables-root" x-data="tableCommandCenter(<?= htmlspecialchars((string) json_encode($startPrefill), ENT_QUOTES) ?>)">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Table Command Center</h1>
            <p class="text-sm text-slate-400 mt-1">
                Live overview of all tables &middot;
                <span class="text-emerald-400 font-semibold"><?= count($tables) ?> tables</span>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Live
            </div>
            <button onclick="location.reload()" class="btn-secondary text-xs !py-2 !px-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </button>
            <?php if (user_can('tables.manage')): ?>
                <a href="<?= e(url('/tables/create')) ?>" class="btn-primary text-xs !py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Add Table
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status Legend -->
    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400">
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span> Available (<?= $availableCount ?>)</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400 opacity-60"></span> Occupied (<?= $occupiedCount ?>)</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-indigo-400"></span> Reserved (<?= $reservedCount ?>)</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span> Maintenance (<?= $maintenanceCount ?>)</span>
    </div>

    <!-- ── Table Grid ────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 fade-in-stagger" id="tables-grid">
        <?php foreach ($tables as $table): ?>
            <?php
                $status = $table['status'];
                $tileClass = match($status) {
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
                $isOccupied = $status === 'occupied' && $elapsed > 0;
            ?>
            <div class="table-tile <?= $tileClass ?> group relative"
                 data-table-id="<?= (int) $table['id'] ?>"
                 data-status="<?= e($status) ?>"
                 <?php if ($isOccupied && $session): ?>
                     data-start-time="<?= e($session['start_time'] ?? '') ?>"
                     data-paused-total="<?= (int) ($session['paused_total_sec'] ?? 0) ?>"
                     data-session-status="<?= e($session['status'] ?? '') ?>"
                 <?php endif; ?>>

                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-bold text-white">#<?= e($table['number']) ?></span>
                    <div class="flex items-center gap-1.5">
                        <span class="status-dot flex-shrink-0"></span>
                        <?php if (user_can('tables.manage')): ?>
                            <a href="<?= e(url('/tables/' . (int) $table['id'] . '/edit')) ?>" title="Edit table"
                               onclick="event.stopPropagation()" class="p-1 rounded-md text-slate-400 hover:text-white hover:bg-white/10 opacity-0 group-hover:opacity-100 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5M18.4 2.6a2.1 2.1 0 013 3L12 15l-4 1 1-4 9.4-9.4z"/></svg>
                            </a>
                            <?php if (in_array($status, ['available', 'maintenance'])): ?>
                                <form method="POST" action="<?= e(url('/tables/' . (int) $table['id'] . '/toggle')) ?>"
                                      class="opacity-0 group-hover:opacity-100 transition" onclick="event.stopPropagation()">
                                    <?= csrf_field() ?>
                                    <button type="submit" title="<?= $status === 'maintenance' ? 'Mark available' : 'Send to maintenance' ?>"
                                            class="p-1 rounded-md text-slate-400 hover:text-white hover:bg-white/10">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 17.66L7.66 20.34a4.5 4.5 0 01-6.36-6.36l2.68-2.68m13.36 0l-2.68 2.68m1.34 5.66l1.5 1.5A2.5 2.5 0 1014 17.66l-1.5-1.5m3.5-13.5l4 4a2.5 2.5 0 11-3.54 3.54l-4-4a2.5 2.5 0 013.54-3.54z"/></svg>
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="text-xs text-slate-400 mb-2 truncate"><?= e($table['name']) ?></p>

                <?php if (!empty($canViewCctv)): ?>
                    <div class="relative mb-2 rounded-lg overflow-hidden bg-black/50 ring-1 ring-white/10 aspect-video">
                        <?php if (!empty($table['camera']['hls_url'])): ?>
                            <video class="table-cam w-full h-full object-cover" muted autoplay playsinline
                                   data-hls-src="<?= e($table['camera']['hls_url']) ?>"></video>
                            <button type="button" title="Fullscreen"
                                    data-player="<?= e($table['camera']['player_url'] ?? '') ?>"
                                    onclick="event.stopPropagation(); openCam(this)"
                                    class="absolute bottom-1 right-1 p-1 rounded-md bg-black/60 text-slate-300 hover:text-white opacity-0 group-hover:opacity-100 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                            </button>
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col items-center justify-center text-slate-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/><path stroke-linecap="round" d="M3 3l18 18"/></svg>
                                <span class="text-[10px] mt-0.5">No signal</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($isOccupied && $session):
                    $players = array_values(array_filter([
                        trim((string) ($session['player_winner'] ?? '')),
                        trim((string) ($session['player_loser'] ?? '')),
                    ]));
                    $clientLabel = trim((string) ($session['client_name'] ?? ''));
                    if ($clientLabel === '') { $clientLabel = trim((string) ($session['customer_name'] ?? '')); }
                    if ($clientLabel === '' && $players !== []) { $clientLabel = implode(' vs ', $players); }
                    $startTxt = date('g:i A', strtotime($session['start_time']));
                    $endTxt   = !empty($session['expected_end_time']) ? date('g:i A', strtotime($session['expected_end_time'])) : null;
                    $isFixed  = ($session['charge_type'] ?? 'timer') === 'fixed';
                    $payStatus = (string) ($session['payment_status'] ?? 'unpaid');
                    if (!$isFixed) {
                        $rate = (float) ($session['rate'] ?? $table['hourly_rate']);
                        $min  = (float) ($table['min_charge'] ?? 100);
                        $est  = $elapsed > 0 ? round(($elapsed / 3600) * $rate) : 0;
                        if ($elapsed > 0 && $est < $min) { $est = $min; }
                        $est = (int) (ceil($est / 10) * 10);
                    }
                ?>
                    <div class="flex items-center gap-1.5 mb-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-emerald-400 timer-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-xs font-mono text-emerald-400 font-semibold timer-display"
                              data-start="<?= date('U', strtotime($session['start_time'])) ?>"
                              data-paused="<?= e((string) ($session['paused_total_sec'] ?? 0)) ?>"
                              data-status="<?= e($session['status'] ?? '') ?>">
                            <?= format_duration($elapsed) ?>
                        </span>
                    </div>
                    <?php if ($players !== []): ?>
                        <p class="text-[11px] text-white/90 font-medium truncate mb-0.5"><?= e(implode(' vs ', $players)) ?></p>
                    <?php endif; ?>
                    <?php if ($clientLabel !== ''): ?>
                        <p class="text-[11px] text-slate-400 truncate mb-0.5"><?= e($clientLabel) ?></p>
                    <?php endif; ?>
                    <p class="text-[10px] text-slate-500 mb-1.5">
                        <?= e($startTxt) ?><?= $endTxt ? ' &rarr; ' . e($endTxt) : '' ?>
                    </p>
                    <div class="flex items-center justify-between gap-1">
                        <?php if ($isFixed): ?>
                            <span class="text-[11px] font-semibold text-white tile-fixed" data-amount="<?= (float) ($session['fixed_amount'] ?? 0) ?>">Rs <?= number_format((float) ($session['fixed_amount'] ?? 0)) ?></span>
                        <?php else: ?>
                            <span class="text-[11px] font-semibold text-emerald-400 tile-amount"
                                  data-rate="<?= (float) ($session['rate'] ?? $table['hourly_rate']) ?>"
                                  data-min-charge="<?= (float) ($table['min_charge'] ?? 100) ?>">Rs <?= number_format($est) ?></span>
                        <?php endif; ?>
                        <span class="badge badge-<?= match($payStatus) { 'paid' => 'emerald', 'partial' => 'amber', default => 'rose' } ?> !text-[9px]">
                            <?= $payStatus === 'paid' ? 'Paid' : ($payStatus === 'partial' ? 'Partial' : 'Udhaar') ?>
                        </span>
                    </div>
                <?php elseif ($status === 'reserved'): ?>
                    <p class="text-[11px] text-indigo-400 font-medium">Reserved</p>
                <?php else: ?>
                    <p class="text-[11px] text-slate-500">Rs <?= number_format((float) $table['hourly_rate']) ?>/hr</p>
                <?php endif; ?>

                <div class="mt-3 flex items-center justify-between">
                    <span class="badge badge-<?= match($status) {
                        'available' => 'emerald',
                        'occupied'  => 'emerald',
                        'reserved'  => 'violet',
                        default     => 'rose',
                    } ?>"><?= $label ?></span>

                    <?php if ($status === 'available'): ?>
                        <?php if (user_can('sessions.manage')): ?>
                        <button class="btn-primary !py-1.5 !px-3 !text-[11px] opacity-0 group-hover:opacity-100 transition-opacity"
                                onclick="event.stopPropagation(); openStartModal(<?= (int) $table['id'] ?>, '<?= e($table['number']) ?>', <?= (float) $table['hourly_rate'] ?>)">
                            Start
                        </button>
                        <?php endif; ?>
                    <?php elseif ($status === 'occupied' && $session): ?>
                        <?php if (user_can('sessions.manage')): ?>
                        <button class="btn-danger !py-1.5 !px-3 !text-[11px] opacity-0 group-hover:opacity-100 transition-opacity"
                                onclick="event.stopPropagation(); endSession(<?= (int) $session['id'] ?>, <?= (int) $table['id'] ?>)">
                            End
                        </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($tables)): ?>
        <div class="col-span-full empty-state fade-in-stagger">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><rect x="4" y="6" width="16" height="10" rx="1.5"/><path stroke-linecap="round" d="M4 12h16M9 20h6M12 16v4"/></svg>
            <p class="text-slate-400">No tables yet</p>
            <p class="text-xs text-slate-600 mt-1">Add your first table to start tracking snooker sessions.</p>
            <?php if (user_can('tables.manage')): ?>
                <a href="<?= e(url('/tables/create')) ?>" class="btn-primary text-xs mt-4">Add Table</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Active Sessions Panel ─────────────────────────────────── -->
    <?php if (!empty($activeSessions)): ?>
    <div class="card p-6">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Active Sessions</h2>
                <span class="badge badge-emerald"><?= count($activeSessions) ?></span>
            </div>
            <a href="/sessions/active" class="text-xs text-emerald-400 hover:text-emerald-300 transition font-medium">View all &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Customer</th>
                        <th>Players</th>
                        <th>Rate</th>
                        <th>Elapsed</th>
                        <th class="text-right">Est. Amount</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activeSessions as $sess): ?>
                        <?php
                            $elapsedNow = 0;
                            if ($sess['status'] === 'active') {
                                $elapsedNow = time() - strtotime($sess['start_time']);
                                $elapsedNow -= (int) ($sess['paused_total_sec'] ?? 0);
                                $elapsedNow = max(0, $elapsedNow);
                            }
                            $isFixedSess = ($sess['charge_type'] ?? 'timer') === 'fixed';
                            $estAmount = 0;
                            if ($isFixedSess) {
                                $estAmount = (float) ($sess['fixed_amount'] ?? 0);
                            } elseif ($elapsedNow > 0) {
                                $rate = (float) ($sess['rate'] ?? $sess['table_rate'] ?? 300);
                                $estAmount = round(($elapsedNow / 3600) * $rate);
                                $minCharge = (float) ($sess['table_min_charge'] ?? 100);
                                if ($estAmount < $minCharge) $estAmount = $minCharge;
                                $estAmount = ceil($estAmount / 10) * 10;
                            }
                            $sessPlayers = array_values(array_filter([
                                trim((string) ($sess['player_winner'] ?? '')),
                                trim((string) ($sess['player_loser'] ?? '')),
                            ]));
                            $sessClient = trim((string) ($sess['client_name'] ?? ''));
                            if ($sessClient === '') { $sessClient = trim((string) ($sess['customer_name'] ?? '')); }
                            $sessPayStatus = (string) ($sess['payment_status'] ?? 'unpaid');
                        ?>
                        <tr data-session-id="<?= (int) $sess['id'] ?>">
                            <td>
                                <a href="/tables" class="font-medium text-white hover:text-emerald-400 transition">
                                    #<?= e($sess['table_number']) ?>
                                </a>
                                <span class="text-slate-500 ml-1"> <?= e($sess['table_name']) ?></span>
                            </td>
                            <td>
                                <?php if ($sessClient !== ''): ?>
                                    <span class="text-white"><?= e($sessClient) ?></span>
                                    <?php if (!empty($sess['customer_phone'])): ?>
                                        <br><span class="text-[11px] text-slate-500"><?= e($sess['customer_phone']) ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-slate-500 italic">Walk-in</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-slate-400">
                                <?php if ($sessPlayers !== []): ?>
                                    <span class="text-slate-200"><?= e(implode(' vs ', $sessPlayers)) ?></span><br>
                                <?php endif; ?>
                                <span class="text-[11px] text-slate-500"><?= (int) ($sess['players_count'] ?? 1) ?> player(s)</span>
                            </td>
                            <td>
                                <?php if ($isFixedSess): ?>
                                    <span class="badge badge-amber">Fixed</span>
                                    <span class="text-[11px] text-slate-500 ml-1">Rs <?= number_format((float) ($sess['fixed_amount'] ?? 0)) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-sky"><?= e(ucfirst($sess['rate_type'] ?? 'hourly')) ?></span>
                                    <span class="text-[11px] text-slate-500 ml-1">Rs <?= number_format((float) ($sess['rate'] ?? 0)) ?>/hr</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="font-mono text-sm font-semibold session-timer <?= $sess['status'] === 'active' ? 'text-emerald-400' : 'text-amber-400' ?>"
                                      data-start-time="<?= date('U', strtotime($sess['start_time'])) ?>"
                                      data-paused-total="<?= (int) ($sess['paused_total_sec'] ?? 0) ?>"
                                      data-session-status="<?= e($sess['status']) ?>">
                                    <?= format_duration($elapsedNow) ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <?php if ($isFixedSess): ?>
                                    <span class="font-semibold text-white">Rs <?= number_format($estAmount) ?></span>
                                <?php else: ?>
                                    <span class="font-semibold text-white est-amount" data-rate="<?= (float) ($sess['rate'] ?? $sess['table_rate'] ?? 300) ?>" data-min-charge="<?= (float) ($sess['table_min_charge'] ?? 100) ?>">
                                        Rs <?= number_format($estAmount) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $sess['status'] === 'active' ? 'emerald' : 'amber' ?>">
                                    <?= ucfirst($sess['status']) ?>
                                </span>
                                <span class="badge badge-<?= match($sessPayStatus) { 'paid' => 'emerald', 'partial' => 'amber', default => 'rose' } ?> ml-1">
                                    <?= $sessPayStatus === 'paid' ? 'Paid' : ($sessPayStatus === 'partial' ? 'Partial' : 'Udhaar') ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button class="btn-danger !py-1.5 !px-3 !text-[11px]"
                                            onclick="endSession(<?= (int) $sess['id'] ?>, <?= (int) $sess['table_id'] ?>)">
                                        End Session
                                    </button>
                                    <a href="/payments?session_id=<?= (int) $sess['id'] ?>" class="btn-primary !py-1.5 !px-3 !text-[11px]">
                                        Pay
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Start Session Modal ───────────────────────────────────── -->
    <div x-show="showStartModal" x-cloak
         class="modal-overlay"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="showStartModal = false"
         @keydown.escape.window="showStartModal = false">

        <div class="modal-card"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             @click.stop>

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/[0.06]">
                <div>
                    <h3 class="text-base font-semibold text-white">Start Session</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Table <span class="text-emerald-400 font-semibold" x-text="'#' + modalTableNumber"></span></p>
                </div>
                <button @click="showStartModal = false" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 flex items-center justify-center transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body -->
            <form @submit.prevent="submitStartSession()" class="p-6 space-y-5">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" x-model="modalTableId">

                <!-- Customer Search -->
                <div class="relative">
                    <label class="block text-xs font-medium text-slate-400 mb-2">Customer</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text"
                               x-model="customerSearch"
                               @input.debounce.300ms="searchCustomers()"
                               @focus="customerSearchFocused = true"
                               @click.outside="setTimeout(() => customerSearchFocused = false, 150)"
                               placeholder="Search by name or phone..."
                               class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 focus:outline-none transition">
                        <div x-show="selectedCustomer" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <button type="button" @click="selectedCustomer = null; customerSearch = ''" class="text-slate-500 hover:text-white transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <!-- Customer dropdown -->
                    <div x-show="customerSearchFocused && customerResults.length > 0 && !selectedCustomer"
                         class="absolute z-50 mt-1 w-full bg-ink-800 border border-white/10 rounded-xl shadow-2xl max-h-48 overflow-y-auto">
                        <template x-for="c in customerResults" :key="c.id">
                            <button type="button" @click="selectCustomer(c)"
                                    class="w-full text-left px-4 py-2.5 hover:bg-white/5 transition text-sm flex items-center justify-between">
                                <div>
                                    <span class="text-white font-medium" x-text="c.name"></span>
                                    <span x-show="c.phone" class="text-slate-500 text-xs ml-2" x-text="c.phone"></span>
                                </div>
                                <span class="badge badge-slate text-[10px]" x-text="c.category || 'Regular'"></span>
                            </button>
                        </template>
                    </div>
                    <p x-show="selectedCustomer" class="mt-2 text-xs text-emerald-400 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="selectedCustomer?.name"></span> selected
                    </p>
                </div>

                <!-- Winner / Loser -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-2">Winner</label>
                        <input type="text" x-model="playerWinner" placeholder="Winning player"
                               class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-2">Loser</label>
                        <input type="text" x-model="playerLoser" placeholder="Losing player"
                               class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition">
                    </div>
                </div>

                <!-- Client identity -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-2">Client Name</label>
                        <input type="text" x-model="clientName" placeholder="Who is paying?"
                               class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-2">Phone <span class="text-slate-600">(for udhaar)</span></label>
                        <input type="tel" x-model="clientPhone" placeholder="03xx-xxxxxxx"
                               class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition">
                    </div>
                </div>

                <!-- Charge type -->
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-2">Charge Mode</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="chargeType = 'timer'"
                                :class="chargeType === 'timer' ? 'border-emerald-500/60 bg-emerald-500/10 text-emerald-300' : 'border-white/10 text-slate-400 hover:text-white'"
                                class="rounded-xl border px-4 py-2.5 text-sm font-medium transition">Live Timer</button>
                        <button type="button" @click="chargeType = 'fixed'"
                                :class="chargeType === 'fixed' ? 'border-emerald-500/60 bg-emerald-500/10 text-emerald-300' : 'border-white/10 text-slate-400 hover:text-white'"
                                class="rounded-xl border px-4 py-2.5 text-sm font-medium transition">Fixed Amount</button>
                    </div>
                </div>

                <!-- Fixed amount -->
                <div x-show="chargeType === 'fixed'" x-cloak>
                    <label class="block text-xs font-medium text-slate-400 mb-2">Fixed Amount (Rs) *</label>
                    <input type="number" x-model.number="fixedAmount" step="1" min="0" placeholder="0"
                           class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition">
                </div>

                <!-- Players Count & Rate Type -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-2">Players</label>
                        <select x-model="playersCount"
                                class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition appearance-none">
                            <option value="1">1 Player</option>
                            <option value="2">2 Players</option>
                            <option value="3">3 Players</option>
                            <option value="4">4 Players</option>
                            <option value="5">5 Players</option>
                            <option value="6">6 Players</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-2">Rate Type</label>
                        <select x-model="rateType"
                                class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition appearance-none">
                            <option value="hourly">Hourly</option>
                            <option value="frame">Per Frame</option>
                            <option value="peak">Peak Rate</option>
                            <option value="off_peak">Off-Peak</option>
                            <option value="vip">VIP Rate</option>
                            <option value="night">Night Rate</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                </div>

                <!-- Expected end -->
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-2">Expected End <span class="text-slate-600">(optional)</span></label>
                    <input type="datetime-local" x-model="expectedEnd"
                           class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition">
                </div>

                <!-- Payment -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-2">Payment Method</label>
                        <select x-model="paymentMethod"
                                class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition appearance-none">
                            <?php foreach (\App\Models\Payment::METHODS as $key => $label): ?>
                                <option value="<?= e($key) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-2">Payment Timing</label>
                        <select x-model="payMode"
                                class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition appearance-none">
                            <option value="later">Pay Later (Udhaar)</option>
                            <option value="now" :disabled="chargeType !== 'fixed'">Pay Now</option>
                        </select>
                    </div>
                </div>
                <p x-show="chargeType !== 'fixed'" class="text-[11px] text-amber-400/80 -mt-1">
                    Timer sessions are settled when the session ends.
                </p>
                <p x-show="payMode === 'later' && chargeType === 'fixed'" class="text-[11px] text-rose-400/80 -mt-1">
                    Udhaar requires a client name and phone, or the balance cannot be traced.
                </p>

                <!-- Notes -->
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-2">Notes <span class="text-slate-600">(optional)</span></label>
                    <textarea x-model="notes" rows="2" placeholder="Any notes..."
                              class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition resize-none"></textarea>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-between pt-2">
                    <p class="text-xs text-slate-500">
                        <template x-if="chargeType === 'fixed'">
                            <span>Fixed: <span class="text-emerald-400 font-semibold" x-text="'Rs ' + Number(fixedAmount || 0).toLocaleString()"></span></span>
                        </template>
                        <template x-if="chargeType !== 'fixed'">
                            <span>Rate: <span class="text-emerald-400 font-semibold" x-text="'Rs ' + Number(modalHourlyRate).toLocaleString() + '/hr'"></span></span>
                        </template>
                    </p>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="showStartModal = false" class="btn-secondary">Cancel</button>
                        <button type="submit"
                                class="btn-primary"
                                :disabled="startSubmitting"
                                :class="{ 'opacity-50 cursor-not-allowed': startSubmitting }">
                            <svg x-show="startSubmitting" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <span x-text="startSubmitting ? 'Starting...' : 'Start Session'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ── End Session Confirmation Modal ────────────────────────── -->
    <div x-show="showEndModal" x-cloak
         class="modal-overlay"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="showEndModal = false"
         @keydown.escape.window="showEndModal = false">

        <div class="modal-card max-w-md"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             @click.stop>

            <div class="p-6">
                <div class="text-center mb-5">
                    <div class="w-12 h-12 rounded-full bg-rose-500/15 flex items-center justify-center mx-auto mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.832c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <h3 class="text-base font-semibold text-white mb-1">End Session?</h3>
                    <p class="text-sm text-slate-400">Table <span class="text-white font-medium" x-text="'#' + endModalTableNumber"></span></p>
                    <p class="text-sm text-slate-400 mt-1">
                        Amount due:
                        <span class="text-white font-semibold" x-text="'Rs ' + Number(endModalAmount).toLocaleString()"></span>
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-2">Amount Collected Now (Rs)</label>
                        <input type="number" x-model.number="endPayAmount" step="1" min="0"
                               class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-2">Payment Method</label>
                        <select x-model="endMethod"
                                class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none appearance-none">
                            <?php foreach (\App\Models\Payment::METHODS as $key => $label): ?>
                                <option value="<?= e($key) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Set collected amount to 0 to send the whole amount to Udhaar
                        (requires a linked customer).
                    </p>
                </div>

                <div class="flex items-center gap-3 justify-center mt-5">
                    <button @click="showEndModal = false" class="btn-secondary">Cancel</button>
                    <button @click="confirmEndSession()"
                            class="btn-danger"
                            :disabled="endSubmitting"
                            :class="{ 'opacity-50 cursor-not-allowed': endSubmitting }">
                        <svg x-show="endSubmitting" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="endSubmitting ? 'Ending...' : 'End Session'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function tableCommandCenter(prefill) {
    return {
        // Start Modal
        showStartModal: false,
        modalTableId: null,
        modalTableNumber: '',
        modalHourlyRate: 0,
        customerSearch: '',
        customerResults: [],
        selectedCustomer: null,
        customerSearchFocused: false,
        playersCount: 1,
        rateType: 'hourly',
        playerWinner: '',
        playerLoser: '',
        clientName: '',
        clientPhone: '',
        chargeType: 'timer',
        fixedAmount: 0,
        expectedEnd: '',
        paymentMethod: 'cash',
        payMode: 'later',
        notes: '',
        startSubmitting: false,

        // End Modal
        showEndModal: false,
        endSessionId: null,
        endTableId: null,
        endModalTableNumber: '',
        endModalAmount: 0,
        endPayAmount: 0,
        endMethod: 'cash',
        endSubmitting: false,

        init() {
            // Alpine binds `this` to the component here (unlike x-init, where it is the global scope).
            window.__tablesComp = this;
            if (window.location.search.includes('start_session=1')) {
                if (prefill && Number.isInteger(prefill.id) && prefill.id > 0) {
                    // Quick start: prefill the first free table so the modal is never
                    // submitted without a table id (was crashing as /api/tables/null/start).
                    this.openStartModal(prefill.id, prefill.number, prefill.hourly_rate);
                } else {
                    // No free table right now — keep the modal closed instead of
                    // letting the user submit an empty table.
                    alert('No table is available to start a session right now.');
                }
            }
        },

        async searchCustomers() {
            if (this.customerSearch.length < 2) { this.customerResults = []; return; }
            try {
                const res = await apiGet('/api/customers/search?term=' + encodeURIComponent(this.customerSearch));
                this.customerResults = res.data || res || [];
            } catch(e) { this.customerResults = []; }
        },

        selectCustomer(c) {
            this.selectedCustomer = c;
            this.customerSearch = c.name;
            this.customerResults = [];
            this.customerSearchFocused = false;
        },

        openStartModal(id, number, rate) {
            this.modalTableId = id;
            this.modalTableNumber = number;
            this.modalHourlyRate = rate;
            this.customerSearch = '';
            this.selectedCustomer = null;
            this.customerResults = [];
            this.playersCount = 1;
            this.rateType = 'hourly';
            this.playerWinner = '';
            this.playerLoser = '';
            this.clientName = '';
            this.clientPhone = '';
            this.chargeType = 'timer';
            this.fixedAmount = 0;
            this.expectedEnd = '';
            this.paymentMethod = 'cash';
            this.payMode = 'later';
            this.notes = '';
            this.showStartModal = true;
        },

        async submitStartSession() {
            const tableId = parseInt(this.modalTableId, 10);
            if (!Number.isInteger(tableId) || tableId <= 0) {
                alert('Could not identify the table for this session. Please refresh and try again.');
                this.startSubmitting = false;
                return;
            }
            if (this.chargeType === 'fixed' && !(Number(this.fixedAmount) > 0)) {
                alert('Fixed amount must be greater than zero.');
                return;
            }
            if (this.payMode === 'later' && this.chargeType === 'fixed'
                && Number(this.fixedAmount) > 0 && !this.clientPhone.trim()) {
                alert('Udhaar ke liye client ka phone number zaroori hai.');
                return;
            }
            this.startSubmitting = true;
            try {
                const result = await apiPost('/api/tables/' + tableId + '/start', {
                    customer_id: this.selectedCustomer ? this.selectedCustomer.id : null,
                    players_count: parseInt(this.playersCount),
                    rate_type: this.rateType,
                    player_winner: this.playerWinner,
                    player_loser: this.playerLoser,
                    client_name: this.clientName,
                    customer_name: this.clientName,
                    customer_phone: this.clientPhone,
                    charge_type: this.chargeType,
                    fixed_amount: this.chargeType === 'fixed' ? Number(this.fixedAmount) : 0,
                    expected_end_time: this.expectedEnd,
                    payment_method: this.paymentMethod,
                    pay_mode: this.payMode,
                    notes: this.notes
                });
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Failed to start session');
                }
            } catch(e) {
                alert('Network error. Please try again.');
            }
            this.startSubmitting = false;
        },

        endSession(sessionId, tableId) {
            const tile = document.querySelector('[data-table-id="' + tableId + '"]');
            this.endSessionId = sessionId;
            this.endTableId = tableId;
            this.endModalTableNumber = tile ? tile.querySelector('.text-sm.font-bold')?.textContent?.replace('#','') || '' : '';

            let amount = 0;
            if (tile) {
                const fixedEl = tile.querySelector('.tile-fixed');
                const amtEl = tile.querySelector('.tile-amount');
                const timerEl = tile.querySelector('.timer-display');
                if (fixedEl) {
                    amount = parseFloat(fixedEl.dataset.amount || '0') || 0;
                } else if (amtEl && timerEl) {
                    const rate = parseFloat(amtEl.dataset.rate || '0') || 0;
                    const min = parseFloat(amtEl.dataset.minCharge || '0') || 0;
                    const start = parseInt(timerEl.dataset.start || '0', 10);
                    const paused = parseInt(timerEl.dataset.paused || '0', 10);
                    const elapsed = Math.max(0, Math.floor(Date.now() / 1000) - start - paused);
                    amount = Math.round((elapsed / 3600) * rate);
                    if (elapsed > 0 && amount < min) amount = min;
                    amount = Math.ceil(amount / 10) * 10;
                }
            }
            this.endModalAmount = amount;
            this.endPayAmount = amount;
            this.showEndModal = true;
        },

        async confirmEndSession() {
            const sessionId = parseInt(this.endSessionId, 10);
            if (!Number.isInteger(sessionId) || sessionId <= 0) {
                alert('Could not identify the session. Please refresh and try again.');
                this.endSubmitting = false;
                return;
            }
            this.endSubmitting = true;
            try {
                const result = await apiPost('/api/sessions/' + sessionId + '/end', {
                    pay_amount: Number(this.endPayAmount) || 0,
                    method: this.endMethod
                });
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Failed to end session');
                }
            } catch(e) {
                alert('Network error. Please try again.');
            }
            this.endSubmitting = false;
        }
    };
}

// Global helper for inline onclick handlers — resolves the Table Command
// Center Alpine component reliably (never the layout/header scopes).
function tablesCommandComponent() {
    if (window.Alpine) {
        try {
            const root = document.getElementById('tables-root');
            if (root) {
                const comp = Alpine.$data(root);
                if (comp && comp !== window && typeof comp.openStartModal === 'function' && typeof comp.endSession === 'function') {
                    return comp;
                }
            }
        } catch (e) { /* fall through */ }
    }
    if (window.__tablesComp && window.__tablesComp !== window
        && typeof window.__tablesComp.openStartModal === 'function'
        && typeof window.__tablesComp.endSession === 'function'
        && window.__tablesComp.endSession !== window.endSession) {
        return window.__tablesComp;
    }
    return null;
}

function openStartModal(id, number, rate) {
    const comp = tablesCommandComponent();
    if (!comp) { alert('Interactive controls failed to load — please refresh the page.'); return; }
    comp.openStartModal(id, number, rate);
}

function endSession(sessionId, tableId) {
    const comp = tablesCommandComponent();
    if (!comp) { alert('Interactive controls failed to load — please refresh the page.'); return; }
    comp.endSession(sessionId, tableId);
}

// ── Live Timer System ──────────────────────────────────────────
function tickTimers() {
    document.querySelectorAll('.timer-display').forEach(el => {
        const start = parseInt(el.dataset.start || '0', 10);
        const paused = parseInt(el.dataset.paused || '0', 10);
        const status = el.dataset.status;
        if (!start || status !== 'active') return;

        const now = Math.floor(Date.now() / 1000);
        let elapsed = now - start - paused;
        if (elapsed < 0) elapsed = 0;
        el.textContent = formatDuration(elapsed);
    });

    document.querySelectorAll('.session-timer').forEach(el => {
        const start = parseInt(el.dataset.startTime || '0', 10);
        const paused = parseInt(el.dataset.pausedTotal || '0', 10);
        const status = el.dataset.sessionStatus;
        if (!start || status !== 'active') return;

        const now = Math.floor(Date.now() / 1000);
        let elapsed = now - start - paused;
        if (elapsed < 0) elapsed = 0;
        el.textContent = formatDuration(elapsed);

        // Update estimated amount
        const row = el.closest('tr');
        if (row) {
            const amtEl = row.querySelector('.est-amount');
            if (amtEl) {
                const rate = parseFloat(amtEl.dataset.rate || 300);
                const minCharge = parseFloat(amtEl.dataset.minCharge || 100);
                let amount = Math.round((elapsed / 3600) * rate);
                if (amount < minCharge && elapsed > 0) amount = minCharge;
                amount = Math.ceil(amount / 10) * 10;
                amtEl.textContent = formatCurrency(amount);
            }
        }
    });

    // Update per-table card amount (timer sessions only)
    document.querySelectorAll('.timer-display').forEach(el => {
        const tile = el.closest('[data-table-id]');
        if (!tile) return;
        const amtEl = tile.querySelector('.tile-amount');
        if (!amtEl) return;
        const status = el.dataset.status;
        if (status !== 'active') return;
        const rate = parseFloat(amtEl.dataset.rate || '0') || 0;
        const minCharge = parseFloat(amtEl.dataset.minCharge || '0') || 0;
        const now = Math.floor(Date.now() / 1000);
        const elapsed = Math.max(0, now - parseInt(el.dataset.start || '0', 10) - parseInt(el.dataset.paused || '0', 10));
        let amount = Math.round((elapsed / 3600) * rate);
        if (amount < minCharge && elapsed > 0) amount = minCharge;
        amount = Math.ceil(amount / 10) * 10;
        amtEl.textContent = formatCurrency(amount);
    });
}

setInterval(tickTimers, 1000);

// ── Table camera thumbnails ────────────────────────────────────
function openCam(btn) {
    const url = btn?.dataset?.player;
    if (!url) { alert('No camera stream is linked to this table.'); return; }
    window.open(url, '_blank', 'noopener');
}

(function initTableCameras() {
    const videos = document.querySelectorAll('.table-cam');
    if (!videos.length) return;

    function attach() {
        videos.forEach(video => {
            const src = video.dataset.hlsSrc;
            if (!src) return;
            const hide = () => {
                video.style.visibility = 'hidden';
                const ph = video.parentElement?.querySelector('.cam-offline');
                if (ph) ph.style.display = 'flex';
            };
            video.addEventListener('error', hide);
            video.addEventListener('stalled', hide);
            if (window.Hls && Hls.isSupported()) {
                const hls = new Hls({ lowLatencyMode: true });
                hls.loadSource(src);
                hls.attachMedia(video);
                hls.on(Hls.Events.ERROR, (_, data) => { if (data.fatal) hide(); });
                video.muted = true;
                video.play().catch(hide);
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = src;
                video.muted = true;
                video.play().catch(hide);
            }
        });
    }

    if (window.Hls) { attach(); }
    else {
        const s = document.createElement('script');
        s.src = '/assets/vendor/hls.min.js';
        s.onload = attach;
        document.head.appendChild(s);
    }
})();
</script>
