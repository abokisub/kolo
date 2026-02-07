<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$keys = DB::table('habukhan_key')->first();
$apiKey = $keys->mon_app_key;
$secretKey = $keys->mon_sk_key;
$contractCode = $keys->mon_con_num;

echo "Keys: \nAPI Key: " . substr($apiKey, 0, 5) . "...\nSecret: " . substr($secretKey, 0, 5) . "...\nContract: " . $contractCode . "\n\n";

// 1. Auth
$authResponse = Http::withHeaders([
    'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $secretKey)
])->post('https://api.monnify.com/api/v1/auth/login');

if (!$authResponse->successful()) {
    die("Auth Failed: " . $authResponse->body());
}

$token = $authResponse->json()['responseBody']['accessToken'];
echo "Auth Successful.\n";

// 2. Balance
$balanceResponse = Http::withToken($token)->get('https://api.monnify.com/api/v2/disbursements/wallet-balance', [
    'walletId' => $contractCode
]);

echo "Balance Status: " . $balanceResponse->status() . "\n";
echo "Balance Response: " . $balanceResponse->body() . "\n";
