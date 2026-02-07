<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Banking\Providers\PaystackProvider;
use App\Services\Banking\Providers\XixapayProvider;
use App\Services\Banking\Providers\MonnifyProvider;

echo "--- Provider Balance Check ---\n";

try {
    $ps = new PaystackProvider();
    echo "Paystack Balance: " . $ps->getBalance() . "\n";
} catch (\Exception $e) {
    echo "Paystack Error: " . $e->getMessage() . "\n";
}

try {
    $xx = new XixapayProvider();
    echo "Xixapay Balance: " . $xx->getBalance() . "\n";
} catch (\Exception $e) {
    echo "Xixapay Error: " . $e->getMessage() . "\n";
}

try {
    $mon = new MonnifyProvider();
    echo "Monnify Balance: " . $mon->getBalance() . "\n";
} catch (\Exception $e) {
    echo "Monnify Error: " . $e->getMessage() . "\n";
}
