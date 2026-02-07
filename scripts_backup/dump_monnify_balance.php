<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Banking\Providers\MonnifyProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

$provider = new MonnifyProvider();
$reflection = new ReflectionClass($provider);
$getAccessToken = $reflection->getMethod('getAccessToken');
$getAccessToken->setAccessible(true);
$token = $getAccessToken->invoke($provider);

$baseUrl = 'https://api.monnify.com';
$config = DB::table('habukhan_key')->first();
$contractCode = $config->mon_con_num;

echo "Contract Code: $contractCode\n";

$response = Http::timeout(10)->withToken($token)->get($baseUrl . '/api/v2/disbursements/wallet-balance', [
    'walletId' => $contractCode
]);

echo "Status: " . $response->status() . "\n";

if ($response->successful()) {
    $data = $response->json();
    echo "Body: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Request Failed!\n";
    echo "Body: " . $response->body() . "\n";
}
