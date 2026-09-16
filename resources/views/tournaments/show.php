<?php
/** @var \App\Models\Tournament $tournament */
/** @var array $players, $bracket, $tables, $customers */
/** @var int $registeredCount */

$statusBadge = [
    'draft'       => ['bg-slate-500/15 text-slate-300', 'Draft'],
    'open'        => ['bg-sky-500/15 text-sky-300', 'Open'],
    'in_progress' => ['bg-amber-500/15 text-amber-300', 'In Progress'],
    'completed'   => ['bg-emerald-500/15 text-emerald-300', 'Completed'],
    'cancelled'   => ['bg-rose-500/15 text-rose-300', 'Cancelled'],
];
[$badge, $badgeLabel] = $statusBadge[$tournament->status] ?? $statusBadge['draft'];
$canEdit = user_can('tournaments.manage');
$totPlayers = count($players);
$activeCounts = ['registered' => 0, 'active' => 0, 'withdrew' => 0, 'champion' => 0];
foreach ($players as $p) {
    $activeCounts[$p['status']] = ($activeCounts[$p['status']] ?? 0) + 1;
}
?>

<div class="space-y-6 fade-in" x-data="{ scoreOpen: false, scoreHome: '', scoreAway: '', scoreTid: 0 }">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight"><?= e($tournament->name) ?></h1>
            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full <?= $badge ?>"><?= $badgeLabel ?></span>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= e(url('/tournaments')) ?>" class="btn-secondary">← Tournaments</a>
            <?php if ($canEdit && in_array($tournament->status, ['draft', 'open'], true)): ?>
                <form method="POST" action="<?= e(url('/tournaments/' . (int) $tournament->id . '/status')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="in_progress">
                    <button type="submit" class="btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Start Tournament
                    </button>
                </form>
            <?php endif; ?>
            <?php if ($canEdit && $tournament->status === 'in_progress'): ?>
                <form method="POST" action="<?= e(url('/tournaments/' . (int) $tournament->id . '/status')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="completed">
                    <button type="submit" class="btn-secondary">Mark Completed</button>
                </form>
            <?php endif; ?>
            <?php if ($canEdit && !in_array($tournament->status, ['completed', 'cancelled'], true)): ?>
            <form method="POST" action="<?= e(url('/tournaments/' . (int) $tournament->id . '/delete')) ?>"
                  onsubmit="return confirm('Delete this tournament and all its matches?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn-danger">Delete</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($tournament->prize_details || $tournament->entry_fee > 0 || $tournament->best_of): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Entry Fee</p>
            <p class="text-2xl font-bold text-white mt-2">Rs <?= number_format((float) $tournament->entry_fee) ?></p>
        </div>
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Best Of</p>
            <p class="text-2xl font-bold text-white mt-2"><?= (int) $tournament->best_of ?> frames</p>
        </div>
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Players</p>
            <p class="text-2xl font-bold text-white mt-2"><?= $registeredCount ?><span class="text-sm text-slate-500 font-medium">/<?= $totPlayers ?></span></p>
        </div>
        <?php if ($tournament->champion_id): ?>
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Champion</p>
            <?php $champ = null; foreach ($players as $p) { if ((int) $p['id'] === (int) $tournament->champion_id) { $champ = $p['name']; break; } } ?>
            <p class="text-2xl font-bold text-emerald-400 mt-2"><?= e($champ ?? '—') ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($tournament->prize_details): ?>
    <div class="card px-5 py-4 flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm text-slate-300"><?= e($tournament->prize_details) ?></p>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Players -->
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Players (<?= $registeredCount ?>)</h2>
                <?php if ($canEdit && in_array($tournament->status, ['draft', 'open'], true)): ?>
                <form method="POST" action="<?= e(url('/tournaments/' . (int) $tournament->id . '/bracket')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-secondary !py-1.5 !px-3 text-xs">Generate Bracket</button>
                </form>
                <?php endif; ?>
            </div>

            <ol class="space-y-1.5 mb-5">
                <?php foreach ($players as $i => $p): ?>
                    <li class="flex items-center justify-between gap-2 rounded-lg px-3 py-2 <?= $p['status'] === 'withdrew' ? 'bg-ink-800/40 opacity-50' : 'bg-ink-800/60' ?>">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-6 text-center text-xs font-bold <?= ($i % 2 === 0) ? 'text-emerald-400' : 'text-sky-400' ?>">#<?= (int) $p['seed'] ?></span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-white truncate">
                                    <?= e($p['name']) ?>
                                    <?php if ($p['status'] === 'champion'): ?><span class="ml-1 text-amber-400">🏆</span><?php endif; ?>
                                </p>
                                <?php if ($p['phone']): ?><p class="text-xs text-slate-500"><?= e($p['phone']) ?></p><?php endif; ?>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <?php if ($p['status'] === 'withdrew'): ?>
                                <span class="text-[10px] uppercase text-rose-400 font-semibold">Withdrew</span>
                            <?php elseif ($p['status'] === 'active'): ?>
                                <span class="text-[10px] uppercase text-emerald-400 font-semibold">Active</span>
                            <?php elseif ($p['status'] === 'champion'): ?>
                                <span class="text-[10px] uppercase text-amber-400 font-semibold">Champion</span>
                            <?php elseif ($canEdit && in_array($tournament->status, ['draft', 'open'], true)): ?>
                                <form method="POST" action="<?= e(url('/tournaments/' . (int) $tournament->id . '/players/' . (int) $p['id'] . '/withdraw')) ?>"
                                      onsubmit="return confirm('Withdraw <?= e(addslashes($p['name'])) ?> from the tournament?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-xs text-slate-500 hover:text-rose-400 transition">Withdraw</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
                <?php if ($players === []): ?>
                    <li class="text-sm text-slate-500 py-6 text-center">No players registered yet.</li>
                <?php endif; ?>
            </ol>

            <?php if ($canEdit && in_array($tournament->status, ['draft', 'open'], true)): ?>
            <form method="POST" action="<?= e(url('/tournaments/' . (int) $tournament->id . '/players')) ?>" class="border-t border-white/5 pt-4 space-y-3">
                <?= csrf_field() ?>
                <p class="text-xs font-medium text-slate-400">Register a player</p>
                <div>
                    <label class="block text-[11px] text-slate-500 mb-1">Registered customer</label>
                    <select name="customer_id" class="input" id="customer-select">
                        <option value="0">— Walk-in / guest —</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" data-name="<?= e($c['name']) ?>" data-phone="<?= e($c['phone'] ?? '') ?>">
                                <?= e($c['name']) ?> · <?= e($c['phone'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] text-slate-500 mb-1">Name (if walk-in)</label>
                        <input type="text" name="name" placeholder="Player name" class="input" id="player-name">
                    </div>
                    <div>
                        <label class="block text-[11px] text-slate-500 mb-1">Phone</label>
                        <input type="text" name="phone" placeholder="03XXXXXXXXX" class="input" id="player-phone">
                    </div>
                </div>
                <button type="submit" class="btn-primary w-full">Add Player</button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Bracket -->
        <div class="lg:col-span-2 card p-5 overflow-x-auto">
            <h2 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Bracket</h2>

            <?php if ($bracket === []): ?>
                <div class="text-center py-14">
                    <p class="text-slate-400 font-semibold">No bracket yet</p>
                    <p class="text-sm text-slate-500 mt-1">Register at least 2 players, then generate the bracket.</p>
                    <?php if ($canEdit && in_array($tournament->status, ['draft', 'open'], true)): ?>
                    <form method="POST" action="<?= e(url('/tournaments/' . (int) $tournament->id . '/bracket')) ?>" class="mt-4">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-primary">Generate Bracket</button>
                    </form>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="flex items-stretch gap-4 min-w-max">
                    <?php
                    $roundNames = [1 => 'First Round'];
                    foreach ($bracket as $round => $matches) {
                        $roundNames[$round] = $round === 1 ? 'First Round' : ($round === array_key_last($bracket) ? 'Final' : 'Round ' . $round);
                    }
                    ?>
                    <?php foreach ($bracket as $round => $matches): ?>
                        <?php $isFinal = $round === array_key_last($bracket); $isLast = $round + 1 > array_key_last($bracket); ?>
                        <div class="flex flex-col gap-6 min-w-[190px] <?= $round > 1 ? 'justify-center' : '' ?>">
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-500"><?= e($roundNames[$round]) ?></p>
                            <?php foreach ($matches as $m): ?>
                                <?php
                                $homeName = $m['home_name'] ?? '';
                                $awayName = $m['away_name'] ?? '';
                                $homeWin  = $m['winner_id'] !== null && (int) $m['winner_id'] === (int) $m['player_home_id'];
                                $awayWin  = $m['winner_id'] !== null && (int) $m['winner_id'] === (int) $m['player_away_id'];
                                $isBye    = ($m['player_home_id'] === null) || ($m['player_away_id'] === null);
                                $done     = $m['status'] === 'completed';
                                ?>
                                <div class="rounded-xl border border-white/10 bg-ink-800/50 p-3 <?= $isFinal ? 'ring-1 ring-amber-500/30' : '' ?> <?= $done && $m['winner_id'] !== null ? 'border-emerald-500/30' : '' ?>">
                                    <div class="text-[9px] uppercase tracking-wider text-slate-500 mb-2 flex justify-between">
                                        <span>#<?= (int) $m['match_no'] ?></span>
                                        <span>Table <?= $m['table_number'] ? e('#' . $m['table_number']) : '—' ?></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2 py-1 rounded-lg px-2 <?= $homeWin ? 'bg-emerald-500/15' : '' ?>">
                                        <span class="text-sm truncate <?= $homeName === '' ? 'text-slate-600 italic' : 'text-slate-200' ?>"><?= e($homeName !== '' ? $homeName : 'TBD') ?></span>
                                        <span class="text-xs font-bold <?= $homeWin ? 'text-emerald-400' : 'text-slate-400' ?>"><?= (int) $m['score_home'] ?></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2 py-1 rounded-lg px-2 <?= $awayWin ? 'bg-emerald-500/15' : '' ?>">
                                        <span class="text-sm truncate <?= $awayName === '' ? 'text-slate-600 italic' : 'text-slate-200' ?>"><?= e($awayName !== '' ? $awayName : 'TBD') ?></span>
                                        <span class="text-xs font-bold <?= $awayWin ? 'text-emerald-400' : 'text-slate-400' ?>"><?= (int) $m['score_away'] ?></span>
                                    </div>
                                    <?php if ($canEdit && $tournament->status !== 'completed' && $tournament->status !== 'cancelled' && !$done && $homeName !== '' && $awayName !== ''): ?>
                                        <button type="button" x-on:click="scoreTid=<?= (int) $m['id'] ?>; scoreHome='<?= e(addslashes($homeName)) ?>'; scoreAway='<?= e(addslashes($awayName)) ?>'; scoreOpen=true"
                                                class="mt-2 w-full text-xs btn-secondary !py-1.5">Record Result</button>
                                    <?php elseif ($done && $m['winner_id'] !== null): ?>
                                        <div class="mt-2 text-[10px] text-emerald-400 font-semibold">✓ <?= e(($homeWin ? $homeName : $awayName)) ?> advances</div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Score modal -->
    <div id="scoreModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" x-show="scoreOpen" x-cloak x-transition.opacity>
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" x-on:click="scoreOpen=false"></div>
        <form method="POST" :action="'<?= e(url('/tournaments')) ?>/' + '<?= (int) $tournament->id ?>' + '/matches/' + scoreTid + '/score'"
              class="relative card p-6 w-full max-w-md space-y-4" @submit="scoreOpen=false">
            <?= csrf_field() ?>
            <h3 class="text-lg font-semibold text-white">Record Result</h3>
            <p class="text-sm text-slate-400" x-text="scoreHome + '  vs  ' + scoreAway"></p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5 text-emerald-400" x-text="scoreHome"></label>
                    <input type="number" name="score_home" min="0" max="255" value="0" class="input text-center text-lg font-bold" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5 text-sky-400" x-text="scoreAway"></label>
                    <input type="number" name="score_away" min="0" max="255" value="0" class="input text-center text-lg font-bold" required>
                </div>
            </div>
            <p class="text-[11px] text-slate-500">Frames won by each player. Scores must not be tied.</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Table</label>
                    <select name="table_id" class="input">
                        <option value="0">—</option>
                        <?php foreach ($tables as $t): ?>
                            <option value="<?= (int) $t['id'] ?>">#<?= e($t['number']) ?> — <?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Scheduled at</label>
                    <input type="datetime-local" name="scheduled_at" class="input">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Notes</label>
                <input type="text" name="notes" class="input" placeholder="Break of 147!">
            </div>
            <div class="flex items-center gap-3 pt-1">
                <button type="submit" class="btn-primary flex-1">Save Result</button>
                <button type="button" x-on:click="scoreOpen=false" class="btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('change', function (e) {
    const sel = document.getElementById('customer-select');
    if (!sel || e.target !== sel) return;
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value !== '0') {
        document.getElementById('player-name').placeholder = 'Auto: ' + opt.dataset.name;
        document.getElementById('player-phone').placeholder = 'Auto: ' + (opt.dataset.phone || '');
    } else {
        document.getElementById('player-name').placeholder = 'Player name';
        document.getElementById('player-phone').placeholder = '03XXXXXXXXX';
    }
});
</script>