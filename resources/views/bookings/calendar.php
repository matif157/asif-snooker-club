<?php
/** @var string $month, $display, $prevMonth, $nextMonth */
/** @var array $cells, $byDay */
/** @var int $daysInMonth */
/** @var string $today */
?>
<div class="space-y-6 fade-in">

    <!-- Page Title -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Booking Calendar</h1>
            <p class="text-sm text-slate-400 mt-1">Month view of all table reservations</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= e(url('/bookings/calendar?month=' . $prevMonth)) ?>" class="btn-secondary !px-3 !py-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <span class="text-sm font-semibold text-white px-3 min-w-[130px] text-center"><?= e($display) ?></span>
            <a href="<?= e(url('/bookings/calendar?month=' . $nextMonth)) ?>" class="btn-secondary !px-3 !py-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>

    <!-- Month Grid -->
    <div class="card p-4 sm:p-6">
        <div class="grid grid-cols-7 gap-1.5 sm:gap-2 mb-2">
            <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d): ?>
                <div class="text-center text-[11px] font-semibold text-slate-500 uppercase tracking-wider py-2"><?= $d ?></div>
            <?php endforeach; ?>
        </div>
        <div class="grid grid-cols-7 gap-1.5 sm:gap-2">
            <?php foreach ($cells as $day): ?>
                <?php
                    $inMonth = $day >= 1 && $day <= $daysInMonth;
                    $dateStr = $inMonth ? sprintf('%s-%02d', $month, $day) : '';
                    $dayBookings = $inMonth ? ($byDay[$dateStr] ?? []) : [];
                    $isToday = $dateStr === $today;
                    $dayTotal = count($dayBookings);
                ?>
                <div class="rounded-xl border min-h-[92px] sm:min-h-[110px] p-1.5 sm:p-2 transition
                            <?= $inMonth
                                ? ($isToday ? 'border-emerald-500/50 bg-emerald-500/[0.06]' : 'border-white/[0.06] bg-white/[0.02] hover:bg-white/[0.05]')
                                : 'border-transparent opacity-30' ?>">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-sm font-semibold <?= $isToday ? 'text-emerald-400' : ($inMonth ? 'text-slate-300' : '') ?>"><?= $inMonth ? $day : '' ?></span>
                        <?php if ($inMonth && $dayTotal > 0): ?>
                            <span class="badge badge-violet !text-[10px] !px-1.5 !py-0.5"><?= $dayTotal ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($inMonth): ?>
                        <?php foreach (array_slice($dayBookings, 0, 2) as $bk): ?>
                            <a href="<?= e(url('/bookings?date=' . $dateStr)) ?>"
                               class="block truncate text-[10px] sm:text-[11px] mb-0.5 py-0.5 px-1 rounded-md
                                      <?= $bk['status'] === 'active' ? 'bg-emerald-500/15 text-emerald-400' : 'bg-violet-500/10 text-violet-400' ?>
                                      hover:bg-white/10 transition">
                                #<?= (int) $bk['table_number'] ?> · <?= e($bk['customer_linked_name'] ?? $bk['customer_name'] ?? '—') ?>
                            </a>
                        <?php endforeach; ?>
                        <?php if ($dayTotal > 2): ?>
                            <a href="<?= e(url('/bookings?date=' . $dateStr)) ?>" class="block text-[10px] text-slate-500 hover:text-slate-300 px-1">+<?= $dayTotal - 2 ?> more</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400">
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-violet-500/30"></span> Active bookings</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-emerald-500/30"></span> Today</span>
        <span>Click a day to view its bookings</span>
    </div>
</div>