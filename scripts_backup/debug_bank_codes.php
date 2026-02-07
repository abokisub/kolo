<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "--- Unified Banks (First 5) ---\n";
$banks = DB::table('unified_banks')->whereNotNull('paystack_code')->limit(5)->get(['name', 'code', 'paystack_code']);
foreach ($banks as $bank) {
    echo "Name: {$bank->name} | Code: {$bank->code} | Paystack: {$bank->paystack_code}\n";
}

echo "\n--- Recent Transfers (First 5) ---\n";
$transfers = DB::table('transfers')->orderBy('id', 'desc')->limit(5)->get(['reference', 'bank_code', 'account_number']);
foreach ($transfers as $t) {
    echo "Ref: {$t->reference} | Bank Code: {$t->bank_code}\n";
}

echo "\n--- Checking OPay (999992) ---\n";
$opay = DB::table('unified_banks')->where('paystack_code', '999992')->orWhere('code', '999992')->first();
if ($opay) {
    echo "Found OPay: Name: {$opay->name} | Code: {$opay->code} | Paystack: {$opay->paystack_code}\n";
} else {
    echo "OPay 999992 NOT FOUND in unified_banks\n";
}

echo "\n--- Checking 035A ---\n";
$wema = DB::table('unified_banks')->where('paystack_code', '035A')->orWhere('code', '035A')->first();
if ($wema) {
    echo "Found 035A: Name: {$wema->name} | Code: {$wema->code} | Paystack: {$wema->paystack_code}\n";
} else {
    echo "035A NOT FOUND in unified_banks\n";
}
