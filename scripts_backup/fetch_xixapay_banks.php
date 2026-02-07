<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Banking\Providers\XixapayProvider;

try {
    $provider = new XixapayProvider();
    $banks = $provider->getBanks();
    echo "First 5 Banks:\n";
    print_r(array_slice($banks, 0, 5));
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
