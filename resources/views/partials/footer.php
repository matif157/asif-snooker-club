<?php if (!is_authenticated()) return; ?>
<footer class="py-4 px-6 text-center text-xs text-slate-600 border-t border-white/[0.04]">
    <?= e(config('app.name', 'ASIF SNOOKER CLUB')) ?> &middot; D Ground, Faisalabad &middot; &copy; <?= date('Y') ?>
</footer>