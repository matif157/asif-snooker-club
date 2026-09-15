<?php if (!is_authenticated()) return; ?>
<?php
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$isActive = fn(string $path) => str_starts_with($uri, $path) || $uri === $path ? 'true' : 'false';
?>
<!-- Sidebar (always left) -->
<aside class="fixed inset-y-0 left-0 z-50 w-[240px] bg-ink-850/95 border-r border-white/[0.06] flex flex-col
              transform transition-transform duration-300 lg:translate-x-0
              open ? 'translate-x-0' : '-translate-x-full'"
     x-bind:class="mobileNav ? 'translate-x-0' : '-translate-x-full'">

    <!-- Brand -->
    <div class="px-5 pt-5 pb-4 border-b border-white/[0.06]">
        <a href="/" class="flex items-center gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-md shadow-emerald-500/20 flex items-center justify-center flex-shrink-0 ring-1 ring-white/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                    <circle cx="12" cy="12" r="9"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </div>
            <div>
                <div class="text-sm font-bold text-white tracking-tight">ASIF SNOOKER</div>
                <div class="text-[11px] text-slate-500 font-medium tracking-wide">CLUB MANAGEMENT</div>
            </div>
        </a>
    </div>

    <!-- Nav -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">

        <p class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-500/70">Main</p>

        <a href="/" class="nav-item <?php if ($uri === '/') echo 'active'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 13.5V5a2 2 0 012-2h6.5l1 1H20a2 2 0 012 2v9.5a2 2 0 01-2 2h-2a2 2 0 00-2 2H8a2 2 0 00-2-2H4a2 2 0 01-2-2z"/></svg>
            Dashboard
        </a>

        <a href="/tables" class="nav-item <?php if (str_starts_with($uri, '/tables')) echo 'active'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            Tables
        </a>

        <a href="/sessions" class="nav-item <?php if (str_starts_with($uri, '/sessions')) echo 'active'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Sessions
        </a>

        <a href="/bookings" class="nav-item <?php if (str_starts_with($uri, '/bookings')) echo 'active'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Bookings
        </a>

        <p class="px-3 mt-5 mb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-500/70">People & Money</p>

        <a href="/customers" class="nav-item <?php if (str_starts_with($uri, '/customers')) echo 'active'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M9 20H4v-2a3 3 0 015.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Customers
        </a>

        <a href="/payments" class="nav-item <?php if (str_starts_with($uri, '/payments')) echo 'active'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
            Payments
        </a>

        <a href="/expenses" class="nav-item <?php if (str_starts_with($uri, '/expenses')) echo 'active'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
            Expenses
        </a>

        <?php if (user_can('reports.view') || user_can('finance.view')): ?>
        <a href="/reports/daily" class="nav-item <?php if (str_starts_with($uri, '/reports/daily')) echo 'active'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Daily Closing
        </a>
        <a href="/reports/analytics" class="nav-item <?php if (str_starts_with($uri, '/reports/analytics')) echo 'active'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2zm3-8h2a1 1 0 110 2h-2a1 1 0 110-2z"/></svg>
            Analytics
        </a>
        <?php endif; ?>

        <?php if (user_can('settings.manage')): ?>
        <p class="px-3 mt-5 mb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-500/70">System</p>
        <a href="/settings" class="nav-item <?php if (str_starts_with($uri, '/settings')) echo 'active'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Settings
        </a>
        <?php endif; ?>

    </nav>

    <!-- User -->
    <div class="border-t border-white/[0.06] p-3">
        <div class="flex items-center gap-3 px-2 py-2">
            <div class="w-8 h-8 rounded-lg bg-emerald-600/30 flex items-center justify-center flex-shrink-0">
                <span class="text-emerald-400 text-xs font-bold">
                    <?= strtoupper(substr($user?->name ?? 'U', 0, 1)) ?>
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-200 truncate"><?= e($user?->name ?? 'User') ?></p>
                <p class="text-[11px] text-slate-500 uppercase tracking-wider"><?= e($user?->role ?? 'staff') ?></p>
            </div>
            <form method="POST" action="<?= e(url('/logout')) ?>" class="flex-shrink-0">
                <?= csrf_field() ?>
                <button type="submit" title="Sign out"
                        class="p-2 rounded-lg text-slate-500 hover:text-white hover:bg-white/5 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </div>
    </div>

</aside>