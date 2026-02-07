<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Banking\BankingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$service = new BankingService();

echo "--- 1. Syncing Banks for Xixapay ---\n";
try {
    $count = $service->syncBanksFromProvider('xixapay');
    echo "Synced $count banks from Xixapay.\n";
} catch (\Exception $e) {
    echo "Sync Failed: " . $e->getMessage() . "\n";
}

echo "\n--- 2. Checking Supported Banks (Top 5) ---\n";
$banks = DB::table('unified_banks')->whereNotNull('xixapay_code')->limit(5)->get();
foreach ($banks as $b) {
    echo "{$b->name} [Code: {$b->code}] -> Xixapay: {$b->xixapay_code}\n";
}

echo "\n--- 3. Testing Balance Endpoint candidates ---\n";
$config = config('services.xixapay');
$secret = str_replace('Bearer ', '', $config['authorization']);
$apiKey = $config['api_key'];

$endpoints = [
    'https://api.xixapay.com/api/balance',
    'https://api.xixapay.com/api/get/balance',
    'https://api.xixapay.com/api/v1/balance'
];

foreach ($endpoints as $url) {
    echo "Trying $url ... ";
    try {
        $response = Http::timeout(5)->withHeaders([
            'Authorization' => 'Bearer ' . $secret,
            'api-key' => $apiKey,
            'Content-Type' => 'application/json'
        ])->get($url);

        echo $response->status();
        if ($response->successful()) {
            echo " [SUCCESS] Body: " . $response->body() . "\n";
        } else {
            echo " [FAIL]\n";
        }
    } catch (\Exception $e) {
        echo " [ERROR] " . $e->getMessage() . "\n";
    }
}
