<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "--- Testing Transfer Join Query ---\n";

// Replicate the query from AdminTrans.php
$results = DB::table('transfers')
    ->join('user', 'transfers.user_id', '=', 'user.id')
    ->leftJoin('unified_banks', 'transfers.bank_code', '=', 'unified_banks.paystack_code')
    ->select('transfers.reference', 'transfers.bank_code', 'user.username', 'unified_banks.name as bank_name', 'unified_banks.paystack_code')
    ->orderBy('transfers.id', 'desc')
    ->limit(5)
    ->get();

foreach ($results as $row) {
    echo "Ref: {$row->reference} | Bank Code: {$row->bank_code} | Paystack Code: {$row->paystack_code} | Bank Name: " . ($row->bank_name ?? 'NULL') . "\n";
}
