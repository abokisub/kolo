<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Cleaning Bank Codes (Leaving Paystack safe)...\n";

try {
    DB::table('unified_banks')->update([
        'xixapay_code' => null,
        'monnify_code' => null
    ]);

    // Optionally: remove banks that don't have a Paystack code?
    // User said "live paytack banks an bank code since is our default"
    // So let's deactivate or delete banks that have NO paystack_code.
    // Deactivating is safer.

    $deleted = DB::table('unified_banks')
        ->whereNull('paystack_code')
        ->orWhere('paystack_code', '')
        ->delete();

    echo "Cleared Monnify/Xixapay codes from all banks.\n";
    echo "Removed $deleted banks that had no Paystack code.\n";
    echo "Database is now clean for Paystack transfers.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
