<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Banking\Providers\XixapayProvider;
use Illuminate\Support\Facades\DB;

$username = 'developer';
$user = DB::table('user')->where('username', $username)->first();
$provider = new XixapayProvider();

echo "=== Xixapay Card Creation Diagnostics ===\n\n";

// Test 1: Try with amount = 1000 (minimum recommended)
echo "Test 1: Creating card with 1000 NGN initial funding...\n";
$result1 = $provider->createVirtualCard($user->customer_id, 'NGN', 1000);
echo "Status: " . $result1['status'] . "\n";
echo "Message: " . $result1['message'] . "\n";
if (isset($result1['full_response'])) {
    echo "Full Response: " . json_encode($result1['full_response']) . "\n";
}
echo "\n";

// Test 2: Check if customer exists and is valid
echo "Test 2: Verifying customer data...\n";
echo "Customer ID: " . $user->customer_id . "\n";
echo "Customer Email: " . $user->email . "\n";
echo "Customer Data: " . ($user->customer_data ?? 'NULL') . "\n";
echo "\n";

// Test 3: Try USD card to see if it's NGN-specific
echo "Test 3: Attempting USD card creation (for comparison)...\n";
$result3 = $provider->createVirtualCard($user->customer_id, 'USD', 3);
echo "Status: " . $result3['status'] . "\n";
echo "Message: " . $result3['message'] . "\n";
if (isset($result3['full_response'])) {
    echo "Full Response: " . json_encode($result3['full_response']) . "\n";
}
