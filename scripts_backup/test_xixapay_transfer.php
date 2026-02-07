<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Banking\BankingService;
use Illuminate\Support\Facades\Log;

try {
    echo "Starting Xixapay Transfer Test...\n";

    // 1. Verify Configuration
    $service = new BankingService();
    $active = $service->getActiveProvider();

    echo "Active Provider: " . $active->getProviderSlug() . "\n";

    if ($active->getProviderSlug() !== 'xixapay') {
        throw new \Exception("Error: Active provider is NOT Xixapay. Please check settings.");
    }

    // 2. Real Transfer Data
    // EDIT THESE VALUES TO TEST REAL 100 NAIRA TRANSFER
    $payload = [
        'amount' => 100, // 100 Naira
        'bank_code' => '058', // Change to destination bank code (e.g. 058 for GTB)
        'account_number' => '0123456789', // CHANGE THIS to real account number
        'account_name' => 'Test Account', // Optional validation
        'narration' => 'Xixapay Live Sys Test',
        'reference' => 'LIVE_' . time()
    ];

    echo "Initiating Transfer of N{$payload['amount']} to {$payload['account_number']} ({$payload['bank_code']})...\n";

    // 3. Initiate Transfer
    $result = $service->transfer($payload);

    echo "\n--- Transfer Result (JSON) ---\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n------------------------------\n";

} catch (\Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
}
