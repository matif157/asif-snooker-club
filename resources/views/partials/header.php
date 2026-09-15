<?php if (!is_authenticated()) return; ?>
<?php
use App\Services\SettingsService;
use App\Core\Database;
$now    = new \DateTimeImmutable();
$today  = $now->format('l, M j, Y');
$clock  = $now->format('g:i A');
$clubPhone = SettingsService::clubPhone();
$clubCallDigits = preg_replace('/\D+/', '', $clubPhone);

// Alerts for the bell
$notifications = [];
$tableStats = Database::fetchOne("SELECT COUNT(*) AS total,
                                          SUM(status='occupied') AS occupied,
                                          SUM(status='reserved') AS reserved FROM tables");
$occupied = (int) ($tableStats['occupied'] ?? 0);
$reserved = (int) ($tableStats['reserved'] ?? 0);
$total    = (int) ($tableStats['total'] ?? 0);
if ($total > 0 && $occupied + $reserved >= $total) {
    $notifications[] = ['type' => 'alert', 'title' => 'All tables occupied', 'body' => "$occupied playing, $reserved reserved — no free tables left.", 'href' => '/tables'];
}

$pendingBookings = Database::query(
    "SELECT id, customer_name, table_id, start_time
     FROM bookings
     WHERE status IN ('requested','confirmed')
       AND booking_date = ?
     ORDER BY start_time ASC LIMIT 5",
    [date('Y-m-d')]
);
foreach ($pendingBookings as $b) {
    $notifications[] = ['type' => 'booking', 'title' => 'Booking: ' . ($b['customer_name'] ?? 'Guest'), 'body' => 'Table #' . $b['table_id'] . ' at ' . (new \DateTime('@' . strtotime(date('Y-m-d') . ' ' . $b['start_time'])))->format('g:i A'), 'href' => '/bookings'];
}

$unpaid = Database::fetchOne(
    "SELECT COUNT(*) AS c, COALESCE(SUM(amount), 0) AS total
     FROM sessions
     WHERE payment_status IN ('unpaid','partial') AND status = 'completed'"
);
if ((int) ($unpaid['c'] ?? 0) > 0) {
    $notifications[] = ['type' => 'money', 'title' => 'Unpaid sessions: Rs ' . number_format((float) $unpaid['total']), 'body' => (int) $unpaid['c'] . ' session(s) awaiting payment', 'href' => '/sessions?status=unpaid'];
}

$currentShift = 'Day';
$hour   = (int) $now->format('G');
if ($hour >= 18 || $hour < 6) {
    $currentShift = 'Night';
} elseif ($hour >= 6 && $hour < 12) {
    $currentShift = 'Morning';
}
?>
<header class="sticky top-0 z-30 border-b border-white/[0.06] bg-ink-900/80 backdrop-blur-xl">
    <div class="flex items-center gap-4 px-4 sm:px-6 lg:px-8 h-16">

        <!-- Mobile menu button -->
        <button @click="mobileNav = !mobileNav"
                class="lg:hidden p-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <!-- Title -->
        <div class="hidden sm:block">
            <h1 class="text-sm font-semibold text-white tracking-tight">ASIF SNOOKER CLUB</h1>
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    LIVE
                </span>
                <span>·</span>
                <span id="clock-live"><?= e($clock) ?></span>
                <span>·</span>
                <span><?= e($today) ?></span>
            </div>
        </div>

        <div class="flex-1"></div>

        <!-- Notifications bell -->
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button @click="open = !open" title="Notifications" class="relative p-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <?php if (count($notifications) > 0): ?>
                    <span class="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full bg-rose-500 text-[10px] font-bold text-white flex items-center justify-center">
                        <?= min(9, count($notifications)) ?>
                    </span>
                <?php endif; ?>
            </button>
            <div x-show="open" x-transition class="absolute right-0 z-50 mt-2 w-80 card p-3 max-h-96 overflow-y-auto" style="display:none">
                <div class="px-2 pt-1 pb-2 text-xs font-semibold uppercase tracking-widest text-slate-500">Alerts &amp; Notifications</div>
                <?php if (empty($notifications)): ?>
                    <div class="px-2 py-6 text-center text-sm text-slate-500">All clear — nothing to show.</div>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                        <a href="<?= e($n['href']) ?>" class="flex items-start gap-3 rounded-xl px-2 py-2.5 hover:bg-white/5 transition">
                            <span class="<?= $n['type'] === 'alert' ? 'bg-rose-500/20 text-rose-400' : ($n['type'] === 'money' ? 'bg-amber-500/20 text-amber-400' : 'bg-emerald-500/20 text-emerald-400') ?> w-8 h-8 rounded-lg flex items-center justify-center shrink-0">
                                <?php if ($n['type'] === 'booking'): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <?php else: ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <?php endif; ?>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-white truncate"><?= e($n['title']) ?></span>
                                <span class="block text-xs text-slate-500 truncate"><?= e($n['body']) ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Club call link (Pakistani number, configurable in settings) -->
        <a href="tel:+<?= e($clubCallDigits) ?>" title="Call club: <?= e($clubPhone) ?>"
           class="hidden md:flex items-center gap-2 text-slate-400 hover:text-emerald-400 transition text-xs font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            <?= e($clubPhone) ?>
        </a>

        <!-- Quick actions -->
        <a href="/tables?start_session=1"
           class="hidden sm:flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition shadow-md shadow-emerald-500/20 active:scale-95">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Session
        </a>

        <!-- Theme toggle -->
        <button id="themeToggle" title="Toggle theme"
                class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>

        <!-- User dropdown (desktop) -->
        <div class="hidden sm:flex items-center gap-3 text-right">
            <div class="w-8 h-8 rounded-lg bg-emerald-600/30 flex items-center justify-center">
                <span class="text-emerald-400 text-xs font-bold">
                    <?= strtoupper(substr(current_user()?->name ?? 'U', 0, 1)) ?>
                </span>
            </div>
        </div>

    </div>
</header>