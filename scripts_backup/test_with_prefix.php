<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

$username = 'developer';
$user = DB::table('user')->where('username', $username)->first();
$config = config('services.xixapay');
$secret = str_replace('Bearer ', '', $config['authorization']);

echo "Testing with xixapay_ prefix...\n\n";

// Test 1: With prefix
$businessIdWithPrefix = 'xixapay_' . $config['business_id'];
echo "Test 1: businessId = $businessIdWithPrefix\n";

$response1 = Http::timeout(180)->withOptions([
    'verify' => true,
    'http_version' => '1.1',
    'connect_timeout' => 30,
])->withHeaders([
            'Authorization' => 'Bearer ' . $secret,
            'api-key' => $config['api_key'],
            'Content-Type' => 'application/json'
        ])->post('https://api.xixapay.com/api/card/create', [
            'businessId' => $businessIdWithPrefix,
            'customer_id' => $user->customer_id,
            'country' => 'NG',
            'amount' => 0
        ]);

echo "Status: " . $response1->status() . "\n";
echo "Response: " . $response1->body() . "\n\n";

// Test 2: Without prefix (current)
echo "Test 2: businessId = " . $config['business_id'] . " (current)\n";

$response2 = Http::timeout(180)->withOptions([
    'verify' => true,
    'http_version' => '1.1',
    'connect_timeout' => 30,
])->withHeaders([
            'Authorization' => 'Bearer ' . $secret,
            'api-key' => $config['api_key'],
            'Content-Type' => 'application/json'
        ])->post('https://api.xixapay.com/api/card/create', [
            'businessId' => $config['business_id'],
            'customer_id' => $user->customer_id,
            'country' => 'NG',
            'amount' => 0
        ]);

echo "Status: " . $response2->status() . "\n";
echo "Response: " . $response2->body() . "\n";
