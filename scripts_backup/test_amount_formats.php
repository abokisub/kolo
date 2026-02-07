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

echo "Testing different amount formats for NGN card...\n\n";

// Test 1: Amount as integer (kobo format like Paystack)
echo "Test 1: Amount = 100000 (100000 kobo = 1000 NGN)\n";
$response1 = Http::timeout(180)->withOptions([
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
            'amount' => 100000  // Try as kobo
        ]);
echo "Status: " . $response1->status() . "\n";
echo "Response: " . $response1->body() . "\n\n";

// Test 2: Amount as decimal string
echo "Test 2: Amount = \"1000.00\" (as string)\n";
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
            'amount' => "1000.00"
        ]);
echo "Status: " . $response2->status() . "\n";
echo "Response: " . $response2->body() . "\n\n";

// Test 3: Amount = 1 (minimum)
echo "Test 3: Amount = 1 (minimum)\n";
$response3 = Http::timeout(180)->withOptions([
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
            'amount' => 1
        ]);
echo "Status: " . $response3->status() . "\n";
echo "Response: " . $response3->body() . "\n";
