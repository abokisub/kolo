<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Banking\Providers\XixapayProvider;
use Illuminate\Support\Facades\DB;

$username = 'developer';
$user = DB::table('user')->where('username', $username)->first();

echo "Testing card creation with 100 NGN funding for $username...\n";

$provider = new XixapayProvider();
$result = $provider->createVirtualCard($user->customer_id, 'NGN', 100);

echo "Result Status: " . $result['status'] . "\n";
echo "Result Message: " . $result['message'] . "\n";
echo "Full Response: " . json_encode($result['full_response'] ?? []) . "\n";
