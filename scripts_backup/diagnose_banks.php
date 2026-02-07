<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- Unified Banks Search ---\n";
$terms = ['%OPay%', '%Paycom%', '%Opay%'];

foreach ($terms as $term) {
    $banks = DB::table('unified_banks')->where('name', 'like', $term)->get();
    echo "Search '$term': " . $banks->count() . " found.\n";
    foreach ($banks as $b) {
        echo " - Name: {$b->name}\n";
        echo "   Code (Default): {$b->code}\n";
        echo "   Xixapay Code: " . ($b->xixapay_code ?? 'NULL') . "\n";
        echo "   Paystack Code: " . ($b->paystack_code ?? 'NULL') . "\n";
    }
}

echo "\n--- Total Xixapay Banks ---\n";
echo DB::table('unified_banks')->whereNotNull('xixapay_code')->count() . "\n";
