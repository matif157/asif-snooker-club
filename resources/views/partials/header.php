<?php if (!is_authenticated()) return; ?>
<?php
$now    = new \DateTimeImmutable();
$today  = $now->format('l, M j, Y');
$clock  = $now->format('g:i A');
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