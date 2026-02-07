<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\Http;

$config = config('services.xixapay');
$response = Http::timeout(30)->get('https://api.xixapay.com/api/get/banks');

if ($response->successful()) {
    $banks = $response->json()['data'];
    foreach ($banks as $bank) {
        if (stripos($bank['bankName'], 'opay') !== false || stripos($bank['bankName'], 'paycom') !== false) {
            echo "FOUND MATCH: " . $bank['bankName'] . " -> " . $bank['bankCode'] . "\n";
        }
    }
    // Also dump first 5 to see format
    echo "\n -- Sample Banks -- \n";
    print_r(array_slice($banks, 0, 5));
} else {
    echo "Failed: " . $response->body();
}
