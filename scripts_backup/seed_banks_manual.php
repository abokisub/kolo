<?php

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$banks = [
    ['name' => 'Access Bank', 'code' => '044', 'slug' => 'access-bank'],
    ['name' => 'Access Bank (Diamond)', 'code' => '063', 'slug' => 'access-bank-diamond'],
    ['name' => 'ALAT by WEMA', 'code' => '035A', 'slug' => 'alat-by-wema'],
    ['name' => 'ASO Savings and Loans', 'code' => '401', 'slug' => 'aso-savings-and-loans'],
    ['name' => 'Bowen Microfinance Bank', 'code' => '50931', 'slug' => 'bowen-microfinance-bank'],
    ['name' => 'Carbon', 'code' => '565', 'slug' => 'carbon'],
    ['name' => 'CEMCS Microfinance Bank', 'code' => '50823', 'slug' => 'cemcs-microfinance-bank'],
    ['name' => 'Citibank Nigeria', 'code' => '023', 'slug' => 'citibank-nigeria'],
    ['name' => 'Ecobank Nigeria', 'code' => '050', 'slug' => 'ecobank-nigeria'],
    ['name' => 'Ekondo Microfinance Bank', 'code' => '562', 'slug' => 'ekondo-microfinance-bank'],
    ['name' => 'Fidelity Bank', 'code' => '070', 'slug' => 'fidelity-bank'],
    ['name' => 'First Bank of Nigeria', 'code' => '011', 'slug' => 'first-bank-of-nigeria'],
    ['name' => 'First City Monument Bank', 'code' => '214', 'slug' => 'first-city-monument-bank'],
    ['name' => 'Globus Bank', 'code' => '00103', 'slug' => 'globus-bank'],
    ['name' => 'Guaranty Trust Bank', 'code' => '058', 'slug' => 'guaranty-trust-bank'],
    ['name' => 'Hasal Microfinance Bank', 'code' => '50383', 'slug' => 'hasal-microfinance-bank'],
    ['name' => 'Heritage Bank', 'code' => '030', 'slug' => 'heritage-bank'],
    ['name' => 'Jaiz Bank', 'code' => '301', 'slug' => 'jaiz-bank'],
    ['name' => 'Keystone Bank', 'code' => '082', 'slug' => 'keystone-bank'],
    ['name' => 'Kuda Bank', 'code' => '50211', 'slug' => 'kuda-bank'],
    ['name' => 'Lagos Building Investment Company Plc.', 'code' => '90052', 'slug' => 'lbic-plc'],
    ['name' => 'Moniepoint Microfinance Bank', 'code' => '50515', 'slug' => 'moniepoint-microfinance-bank'],
    ['name' => 'Opay', 'code' => '999992', 'slug' => 'paycom'],
    ['name' => 'Palmpay', 'code' => '999991', 'slug' => 'palmpay'],
    ['name' => 'Parallex Bank', 'code' => '526', 'slug' => 'parallex-bank'],
    ['name' => 'Parkway - ReadyCash', 'code' => '311', 'slug' => 'parkway-ready-cash'],
    ['name' => 'Polaris Bank', 'code' => '076', 'slug' => 'polaris-bank'],
    ['name' => 'Providus Bank', 'code' => '101', 'slug' => 'providus-bank'],
    ['name' => 'Rubies MFB', 'code' => '125', 'slug' => 'rubies-mfb'],
    ['name' => 'Sparkle Microfinance Bank', 'code' => '51310', 'slug' => 'sparkle-microfinance-bank'],
    ['name' => 'Stanbic IBTC Bank', 'code' => '221', 'slug' => 'stanbic-ibtc-bank'],
    ['name' => 'Standard Chartered Bank', 'code' => '068', 'slug' => 'standard-chartered-bank'],
    ['name' => 'Sterling Bank', 'code' => '232', 'slug' => 'sterling-bank'],
    ['name' => 'Suntrust Bank', 'code' => '100', 'slug' => 'suntrust-bank'],
    ['name' => 'TAJ Bank', 'code' => '302', 'slug' => 'taj-bank'],
    ['name' => 'TCF MFB', 'code' => '51211', 'slug' => 'tcf-mfb'],
    ['name' => 'Titan Bank', 'code' => '102', 'slug' => 'titan-bank'],
    ['name' => 'Union Bank of Nigeria', 'code' => '032', 'slug' => 'union-bank-of-nigeria'],
    ['name' => 'United Bank For Africa', 'code' => '033', 'slug' => 'united-bank-for-africa'],
    ['name' => 'Unity Bank', 'code' => '215', 'slug' => 'unity-bank'],
    ['name' => 'VFD Microfinance Bank', 'code' => '566', 'slug' => 'vfd'],
    ['name' => 'Wema Bank', 'code' => '035', 'slug' => 'wema-bank'],
    ['name' => 'Zenith Bank', 'code' => '057', 'slug' => 'zenith-bank']
];

echo "Bypassing network manual seed..." . PHP_EOL;

foreach ($banks as $bank) {
    if (!DB::table('unified_banks')->where('code', $bank['code'])->exists()) {
        DB::table('unified_banks')->insert([
            'name' => $bank['name'],
            'code' => $bank['code'],
            'paystack_code' => $bank['code'], // Assume code is same for Paystack for now
            'xixapay_code' => $bank['code'],
            'active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        echo "Inserted: " . $bank['name'] . PHP_EOL;
    }
}

echo "Done seeding banks." . PHP_EOL;
$count = DB::table('unified_banks')->count();
echo "Total Banks in DB: " . $count . PHP_EOL;
