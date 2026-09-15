<?php
/** @var array|null $customer */
/** @var array $sessions, $payments */
/** @var string $phone */
/** @var bool $verified */
/** @var string|null $error */

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
<div class="text-center mb-8">
    <h1 class="text-2xl font-bold text-white tracking-tight">Welcome to the Member Portal</h1>
    <p class="text-sm text-slate-400 mt-2">Enter the phone number you registered with to view your balance, visits and payments.</p>
</div>

<!-- Lookup form -->
<form method="GET" action="<?= e(url('/portal')) ?>" class="card p-4 flex gap-3 mb-6">
    <input type="tel" name="phone" inputmode="numeric" required value="<?= e($phone) ?>"
           placeholder="e.g. 0300 1234567"
           class="flex-1 bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
    <button type="submit" class="btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m2.2-5.3a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/></svg>
        Look up
    </button>
</form>

<?php if (!empty($error)): ?>
    <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-300 mb-6">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<?php if ($customer !== null): ?>
    <!-- Profile -->
    <div class="card p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-lg font-bold text-white"><?= e($customer['name']) ?></p>
                <p class="text-xs text-slate-400 capitalize"><?= e($customer['category'] ?? 'regular') ?> member
                    · <?= (int) ($customer['total_visits'] ?? 0) ?> visits · <?= number_format((float) ($customer['total_hours'] ?? 0), 1) ?> hrs</p>
            </div>
            <?php $balance = (float) ($customer['outstanding_balance'] ?? 0); ?>
            <div class="text-right">
                <p class="text-[11px] uppercase tracking-wider text-slate-500">Outstanding</p>
                <p class="text-xl font-black <?= $balance > 0 ? 'text-rose-400' : 'text-emerald-400' ?>">Rs <?= number_format($balance) ?></p>
                <p class="text-[11px] text-slate-500"><?= $balance > 0 ? 'please settle at the club or via JazzCash' : 'all clear' ?></p>
            </div>
        </div>
    </div>

    <!-- Recent sessions -->
    <div class="card p-6 mb-6">
        <h2 class="text-sm font-semibold text-white mb-4">Recent Sessions</h2>
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
        <h2 class="text-sm font-semibold text-white mb-4">Payment History</h2>
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