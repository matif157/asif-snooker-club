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
    <title><?= e(config('app.name', 'ASIF SNOOKER CLUB')) ?></title>
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <script>
        if (localStorage.getItem('theme') === 'light') { document.documentElement.classList.remove('dark'); }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        ink: {900:'#0b0e14',850:'#0f131c',800:'#131824',750:'#171d2b',700:'#1b2233',600:'#232b3d'},
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
</body>
</html>