<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Banking\Providers\XixapayProvider;
use Illuminate\Support\Facades\Log;

try {
    echo "Starting Xixapay Insufficient Funds Test...\n";

    $provider = new XixapayProvider();

    // Attempt a transfer of a large amount to trigger insufficient funds
    $payload = [
        'amount' => 150000, // 150k Naira (Within limit, likely above balance)
        'bank_code' => '058', // GTB
        'account_number' => '0123456789', // Dummy
        'account_name' => 'Test Account',
        'narration' => 'Xixapay Insufficient Funds Test',
        'reference' => 'FAIL_TEST_' . time()
    ];

    echo "Initiating Transfer of N{$payload['amount']}...\n";

    $result = $provider->transfer($payload);

    echo "\n--- Transfer Result (JSON) ---\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n------------------------------\n";

} catch (\Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
}
