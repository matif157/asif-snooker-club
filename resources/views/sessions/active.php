<?php
/** @var array $sessions — from ClubSession::activeSessions() with joined fields */

$totalSessions = count($sessions);
$totalEstRevenue = 0;
foreach ($sessions as $s) {
    if ($s['status'] === 'active') {
        $elapsed = time() - strtotime($s['start_time']);
        $elapsed -= (int) ($s['paused_total_sec'] ?? 0);
        $elapsed = max(0, $elapsed);
        $rate = (float) ($s['rate'] ?? $s['table_rate'] ?? 300);
        $est = ceil(round(($elapsed / 3600) * $rate) / 10) * 10;
        $minCharge = (float) ($s['table_min_charge'] ?? 100);
        if ($est < $minCharge && $elapsed > 0) $est = $minCharge;
        $totalEstRevenue += $est;
    } else {
        $totalEstRevenue += (float) ($s['amount'] ?? 0);
    }
}
?>

<div class="space-y-6 fade-in" x-data="activeSessionsPage()">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Active Sessions</h1>
            <p class="text-sm text-slate-400 mt-1">
                Live timers for all running sessions &middot;
                <span class="text-emerald-400 font-semibold"><?= $totalSessions ?> active</span>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Live timers
            </div>
            <a href="/tables" class="btn-secondary text-xs !py-2 !px-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Tables View
            </a>
        </div>
    </div>

    <!-- Summary Strip -->
    <div class="grid grid-cols-3 gap-4">
        <div class="stat-card flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-sky-500/15 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Sessions</p>
                <p class="text-xl font-bold text-white"><?= $totalSessions ?></p>
            </div>
        </div>
        <div class="stat-card flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/15 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Est. Revenue</p>
                <p class="text-xl font-bold text-white">Rs <?= number_format($totalEstRevenue) ?></p>
            </div>
        </div>
        <div class="stat-card flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-amber-500/15 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Tables Busy</p>
                <p class="text-xl font-bold text-white"><?= $totalSessions ?></p>
            </div>
        </div>
    </div>

    <?php if (empty($sessions)): ?>
        <!-- Empty State -->
        <div class="card p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-ink-750 flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-base font-semibold text-white mb-1">No Active Sessions</h3>
            <p class="text-sm text-slate-400 mb-5">All tables are free. Start a session from the table view.</p>
            <a href="/tables" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Go to Tables
            </a>
        </div>
    <?php else: ?>
        <!-- Sessions Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($sessions as $sess): ?>
                <?php
                    $elapsedInit = 0;
                    if ($sess['status'] === 'active') {
                        $elapsedInit = time() - strtotime($sess['start_time']);
                        $elapsedInit -= (int) ($sess['paused_total_sec'] ?? 0);
                        $elapsedInit = max(0, $elapsedInit);
                    }
                    $estInit = 0;
                    if ($elapsedInit > 0) {
                        $rate = (float) ($sess['rate'] ?? $sess['table_rate'] ?? 300);
                        $estInit = round(($elapsedInit / 3600) * $rate);
                        $minCharge = (float) ($sess['table_min_charge'] ?? 100);
                        if ($estInit < $minCharge) $estInit = $minCharge;
                        $estInit = ceil($estInit / 10) * 10;
                    }
                ?>
                <div class="card p-5 relative overflow-hidden group transition-all hover:border-white/[0.1]"
                     data-session-id="<?= (int) $sess['id'] ?>"
                     data-table-id="<?= (int) $sess['table_id'] ?>">

                    <!-- Status indicator bar -->
                    <div class="absolute top-0 left-0 w-full h-0.5 <?= $sess['status'] === 'active' ? 'bg-emerald-400' : 'bg-amber-400' ?>"></div>

                    <!-- Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl <?= $sess['status'] === 'active' ? 'bg-emerald-500/15' : 'bg-amber-500/15' ?> flex items-center justify-center">
                                <span class="text-sm font-bold <?= $sess['status'] === 'active' ? 'text-emerald-400' : 'text-amber-400' ?>">#<?= e($sess['table_number']) ?></span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white"><?= e($sess['table_name']) ?></p>
                                <p class="text-[11px] text-slate-500"><?= e(ucfirst($sess['rate_type'] ?? 'hourly')) ?> &middot; Rs <?= number_format((float) ($sess['rate'] ?? 0)) ?>/hr</p>
                            </div>
                        </div>
                        <span class="badge badge-<?= $sess['status'] === 'active' ? 'emerald' : 'amber' ?>">
                            <?= ucfirst($sess['status']) ?>
                        </span>
                    </div>

                    <!-- Customer -->
                    <div class="mb-4">
                        <?php if (!empty($sess['customer_name'])): ?>
                            <p class="text-sm text-white font-medium"><?= e($sess['customer_name']) ?></p>
                            <?php if (!empty($sess['customer_phone'])): ?>
                                <p class="text-[11px] text-slate-500 mt-0.5"><?= e($sess['customer_phone']) ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-sm text-slate-500 italic">Walk-in customer</p>
                        <?php endif; ?>
                    </div>

                    <!-- Timer -->
                    <div class="bg-ink-850 rounded-xl p-4 mb-4 border border-white/[0.04]">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Elapsed Time</span>
                            <div class="flex items-center gap-1.5">
                                <?php if ($sess['status'] === 'active'): ?>
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <?php else: ?>
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                <?php endif; ?>
                                <span class="text-[11px] text-slate-500"><?= $sess['players_count'] ?? 1 ?> player<?= ($sess['players_count'] ?? 1) > 1 ? 's' : '' ?></span>
                            </div>
                        </div>
                        <p class="text-2xl font-mono font-bold <?= $sess['status'] === 'active' ? 'text-emerald-400' : 'text-amber-400' ?> session-timer"
                           data-start-time="<?= (int) strtotime($sess['start_time']) ?>"
                           data-paused-total="<?= (int) ($sess['paused_total_sec'] ?? 0) ?>"
                           data-session-status="<?= e($sess['status']) ?>">
                            <?= format_duration($elapsedInit) ?>
                        </p>
                    </div>

                    <!-- Amount & Actions -->
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] text-slate-500 uppercase tracking-wider">Est. Amount</p>
                            <p class="text-lg font-bold text-white est-amount"
                               data-rate="<?= (float) ($sess['rate'] ?? $sess['table_rate'] ?? 300) ?>"
                               data-min-charge="<?= (float) ($sess['table_min_charge'] ?? 100) ?>">
                                Rs <?= number_format($estInit) ?>
                            </p>
                            <?php if ((float) ($sess['extra_charges'] ?? 0) > 0): ?>
                                <p class="text-[11px] text-amber-400 font-medium est-extras">Extras: Rs <?= number_format((float) $sess['extra_charges']) ?></p>
                            <?php else: ?>
                                <p class="text-[11px] text-slate-600 est-extras hidden"></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <div class="flex items-center gap-2">
                                <a href="/payments?session_id=<?= (int) $sess['id'] ?>" class="btn-primary !py-2 !px-4 !text-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    Pay
                                </a>
                                <?php if (user_can('sessions.manage')): ?>
                                    <button class="btn-secondary !py-2 !px-4 !text-xs"
                                            @click="openChargeModal(<?= (int) $sess['id'] ?>, '<?= e($sess['table_number']) ?>', <?= (float) ($sess['extra_charges'] ?? 0) ?>)">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        Extra
                                    </button>
                                <?php endif; ?>
                            </div>
                            <button class="btn-danger !py-2 !px-4 !text-xs"
                                    @click="openEndModal(<?= (int) $sess['id'] ?>, <?= (int) $sess['table_id'] ?>, '<?= e($sess['table_number']) ?>')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                                End
                            </button>
                        </div>
                    </div>

                    <!-- Staff -->
                    <?php if (!empty($sess['staff_name'])): ?>
                        <div class="mt-3 pt-3 border-t border-white/[0.04] flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span class="text-[11px] text-slate-600">Started by <?= e($sess['staff_name']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ── End Session Modal ─────────────────────────────────────── -->
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
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             @click.stop>

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/[0.06]">
                <h3 class="text-base font-semibold text-white">End Session</h3>
                <button @click="showEndModal = false" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 flex items-center justify-center transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-14 h-14 rounded-full bg-rose-500/15 flex items-center justify-center mx-auto mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                    </div>
                    <p class="text-sm text-slate-400">End session on Table <span class="text-white font-semibold" x-text="'#' + endTableNumber"></span>?</p>
                    <p class="text-xs text-slate-500 mt-1">This will stop the timer and calculate the final amount.</p>
                </div>

                <!-- Payment summary preview -->
                <div class="bg-ink-850 rounded-xl p-4 mb-6 border border-white/[0.04]">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-400">Final amount will be computed automatically.</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">You can process payment after ending the session.</p>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="showEndModal = false" class="btn-secondary flex-1 justify-center">Cancel</button>
                    <button @click="confirmEndSession()"
                            class="btn-danger flex-1 justify-center"
                            :disabled="endSubmitting"
                            :class="{ 'opacity-50 cursor-not-allowed': endSubmitting }">
                        <svg x-show="endSubmitting" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="endSubmitting ? 'Ending...' : 'End Session'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Add Extra Charge Modal ─────────────────────────────── -->
    <div x-show="showChargeModal" x-cloak
         class="modal-overlay"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="showChargeModal = false"
         @keydown.escape.window="showChargeModal = false">

        <div class="modal-card max-w-md"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             @click.stop>

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/[0.06]">
                <h3 class="text-base font-semibold text-white">Add Extra Charge</h3>
                <button @click="showChargeModal = false" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 flex items-center justify-center transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <p class="text-sm text-slate-400">Add an extra billable item to Table <span class="text-white font-semibold" x-text="'#' + chargeTableNumber"></span> (e.g. refreshments, damage, chalk).</p>

                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Amount (Rs) *</label>
                    <input type="number" x-model="chargeAmount" step="0.01" min="0" placeholder="0.00"
                           class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Label</label>
                    <input type="text" x-model="chargeLabel" maxlength="120" placeholder="e.g. Refreshments"
                           class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button @click="showChargeModal = false" class="btn-secondary flex-1 justify-center">Cancel</button>
                    <button @click="submitExtraCharge()"
                            class="btn-primary flex-1 justify-center"
                            :disabled="chargeSubmitting"
                            :class="{ 'opacity-50 cursor-not-allowed': chargeSubmitting }">
                        <svg x-show="chargeSubmitting" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="chargeSubmitting ? 'Adding...' : 'Add Charge'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Alpine.js -->
<script defer src="/assets/vendor/alpine.min.js"></script>

<script>
function activeSessionsPage() {
    return {
        showEndModal: false,
        endSessionId: null,
        endTableId: null,
        endTableNumber: '',
        endSubmitting: false,

        showChargeModal: false,
        chargeSessionId: null,
        chargeTableNumber: '',
        chargeAmount: '',
        chargeLabel: '',
        chargeSubmitting: false,

        openEndModal(sessionId, tableId, tableNumber) {
            this.endSessionId = sessionId;
            this.endTableId = tableId;
            this.endTableNumber = tableNumber;
            this.showEndModal = true;
        },

        openChargeModal(sessionId, tableNumber, extras) {
            this.chargeSessionId = sessionId;
            this.chargeTableNumber = tableNumber;
            this.chargeAmount = '';
            this.chargeLabel = '';
            this.showChargeModal = true;
        },

        async submitExtraCharge() {
            const amount = parseFloat(this.chargeAmount);
            if (!amount || amount <= 0) { alert('Enter a valid charge amount.'); return; }
            this.chargeSubmitting = true;
            try {
                const result = await apiPost('/api/sessions/' + this.chargeSessionId + '/charge', {
                    amount: amount,
                    label: this.chargeLabel
                });
                if (result.success) {
                    const card = document.querySelector('[data-session-id="' + this.chargeSessionId + '"]');
                    if (card) {
                        const el = card.querySelector('.est-extras');
                        if (el) {
                            el.textContent = 'Extras: Rs ' + Number(result.data.extra_charges).toLocaleString();
                            el.classList.remove('hidden');
                        }
                    }
                    this.showChargeModal = false;
                    this.chargeAmount = '';
                    this.chargeLabel = '';
                } else {
                    alert(result.message || 'Failed to add charge');
                }
            } catch(e) {
                alert('Network error. Please try again.');
            }
            this.chargeSubmitting = false;
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

// ── Live Timer System ──────────────────────────────────────────
function tickSessionTimers() {
    document.querySelectorAll('.session-timer').forEach(el => {
        const startTime = el.dataset.startTime;
        const pausedTotal = parseInt(el.dataset.pausedTotal || '0', 10);
        const sessionStatus = el.dataset.sessionStatus;
        if (!startTime || sessionStatus !== 'active') return;

        const now = Math.floor(Date.now() / 1000);
        const started = parseInt(el.dataset.startTime, 10);
        let elapsed = now - started - pausedTotal;
        if (elapsed < 0) elapsed = 0;
        el.textContent = formatDuration(elapsed);

        // Update estimated amount in same card
        const card = el.closest('[data-session-id]');
        if (card) {
            const amtEl = card.querySelector('.est-amount');
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

setInterval(tickSessionTimers, 1000);
</script>
