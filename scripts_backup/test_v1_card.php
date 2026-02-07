<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Banking\Providers\XixapayProvider;
use Illuminate\Support\Facades\Http;

$username = 'developer';
$user = DB::table('user')->where('username', $username)->first();
$config = config('services.xixapay');
$secret = str_replace('Bearer ', '', $config['authorization']);

echo "Testing v1 card creation for $username...\n";

$payload = [
    'businessId' => $config['business_id'],
    'customer_id' => $user->customer_id,
    'country' => 'NG',
    'amount' => 0
];

$response = Http::timeout(60)->withHeaders([
    'Authorization' => 'Bearer ' . $secret,
    'api-key' => $config['api_key'],
    'Content-Type' => 'application/json'
])->post('https://api.xixapay.com/api/v1/card/create', $payload);

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
