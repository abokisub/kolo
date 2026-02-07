<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Banking\Providers\XixapayProvider;
use Illuminate\Support\Facades\DB;

$username = 'developer';
$user = DB::table('user')->where('username', $username)->first();

if (!$user) {
    die("User $username not found\n");
}

echo "Final test for $username (Customer ID: {$user->customer_id})\n";

$provider = new XixapayProvider();
// Try to create NGN card with 0 initial funding
// We use the non-versioned endpoint as confirmed by previous 404s
$result = $provider->createVirtualCard($user->customer_id, 'NGN', 0);

echo "Result Status: " . $result['status'] . "\n";
echo "Result Message: " . $result['message'] . "\n";
echo "Full Response: " . json_encode($result['full_response'] ?? []) . "\n";

if ($result['status'] === 'success') {
    echo "SUCCESS! Card created with ID: " . $result['card_id'] . "\n";
}
