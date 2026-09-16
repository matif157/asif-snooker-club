<?php
/** @var bool $authed */
/** @var array|null $customer */
/** @var array $sessions, $payments, $bookings, $tables */
/** @var string $presetPhone */

function portalDuration(array $s): string
{
    $secs = $s['end_time']
        ? strtotime($s['end_time']) - strtotime($s['start_time']) - (int) ($s['paused_total_sec'] ?? 0)
        : 0;
    $secs = max(0, $secs);
    $h = intdiv($secs, 3600);
    $m = intdiv($secs % 3600, 60);
    return $h > 0 ? "{$h}h {$m}m" : "{$m} min";
}
?>
<div class="space-y-6 fade-in">

    <?php $flashErr = flash('error'); ?>
    <?php $flashOk  = flash('success'); ?>
    <?php if ($flashErr): ?>
        <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">
            <?= e($flashErr) ?>
        </div>
    <?php endif; ?>
    <?php if ($flashOk): ?>
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
            <?= e($flashOk) ?>
        </div>
    <?php endif; ?>

    <?php if (!$authed): ?>

        <!-- Logged out -->
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-white tracking-tight">Welcome to the Member Portal</h1>
            <p class="text-sm text-slate-400 mt-2">Check your balance, view your sessions and book a table — all from your phone.</p>
        </div>

        <form method="POST" action="<?= e(url('/portal/login')) ?>" class="card p-6 space-y-4">
            <?= csrf_field() ?>
            <div>
                <label for="phone" class="block text-xs font-medium text-slate-400 mb-1.5">Phone Number</label>
                <input type="tel" id="phone" name="phone" inputmode="numeric" required value="<?= e($presetPhone) ?>"
                       placeholder="e.g. 0300 1234567"
                       class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
            </div>
            <div>
                <label for="pin" class="block text-xs font-medium text-slate-400 mb-1.5">4-digit PIN</label>
                <input type="password" id="pin" name="pin" inputmode="numeric" maxlength="4" required
                       placeholder="••••"
                       class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
            </div>
            <button type="submit" class="btn-primary w-full justify-center">Sign in</button>
            <p class="text-xs text-slate-500 text-center">
                Don't have a PIN? Ask the club desk — they'll set one for you (usually within minutes).
            </p>
        </form>

        <div class="card p-4 text-xs text-slate-500 flex items-start gap-2.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>
                Demo members can sign in with <span class="text-slate-300 font-semibold">0300 1234567</span>
                and PIN <span class="text-slate-300 font-semibold">1234</span> (Ali Raza).
            </span>
        </div>

    <?php else: ?>

        <!-- Logged in -->
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-400">Assalam o Alaikum,</p>
                <h1 class="text-2xl font-bold text-white tracking-tight"><?= e($customer['name']) ?></h1>
            </div>
            <form method="POST" action="<?= e(url('/portal/logout')) ?>" class="inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn-secondary !px-3 !py-2 text-xs">Sign out</button>
            </form>
        </div>

        <!-- Balance -->
        <?php $balance = (float) ($customer['outstanding_balance'] ?? 0); ?>
        <div class="card p-5 flex items-center justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Your outstanding balance</p>
                <p class="text-2xl font-black mt-1 <?= $balance > 0 ? 'text-rose-400' : 'text-emerald-400' ?>">Rs <?= number_format($balance) ?></p>
                <p class="text-[11px] text-slate-500 mt-1">
                    <?= $balance > 0 ? 'Please settle at the club or via JazzCash (ask the desk for the number).' : 'All clear — no pending payments.' ?>
                </p>
            </div>
            <div class="text-right shrink-0">
                <p class="text-3xl"><?= (int) ($customer['total_visits'] ?? 0) ?></p>
                <p class="text-[11px] uppercase tracking-wider text-slate-500">visits</p>
            </div>
        </div>

        <!-- Book a table -->
        <div id="book" class="card p-6">
            <h2 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Book a Table</h2>
            <?php if (empty($tables)): ?>
                <p class="text-sm text-slate-500">No tables are available to book right now — call the club desk.</p>
            <?php else: ?>
                <form method="POST" action="<?= e(url('/portal/bookings')) ?>" class="space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Table *</label>
                        <select name="table_id" required
                                class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                            <option value="">Select a table</option>
                            <?php foreach ($tables as $t): ?>
                                <option value="<?= (int) $t['id'] ?>">
                                    Table #<?= (int) $t['number'] ?> — <?= e($t['name']) ?> · Rs <?= number_format((float) $t['hourly_rate']) ?>/hr
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Date *</label>
                            <input type="date" name="booking_date" min="<?= date('Y-m-d') ?>" required
                                   class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Players</label>
                            <input type="number" name="players_count" min="2" max="20" value="2"
                                   class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Start Time *</label>
                            <input type="time" name="start_time" required
                                   class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">End Time *</label>
                            <input type="time" name="end_time" required
                                   class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                        </div>
                    </div>
                    <button type="submit" class="btn-primary w-full justify-center">Request Booking</button>
                    <p class="text-xs text-slate-500 text-center">
                        Your request goes to the club desk — the booking is confirmed when they approve it.
                    </p>
                </form>
            <?php endif; ?>
        </div>

        <!-- My bookings -->
        <div class="card p-6">
            <h2 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">My Bookings</h2>
            <?php if (empty($bookings)): ?>
                <p class="text-sm text-slate-500">No active bookings — request one above.</p>
            <?php else: ?>
                <div class="space-y-2.5">
                    <?php foreach ($bookings as $b): ?>
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-white/[0.06] bg-white/[0.02] px-4 py-3 text-sm">
                            <div class="min-w-0">
                                <p class="font-medium text-white">Table #<?= (int) $b['table_number'] ?></p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    <?= date('D, M j', strtotime($b['booking_date'])) ?> ·
                                    <?= date('g:i A', strtotime($b['start_time'])) ?> — <?= date('g:i A', strtotime($b['end_time'])) ?>
                                </p>
                            </div>
                            <span class="badge badge-<?= \App\Models\Booking::STATUS_COLORS[$b['status']] ?? 'slate' ?> shrink-0">
                                <?= \App\Models\Booking::STATUS_LABELS[$b['status']] ?? $b['status'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent sessions -->
        <div class="card p-6">
            <h2 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Recent Sessions</h2>
            <?php if (empty($sessions)): ?>
                <p class="text-sm text-slate-500">No sessions recorded yet.</p>
            <?php else: ?>
                <div class="space-y-2.5">
                    <?php foreach ($sessions as $s): ?>
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-white/[0.06] bg-white/[0.02] px-4 py-3 text-sm">
                            <div class="min-w-0">
                                <p class="font-medium text-white">Table #<?= (int) $s['table_number'] ?></p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    <?= date('D, M j · g:i A', strtotime($s['start_time'])) ?> — <?= portalDuration($s) ?>
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-semibold text-white">Rs <?= number_format((float) $s['amount']) ?></p>
                                <span class="badge badge-<?= match($s['payment_status']) {
                                    'paid'    => 'emerald',
                                    'partial' => 'amber',
                                    default   => 'rose',
                                } ?>"><?= e($s['payment_status']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent payments -->
        <div class="card p-6">
            <h2 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Payment History</h2>
            <?php if (empty($payments)): ?>
                <p class="text-sm text-slate-500">No payments yet — your first one will appear here.</p>
            <?php else: ?>
                <div class="space-y-2.5">
                    <?php foreach ($payments as $p): ?>
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-white/[0.06] bg-white/[0.02] px-4 py-3 text-sm">
                            <div>
                                <p class="font-medium text-white capitalize"><?= e(str_replace('_', ' ', $p['method'])) ?></p>
                                <p class="text-xs text-slate-500 mt-0.5"><?= date('D, M j · g:i A', strtotime($p['paid_at'])) ?></p>
                            </div>
                            <p class="font-semibold text-emerald-400 shrink-0">Rs <?= number_format((float) $p['amount']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>