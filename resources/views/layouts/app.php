<?php
/** @var string $content */
$user = current_user();
$currentPage = basename($_SERVER['REQUEST_URI'] ?? '/');
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(\App\Services\SettingsService::clubName()) ?></title>
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <?= App\Services\ThemeService::themeBoot(App\Services\ThemeService::userTheme()) ?>
    <?= App\Services\ThemeService::cssVars() ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        ink: {900:'#0b0e14',850:'#0f131c',800:'#131824',750:'#171d2b',700:'#1b2233',600:'#232b3d'},
                        emerald: {<?= App\Services\ThemeService::emeraldMapping() ?>},
                        gold: {400:'#fbbf24', 500:'#f59e0b'}
                    },
                    fontFamily: {sans:['Inter','Manrope','system-ui','sans-serif']}
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-ink-900 text-slate-200 min-h-screen font-sans antialiased">
<?php if (is_authenticated()): ?>
<div class="flex min-h-screen" x-data="{ sidebar:true, mobileNav:false }">

    <!-- Mobile overlay -->
    <div x-show="mobileNav" @click="mobileNav=false" class="fixed inset-0 bg-black/60 z-40 lg:hidden" x-transition.opacity></div>

    <!-- Sidebar -->
    <?php include ROOT_PATH . '/resources/views/partials/sidebar.php' ?>

    <!-- Main area -->
    <div class="flex-1 min-h-screen flex flex-col lg:ml-[240px] ml-0">

        <!-- Header -->
        <?php include ROOT_PATH . '/resources/views/partials/header.php' ?>

        <!-- Page content -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-[1400px] mx-auto w-full">
            <?php
                $flashError   = flash('error');
                $flashSuccess = flash('success');
            ?>
            <?php if ($flashSuccess): ?>
                <div class="mb-4 flex items-center gap-2.5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300" id="flash-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?= e($flashSuccess) ?>
                    <button onclick="this.parentElement.remove()" class="ml-auto text-emerald-400/70 hover:text-emerald-200">✕</button>
                </div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="mb-4 flex items-center gap-2.5 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-300" id="flash-error">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <?= e($flashError) ?>
                    <button onclick="this.parentElement.remove()" class="ml-auto text-rose-400/70 hover:text-rose-200">✕</button>
                </div>
            <?php endif; ?>
            <?= $content ?>
        </main>

        <!-- Footer -->
        <?php include ROOT_PATH . '/resources/views/partials/footer.php' ?>

    </div>
</div>
<?php else: ?>
<!-- Unauthenticated — direct page content, no layout chrome -->
<?= $content ?>
<?php endif; ?>
<script src="/assets/js/app.js"></script>
<script src="/assets/js/tables.js"></script>
</body>
</html>