<?php
/** @var array $tables — each with 'current_session', 'elapsed_seconds' */
/** @var array $activeSessions */
/** @var bool $startSession */

$occupiedCount = count(array_filter($tables, fn($t) => $t['status'] === 'occupied'));
$availableCount = count(array_filter($tables, fn($t) => $t['status'] === 'available'));
$reservedCount = count(array_filter($tables, fn($t) => $t['status'] === 'reserved'));
$maintenanceCount = count(array_filter($tables, fn($t) => $t['status'] === 'maintenance'));
?>

<div class="space-y-6 fade-in" x-data="tableCommandCenter()">

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
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3" id="tables-grid">
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
                    <span class="status-dot flex-shrink-0"></span>
                </div>
                <p class="text-xs text-slate-400 mb-2 truncate"><?= e($table['name']) ?></p>

                <?php if ($isOccupied && $session): ?>
                    <div class="flex items-center gap-1.5 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-emerald-400 timer-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-xs font-mono text-emerald-400 font-semibold timer-display"
                              data-start="<?= e($session['start_time'] ?? '') ?>"
                              data-paused="<?= e((string)($session['paused_total_sec'] ?? 0)) ?>"
                              data-status="<?= e($session['status'] ?? '') ?>">
                            <?= format_duration($elapsed) ?>
                        </span>
                    </div>
                    <?php if (!empty($session['customer_name'])): ?>
                        <p class="text-[11px] text-slate-400 truncate mb-1"><?= e($session['customer_name']) ?></p>
                    <?php endif; ?>
                    <p class="text-[11px] text-emerald-400/70 font-medium tracking-wide">Rs <?= number_format((float)($session['rate'] ?? $table['hourly_rate'])) ?>/hr</p>
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
                        <button class="btn-primary !py-1.5 !px-3 !text-[11px] opacity-0 group-hover:opacity-100 transition-opacity"
                                onclick="event.stopPropagation(); openStartModal(<?= (int) $table['id'] ?>, '<?= e($table['number']) ?>', <?= (float) $table['hourly_rate'] ?>)">
                            Start
                        </button>
                    <?php elseif ($status === 'occupied' && $session): ?>
                        <button class="btn-danger !py-1.5 !px-3 !text-[11px] opacity-0 group-hover:opacity-100 transition-opacity"
                                onclick="event.stopPropagation(); endSession(<?= (int) $session['id'] ?>, <?= (int) $table['id'] ?>)">
                            End
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
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
                            $estAmount = 0;
                            if ($elapsedNow > 0) {
                                $rate = (float) ($sess['rate'] ?? $sess['table_rate'] ?? 300);
                                $estAmount = round(($elapsedNow / 3600) * $rate);
                                $minCharge = (float) ($sess['table_min_charge'] ?? 100);
                                if ($estAmount < $minCharge) $estAmount = $minCharge;
                                $estAmount = ceil($estAmount / 10) * 10;
                            }
                        ?>
                        <tr data-session-id="<?= (int) $sess['id'] ?>">
                            <td>
                                <a href="/tables/<?= (int) $sess['table_id'] ?>" class="font-medium text-white hover:text-emerald-400 transition">
                                    #<?= e($sess['table_number']) ?>
                                </a>
                                <span class="text-slate-500 ml-1"> <?= e($sess['table_name']) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($sess['customer_name'])): ?>
                                    <span class="text-white"><?= e($sess['customer_name']) ?></span>
                                    <?php if (!empty($sess['customer_phone'])): ?>
                                        <br><span class="text-[11px] text-slate-500"><?= e($sess['customer_phone']) ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-slate-500 italic">Walk-in</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-slate-400"><?= (int) ($sess['players_count'] ?? 1) ?></td>
                            <td>
                                <span class="badge badge-sky"><?= e(ucfirst($sess['rate_type'] ?? 'hourly')) ?></span>
                                <span class="text-[11px] text-slate-500 ml-1">Rs <?= number_format((float) ($sess['rate'] ?? 0)) ?>/hr</span>
                            </td>
                            <td>
                                <span class="font-mono text-sm font-semibold session-timer <?= $sess['status'] === 'active' ? 'text-emerald-400' : 'text-amber-400' ?>"
                                      data-start-time="<?= e($sess['start_time']) ?>"
                                      data-paused-total="<?= (int) ($sess['paused_total_sec'] ?? 0) ?>"
                                      data-session-status="<?= e($sess['status']) ?>">
                                    <?= format_duration($elapsedNow) ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <span class="font-semibold text-white est-amount" data-rate="<?= (float) ($sess['rate'] ?? $sess['table_rate'] ?? 300) ?>" data-min-charge="<?= (float) ($sess['table_min_charge'] ?? 100) ?>">
                                    Rs <?= number_format($estAmount) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $sess['status'] === 'active' ? 'emerald' : 'amber' ?>">
                                    <?= ucfirst($sess['status']) ?>
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

                <!-- Notes -->
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-2">Notes <span class="text-slate-600">(optional)</span></label>
                    <textarea x-model="notes" rows="2" placeholder="Any notes..."
                              class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition resize-none"></textarea>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-between pt-2">
                    <p class="text-xs text-slate-500">
                        Rate: <span class="text-emerald-400 font-semibold" x-text="'Rs ' + Number(modalHourlyRate).toLocaleString() + '/hr'"></span>
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

        <div class="modal-card max-w-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             @click.stop>

            <div class="p-6 text-center">
                <div class="w-12 h-12 rounded-full bg-rose-500/15 flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.832c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <h3 class="text-base font-semibold text-white mb-1">End Session?</h3>
                <p class="text-sm text-slate-400 mb-1">Table <span class="text-white font-medium" x-text="'#' + endModalTableNumber"></span></p>
                <p class="text-xs text-slate-500 mb-5">The session will be marked completed and the table freed.</p>

                <div class="flex items-center gap-3 justify-center">
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

<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
function tableCommandCenter() {
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
        notes: '',
        startSubmitting: false,

        // End Modal
        showEndModal: false,
        endSessionId: null,
        endTableId: null,
        endModalTableNumber: '',
        endSubmitting: false,

        init() {
            if (window.location.search.includes('start_session=1')) {
                this.showStartModal = true;
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
            this.notes = '';
            this.showStartModal = true;
        },

        async submitStartSession() {
            this.startSubmitting = true;
            try {
                const result = await apiPost('/api/tables/' + this.modalTableId + '/start', {
                    customer_id: this.selectedCustomer ? this.selectedCustomer.id : null,
                    players_count: parseInt(this.playersCount),
                    rate_type: this.rateType,
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
            this.showEndModal = true;
        },

        async confirmEndSession() {
            this.endSubmitting = true;
            try {
                const result = await apiPost('/api/sessions/' + this.endSessionId + '/end');
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

// Global helper for inline onclick handlers
function openStartModal(id, number, rate) {
    const comp = Alpine.$data(document.querySelector('[x-data]'));
    comp.openStartModal(id, number, rate);
}
function endSession(sessionId, tableId) {
    const comp = Alpine.$data(document.querySelector('[x-data]'));
    comp.endSession(sessionId, tableId);
}

// ── Live Timer System ──────────────────────────────────────────
function tickTimers() {
    document.querySelectorAll('.timer-display').forEach(el => {
        const start = el.dataset.start;
        const paused = parseInt(el.dataset.paused || '0', 10);
        const status = el.dataset.status;
        if (!start || status !== 'active') return;

        const now = Math.floor(Date.now() / 1000);
        const started = Math.floor(new Date(start + 'Z').getTime() / 1000);
        let elapsed = now - started - paused;
        if (elapsed < 0) elapsed = 0;
        el.textContent = formatDuration(elapsed);
    });

    document.querySelectorAll('.session-timer').forEach(el => {
        const start = el.dataset.startTime;
        const paused = parseInt(el.dataset.pausedTotal || '0', 10);
        const status = el.dataset.sessionStatus;
        if (!start || status !== 'active') return;

        const now = Math.floor(Date.now() / 1000);
        const started = Math.floor(new Date(start + 'Z').getTime() / 1000);
        let elapsed = now - started - paused;
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
}

setInterval(tickTimers, 1000);
</script>
