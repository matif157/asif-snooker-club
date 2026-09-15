<?php /** @var string $content */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt — Asif Snooker Club</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .receipt-sheet { box-shadow: none !important; margin: 0 !important; max-width: 100% !important; border: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 font-sans">
    <div class="min-h-screen p-4 sm:p-8">
        <div class="max-w-[420px] mx-auto">
            <div class="no-print flex items-center justify-between gap-3 mb-4">
                <a href="javascript:history.back()" class="text-sm text-slate-500 hover:text-slate-700 font-medium">&larr; Back</a>
                <button onclick="window.print()"
                        class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition shadow-sm">
                    Print Receipt
                </button>
            </div>
            <?= $content ?>
        </div>
    </div>
</body>
</html>