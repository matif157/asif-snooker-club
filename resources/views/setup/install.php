<?php
/** @var array $requirements */
/** @var array $errors */
$clubName = 'ASIF SNOOKER CLUB';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($clubName) ?> — Installer</title>
    <?= App\Services\ThemeService::cssVars('#10b981') ?>
    <script src="/assets/vendor/tailwind.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: {
                colors: {
                    ink: {900:'#0b0e14', 850:'#0f131c', 800:'#131824', 750:'#171d2b', 700:'#1b2233'},
                    emerald: {<?= App\Services\ThemeService::emeraldMapping() ?>},
                    gold: {400:'#fbbf24', 500:'#f59e0b'}
                },
                fontFamily: { sans: ['Inter','Manrope','system-ui','sans-serif'] }
            } }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        body { -webkit-font-smoothing: antialiased; }
        .bg-orbs::before, .bg-orbs::after { content:''; position:fixed; border-radius:9999px; filter:blur(120px); pointer-events:none; z-index:0; }
        .bg-orbs::before { width:480px; height:480px; top:-160px; right:-120px; background:rgba(16,185,129,0.12); }
        .bg-orbs::after  { width:420px; height:420px; bottom:-160px; left:-120px; background:rgba(245,158,11,0.06); }
        .glass { background:rgba(19,24,36,0.66); backdrop-filter:blur(14px); }
    </style>
</head>
<body class="bg-ink-900 text-slate-100 min-h-screen font-sans bg-orbs">
<div class="relative z-10 max-w-xl mx-auto px-4 py-10">

    <!-- Brand -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-lg shadow-emerald-500/25 mb-3 ring-1 ring-white/10">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/></svg>
        </div>
        <h1 class="text-xl font-bold tracking-tight text-white">Install <?= e($clubName) ?> CRM</h1>
        <p class="text-sm text-slate-400 mt-1.5">Create the database, tables, and your owner account in one step.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="mb-6 rounded-xl bg-rose-500/10 border border-rose-500/30 px-4 py-3 space-y-1">
            <?php foreach ($errors as $err): ?>
                <p class="text-sm text-rose-300">• <?= e($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Requirements -->
    <div class="glass rounded-2xl border border-white/10 p-5 mb-6 shadow-2xl shadow-black/40">
        <h2 class="text-sm font-semibold text-white mb-3">Server requirements</h2>
        <?php foreach ($requirements as $req): ?>
            <div class="flex items-center justify-between py-1.5 border-b border-white/[0.04] last:border-0">
                <span class="text-sm text-slate-300"><?= e($req['name']) ?></span>
                <span class="flex items-center gap-2 text-xs">
                    <span class="text-slate-500"><?= e($req['detail']) ?></span>
                    <?php if ($req['ok']): ?>
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <?php else: ?>
                        <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                    <?php endif; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Form -->
    <div class="glass rounded-2xl border border-white/10 shadow-2xl shadow-black/40 overflow-hidden">
        <div class="h-1 bg-gradient-to-r from-emerald-500 via-emerald-400 to-gold-500"></div>
        <form method="POST" action="<?= e(url('/install')) ?>" class="p-6 sm:p-8 space-y-6">
            <?= csrf_field() ?>

            <div>
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-6 h-6 rounded-full bg-emerald-500/15 text-emerald-400 text-xs font-bold flex items-center justify-center">1</span>
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Database</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Host</label>
                        <input name="host" class="input" value="127.0.0.1">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Port</label>
                        <input name="port" class="input" value="3306">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Database name *</label>
                        <input name="db" class="input" placeholder="asif_snooker_club" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Username *</label>
                        <input name="user" class="input" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Password</label>
                        <input name="pass" type="password" class="input">
                    </div>
                </div>
            </div>

            <div class="border-t border-white/[0.06] pt-6">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-6 h-6 rounded-full bg-emerald-500/15 text-emerald-400 text-xs font-bold flex items-center justify-center">2</span>
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Club details</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Club name</label>
                        <input name="club_name" class="input" value="Asif Snooker Club">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Club phone</label>
                        <input name="club_phone" class="input" value="+923001234567">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Address</label>
                        <input name="club_address" class="input" value="D Ground, Faisalabad">
                    </div>
                </div>
            </div>

            <div class="border-t border-white/[0.06] pt-6">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-6 h-6 rounded-full bg-emerald-500/15 text-emerald-400 text-xs font-bold flex items-center justify-center">3</span>
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Admin / Owner account</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Full name *</label>
                        <input name="admin_name" class="input" placeholder="Club Owner" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Email *</label>
                        <input name="admin_email" type="email" class="input" placeholder="owner@example.com" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Password *</label>
                        <input name="admin_password" type="password" class="input" minlength="8" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Confirm password *</label>
                        <input name="admin_password_confirm" type="password" class="input" minlength="8" required>
                    </div>
                </div>
            </div>

            <button type="submit"
                    class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm rounded-xl py-3.5 transition-all duration-200 shadow-lg shadow-emerald-500/25 active:scale-[0.99]">
                Install
            </button>
            <p class="text-center text-xs text-slate-500">Creates the database, applies schema, seeds demo tables, and writes .env</p>
        </form>
    </div>
</div>
</body>
</html>