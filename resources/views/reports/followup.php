<?php
/** @var array $outstanding, $noShows */
/** @var string $reminderTemplate, $noShowTemplate */
?>
<div class="space-y-6 fade-in">

    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Follow-up &amp; Recovery</h1>
        <p class="text-sm text-slate-400 mt-1">Outstanding balances and missed bookings — with one-tap WhatsApp reminders.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Outstanding -->
        <div class="card p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-white">Outstanding Balances</h3>
                <span class="badge badge-amber"><?= count($outstanding) ?></span>
            </div>
            <?php if (empty($outstanding)): ?>
                <p class="text-sm text-slate-500 py-8 text-center">Nothing outstanding — all clear!</p>
            <?php else: ?>
                <div class="space-y-2.5 max-h-[520px] overflow-y-auto">
                    <?php foreach ($outstanding as $c): ?>
                        <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-3.5">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-white truncate"><?= e($c['name']) ?></p>
                                    <p class="text-xs text-slate-500">Due Rs <?= number_format($c['balance']) ?>
                                        <?php if ($c['lastVisit']): ?> · last visit <?= date('M j', strtotime($c['lastVisit'])) ?><?php endif; ?>
                                    </p>
                                    <p class="text-[11px] text-slate-500 mt-1 truncate"><?= e($c['message']) ?></p>
                                </div>
                                <a href="<?= e($c['waLink']) ?>" target="_blank" class="btn-primary !px-3 !py-1.5 text-xs shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 inline -mt-0.5 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21h.01c5.46 0 9.91-4.45 9.91-9.91C21.94 6.45 17.5 2 12.04 2zm5.83 14.13c-.25.7-1.45 1.33-2.02 1.42-.52.08-1.17.11-1.88-.12-.43-.14-.99-.32-1.7-.63-3-1.3-4.95-4.32-5.1-4.52-.15-.2-1.21-1.61-1.21-3.07 0-1.46.77-2.17 1.04-2.47.27-.3.59-.37.79-.37h.57c.18 0 .43-.07.67.51.25.6.85 2.07.92 2.22.08.15.13.33.03.53-.1.2-.15.32-.3.5-.15.18-.32.4-.45.53-.15.15-.31.31-.13.61.18.3.79 1.3 1.7 2.11 1.17 1.04 2.15 1.37 2.46 1.52.3.15.48.13.66-.08.18-.2.76-.88.96-1.19.2-.3.4-.25.67-.15.28.1 1.75.83 2.05.98.3.15.5.22.57.35.08.13.08.73-.17 1.42z"/></svg>
                                    Remind
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <p class="text-[11px] text-slate-500 mt-3">Template: <span class="italic"><?= e($reminderTemplate) ?></span></p>
        </div>

        <!-- No-shows -->
        <div class="card p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-white">Recent Missed Bookings</h3>
                <span class="badge badge-rose"><?= count($noShows) ?></span>
            </div>
            <?php if (empty($noShows)): ?>
                <p class="text-sm text-slate-500 py-8 text-center">No no-shows or cancellations in the last 7 days.</p>
            <?php else: ?>
                <div class="space-y-2.5 max-h-[520px] overflow-y-auto">
                    <?php foreach ($noShows as $n): ?>
                        <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-3.5">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-white truncate"><?= e($n['name']) ?></p>
                                    <p class="text-xs text-slate-500">
                                        Table #<?= (int) $n['table'] ?> · <?= e(date('D, M j', strtotime($n['date']))) ?>
                                        <span class="badge badge-slate !text-[10px] ml-1"><?= e(ucfirst(str_replace('_', ' ', $n['status']))) ?></span>
                                    </p>
                                    <p class="text-[11px] text-slate-500 mt-1 truncate"><?= e($n['message']) ?></p>
                                </div>
                                <?php if (!empty($n['phone'])): ?>
                                    <a href="<?= e($n['waLink']) ?>" target="_blank" class="btn-secondary !px-3 !py-1.5 text-xs shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 inline -mt-0.5 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21h.01c5.46 0 9.91-4.45 9.91-9.91C21.94 6.45 17.5 2 12.04 2zm5.83 14.13c-.25.7-1.45 1.33-2.02 1.42-.52.08-1.17.11-1.88-.12-.43-.14-.99-.32-1.7-.63-3-1.3-4.95-4.32-5.1-4.52-.15-.2-1.21-1.61-1.21-3.07 0-1.46.77-2.17 1.04-2.47.27-.3.59-.37.79-.37h.57c.18 0 .43-.07.67.51.25.6.85 2.07.92 2.22.08.15.13.33.03.53-.1.2-.15.32-.3.5-.15.18-.32.4-.45.53-.15.15-.31.31-.13.61.18.3.79 1.3 1.7 2.11 1.17 1.04 2.15 1.37 2.46 1.52.3.15.48.13.66-.08.18-.2.76-.88.96-1.19.2-.3.4-.25.67-.15.28.1 1.75.83 2.05.98.3.15.5.22.57.35.08.13.08.73-.17 1.42z"/></svg>
                                        Contact
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <p class="text-[11px] text-slate-500 mt-3">Template: <span class="italic"><?= e($noShowTemplate) ?></span></p>
        </div>
    </div>
</div>