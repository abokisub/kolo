<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Banking\Providers\MonnifyProvider;

$provider = new MonnifyProvider();

echo "Testing Monnify Account Verification...\n";
try {
    // Testing with OPay (100004) and the user's account number from before
    $verify = $provider->verifyAccount('7063282968', '100004');
    print_r($verify);
} catch (\Exception $e) {
    echo "Verification Error: " . $e->getMessage() . "\n";
}

echo "\nTesting Monnify Transfer (Dry Run / Initiation)...\n";
try {
    $details = [
        'amount' => 100,
        'bank_code' => '100004',
        'account_number' => '7063282968',
        'reference' => 'TEST_MONNIFY_' . time(),
        'narration' => 'Test Monnify Transfer'
    ];
    $transfer = $provider->transfer($details);
    print_r($transfer);
} catch (\Exception $e) {
    echo "Transfer Error: " . $e->getMessage() . "\n";
}
