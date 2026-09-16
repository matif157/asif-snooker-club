<?php
/** @var array $tournaments */
$statusBadge = [
    'draft'       => ['bg-slate-500/15 text-slate-300', 'Draft'],
    'open'        => ['bg-sky-500/15 text-sky-300', 'Open'],
    'in_progress' => ['bg-amber-500/15 text-amber-300', 'In Progress'],
    'completed'   => ['bg-emerald-500/15 text-emerald-300', 'Completed'],
    'cancelled'   => ['bg-rose-500/15 text-rose-300', 'Cancelled'],
];
?>

<div class="space-y-6 fade-in">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Tournaments</h1>
            <p class="text-sm text-slate-400 mt-1">Knockout tournaments, brackets &amp; results</p>
        </div>
        <?php if (user_can('tournaments.manage')): ?>
        <a href="<?= e(url('/tournaments/create')) ?>" class="btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Tournament
        </a>
        <?php endif; ?>
    </div>

    <?php if ($tournaments === []): ?>
        <div class="card py-16 text-center">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-emerald-500/10 flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9H4.5a2.5 2.5 0 010-5H6M18 9h1.5a2.5 2.5 0 000-5H18M4 22h16M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22m6-7.34V17c0 .55.47.98.97 1.21 1.18.54 1.53 2.03 1.53 3.79"/></svg>
            </div>
            <p class="text-slate-300 font-semibold">No tournaments yet</p>
            <p class="text-sm text-slate-500 mt-1">Create your first knockout tournament to get started.</p>
            <?php if (user_can('tournaments.manage')): ?>
            <a href="<?= e(url('/tournaments/create')) ?>" class="btn-primary mt-5">New Tournament</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <?php foreach ($tournaments as $t): ?>
                <?php [$badgeClasses, $badgeLabel] = $statusBadge[$t['status']] ?? $statusBadge['draft']; ?>
                <a href="<?= e(url('/tournaments/' . (int) $t['id'])) ?>"
                   class="group card p-5 transition hover:border-emerald-500/40 hover:shadow-lg hover:shadow-emerald-500/5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-white group-hover:text-emerald-400 transition"><?= e($t['name']) ?></h3>
                            <p class="text-xs text-slate-500 mt-1">
                                <?= $t['start_date'] ? date('M j', strtotime($t['start_date'])) . ($t['end_date'] ? ' – ' . date('M j, Y', strtotime($t['end_date'])) : '') : 'Dates TBD' ?>
                            </p>
                        </div>
                        <span class="shrink-0 text-[11px] font-semibold px-2.5 py-1 rounded-full <?= $badgeClasses ?>"><?= $badgeLabel ?></span>
                    </div>

                    <div class="grid grid-cols-3 gap-3 mt-5">
                        <div class="text-center rounded-xl bg-ink-800/60 py-3">
                            <p class="text-lg font-bold text-white"><?= (int) $t['player_count'] ?></p>
                            <p class="text-[10px] uppercase tracking-wider text-slate-500 mt-0.5">Players</p>
                        </div>
                        <div class="text-center rounded-xl bg-ink-800/60 py-3">
                            <p class="text-lg font-bold text-white"><?= (int) $t['matches_played'] ?><span class="text-xs text-slate-500 font-medium">/<?= (int) $t['total_matches'] ?></span></p>
                            <p class="text-[10px] uppercase tracking-wider text-slate-500 mt-0.5">Matches</p>
                        </div>
                        <div class="text-center rounded-xl bg-ink-800/60 py-3">
                            <p class="text-lg font-bold text-emerald-400"><?= number_format((float) $t['entry_fee'] * (int) $t['player_count']) ?></p>
                            <p class="text-[10px] uppercase tracking-wider text-slate-500 mt-0.5">Entry Pool</p>
                        </div>
                    </div>

                    <?php if ($t['champion_name']): ?>
                    <div class="mt-4 flex items-center gap-2 text-xs text-emerald-400 font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M5 16L3 14l2-3 2 3-2 2zm4 0l-2 2v2h10v-2l-2-2H9zm7-7l-2-1V5h-4v3l-2 1v2h8V9zm3 7l-2-2 2-3 2 3-2 2z"/></svg>
                        <?= e($t['champion_name']) ?> — Champion
                    </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>