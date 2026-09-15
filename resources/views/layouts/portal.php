<?php /** @var string $content */ ?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Portal — Asif Snooker Club</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: { ink: {900:'#0b0e14',850:'#0f131c',800:'#131824',750:'#171d2b',700:'#1b2233',600:'#232b3d'} } } }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-ink-900 text-slate-200 min-h-screen font-sans antialiased">
    <div class="min-h-screen flex flex-col">
        <header class="border-b border-white/[0.06] bg-ink-850/80 backdrop-blur sticky top-0 z-40">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
                <a href="<?= e(url('/portal')) ?>" class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-md shadow-emerald-500/20 flex items-center justify-center ring-1 ring-white/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-white tracking-tight">ASIF SNOOKER</div>
                        <div class="text-[10px] text-slate-500 font-medium tracking-wide">MEMBER PORTAL</div>
                    </div>
                </a>
                <a href="<?= e(url('/')) ?>" class="text-xs text-slate-500 hover:text-emerald-400 transition">Staff login →</a>
            </div>
        </header>
        <main class="flex-1 w-full max-w-2xl mx-auto px-4 py-8">
            <?= $content ?>
        </main>
        <footer class="py-6 text-center text-[11px] text-slate-600">
            © <?= date('Y') ?> Asif Snooker Club — D Ground, Faisalabad
        </footer>
    </div>
</body>
</html>