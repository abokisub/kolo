<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

$keys = DB::table('habukhan_key')->first();
$apiKey = $keys->mon_app_key;
$secretKey = $keys->mon_sk_key;

echo "Logging in...\n";
$auth = Http::withHeaders([
    'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $secretKey)
])->post('https://api.monnify.com/api/v1/auth/login');

if ($auth->successful()) {
    $token = $auth->json()['responseBody']['accessToken'];
    echo "Login Successful.\n";

    echo "\nFetching Wallet Details...\n";
    $wallets = Http::withToken($token)->get('https://api.monnify.com/api/v1/disbursements/wallet');
    print_r($wallets->json());
} else {
    echo "Auth Failed: " . $auth->body() . "\n";
}
