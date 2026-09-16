<?php
/** @var array $errors */
/** @var array $old */
$clubName    = config('app.name', 'ASIF SNOOKER CLUB');
$clockIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($clubName) ?> — Sign In</title>
    <script>
        if (localStorage.getItem('theme') === 'light') { document.documentElement.classList.remove('dark'); }
    </script>
    <?= App\Services\ThemeService::cssVars() ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        ink: {900:'#0b0e14', 850:'#0f131c', 800:'#131824', 750:'#171d2b', 700:'#1b2233'},
                        emerald: {<?= App\Services\ThemeService::emeraldMapping() ?>},
                        gold: {400:'#fbbf24', 500:'#f59e0b'}
                    },
                    fontFamily: { sans: ['Inter','Manrope','system-ui','sans-serif'] }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { -webkit-font-smoothing: antialiased; }
        .bg-orbs::before, .bg-orbs::after {
            content:''; position:fixed; border-radius:9999px; filter:blur(120px); pointer-events:none; z-index:0;
        }
        .bg-orbs::before { width:480px; height:480px; top:-160px; right:-120px; background:rgba(16,185,129,0.14); }
        .bg-orbs::after  { width:420px; height:420px; bottom:-160px; left:-120px; background:rgba(245,158,11,0.07); }
        .glass { background:rgba(19,24,36,0.66); backdrop-filter:blur(14px); }
    </style>
</head>
<body class="bg-ink-900 text-slate-100 min-h-screen font-sans dark:bg-ink-900 bg-orbs">
    <div class="relative z-10 min-h-screen lg:grid lg:grid-cols-2">
        <!-- Left: brand + sign-in -->
        <div class="flex items-center justify-center p-4">
            <div class="w-full max-w-md">
        <!-- Brand -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-lg shadow-emerald-500/25 mb-4 mx-auto ring-1 ring-white/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                    <circle cx="12" cy="12" r="9"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white"><?= e($clubName) ?></h1>
            <p class="text-sm text-slate-400 mt-1.5">Club Management System</p>
        </div>

        <!-- Card -->
        <div class="glass rounded-2xl border border-white/10 shadow-2xl shadow-black/40 overflow-hidden">
            <div class="h-1 bg-gradient-to-r from-emerald-500 via-emerald-400 to-gold-500"></div>
            <div class="p-8">
                <h2 class="text-lg font-semibold text-white mb-1">Welcome back</h2>
                <p class="text-sm text-slate-400 mb-6">Sign in to continue to your dashboard</p>

                <?php if (!empty($errors['general'])): ?>
                    <div class="mb-5 rounded-xl bg-rose-500/10 border border-rose-500/30 px-4 py-3 flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <p class="text-sm text-rose-300"><?= e($errors['general']) ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= e(url('/login')) ?>" class="space-y-5" autocomplete="on">
                    <?= csrf_field() ?>
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-300 mb-1.5">Email address</label>
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>"
                                   class="w-full bg-ink-800/80 border border-white/10 rounded-xl pl-11 pr-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition"
                                   placeholder="you@example.com" required autofocus>
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-300 mb-1.5">Password</label>
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            <input type="password" id="password" name="password"
                                   class="w-full bg-ink-800/80 border border-white/10 rounded-xl pl-11 pr-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition"
                                   placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm rounded-xl py-3.5 transition-all duration-200 shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/35 focus:outline-none focus:ring-2 focus:ring-emerald-400/50 active:scale-[0.99]">
                        Sign In
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
            <?= e($clubName) ?> · D Ground, Faisalabad · <?= date('Y') ?>
        </p>
            </div>
        </div>

        <!-- Right: club atmosphere (desktop) -->
        <div class="relative hidden lg:block min-h-screen overflow-hidden">
            <img src="https://images.unsplash.com/photo-1550345332-09e3ac987658?w=1200&q=80&amp;auto=format&amp;fit=crop"
                 alt="Snooker at Asif Snooker Club" loading="lazy"
                 referrerpolicy="no-referrer"
                 class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0" style="background:linear-gradient(100deg, rgba(11,14,20,0.92) 0%, rgba(11,14,20,0.4) 45%, rgba(11,14,20,0.12) 100%);"></div>
            <div class="absolute top-0 left-0 p-8 flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-md shadow-emerald-500/20 flex items-center justify-center ring-1 ring-white/10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <div class="text-sm font-bold text-white tracking-tight">ASIF SNOOKER CLUB</div>
            </div>
            <div class="absolute bottom-0 left-0 p-8">
                <p class="text-xs font-semibold text-emerald-400 tracking-[0.2em] uppercase">D Ground · Faisalabad</p>
                <h2 class="mt-2 text-3xl font-extrabold text-white leading-snug">The house of<br>Pakistani snooker</h2>
                <div class="mt-4 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full" style="background:var(--a-400)"></span>
                    <span class="w-1.5 h-1.5 rounded-full" style="background:var(--a-500)"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-gold-400"></span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>