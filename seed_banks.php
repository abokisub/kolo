<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "Seeding Unified Banks with Fallback Data...\n";

$banks = [
    ['name' => 'Access Bank', 'code' => '044'],
    ['name' => 'Citibank', 'code' => '023'],
    ['name' => 'Ecobank Nigeria', 'code' => '050'],
    ['name' => 'Fidelity Bank', 'code' => '070'],
    ['name' => 'First Bank of Nigeria', 'code' => '011'],
    ['name' => 'First City Monument Bank', 'code' => '214'],
    ['name' => 'Guaranty Trust Bank', 'code' => '058'],
    ['name' => 'Heritage Bank', 'code' => '030'],
    ['name' => 'Keystone Bank', 'code' => '082'],
    ['name' => 'Opay', 'code' => '999992'],
    ['name' => 'Palmpay', 'code' => '999991'],
    ['name' => 'Polaris Bank', 'code' => '076'],
    ['name' => 'Providus Bank', 'code' => '101'],
    ['name' => 'Stanbic IBTC Bank', 'code' => '221'],
    ['name' => 'Standard Chartered Bank', 'code' => '068'],
    ['name' => 'Sterling Bank', 'code' => '232'],
    ['name' => 'Union Bank of Nigeria', 'code' => '032'],
    ['name' => 'United Bank For Africa', 'code' => '033'],
    ['name' => 'Unity Bank', 'code' => '215'],
    ['name' => 'Wema Bank', 'code' => '035'],
    ['name' => 'Zenith Bank', 'code' => '057'],
];

$count = 0;
foreach ($banks as $bank) {
    if (DB::table('unified_banks')->where('code', $bank['code'])->count() == 0) {
        DB::table('unified_banks')->insert([
            'name' => $bank['name'],
            'code' => $bank['code'],
            'paystack_code' => $bank['code'], // Assume Generic Code works for Paystack
            'xixapay_code' => $bank['code'], // Assume Generic Code works for Xixapay
            'active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        $count++;
    }
}

echo "Seeded $count banks successfully.\n";
