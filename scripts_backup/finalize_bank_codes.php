<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting MASTER Bank Synchronization (Multi-Match + Global Overrides)...\n";

/**
 * Super aggressive bank name standardizer
 */
function standardizeBankName($name)
{
    if (!$name)
        return "";
    $name = strtolower($name);
    $remove = [
        'microfinance bank',
        'microfinance',
        'bank',
        'limited',
        'ltd',
        'plc',
        'lp',
        'commercial',
        'service',
        'services',
        'international',
        'integrated',
        'digital',
        'nigeria',
        'resources',
        'mortgage',
        'finance',
        'mfb',
        'community',
        'state',
        'savings',
        'loan',
        'and',
        '&',
        '-',
        '.',
        ',',
        '(',
        ')',
        '/',
        '\\',
        'ltd/gtd',
        'investment',
        'money',
        'payment',
        'solution',
        'solutions'
    ];
    foreach ($remove as $word) {
        $name = str_replace($word, ' ', $name);
    }
    $name = preg_replace('/\s+/', ' ', $name);
    return trim($name);
}

// --- 1. Fetch Provider Banks ---
echo "Fetching Provider Banks...\n";
$allProviderBanks = [
    'xixapay' => [],
    'paystack' => [],
    'monnify' => []
];

try {
    $xResp = Http::timeout(90)->get('https://api.xixapay.com/api/get/banks');
    if ($xResp->successful()) {
        $allProviderBanks['xixapay'] = $xResp->json()['data'] ?? [];
        file_put_contents('xixa_banks.json', json_encode($allProviderBanks['xixapay']));
    } elseif (file_exists('xixa_banks.json')) {
        $allProviderBanks['xixapay'] = json_decode(file_get_contents('xixa_banks.json'), true);
    }
} catch (\Exception $e) {
    if (file_exists('xixa_banks.json'))
        $allProviderBanks['xixapay'] = json_decode(file_get_contents('xixa_banks.json'), true);
}

try {
    $secretKey = config('paystack.secretKey') ?? env('PAYSTACK_SECRET_KEY') ?? DB::table('habukhan_key')->value('psk');
    $pResp = Http::withHeaders(['Authorization' => 'Bearer ' . $secretKey])->get('https://api.paystack.co/bank');
    if (!$pResp->successful())
        $pResp = Http::get('https://api.paystack.co/bank');
    if ($pResp->successful()) {
        $allProviderBanks['paystack'] = $pResp->json()['data'] ?? [];
    }
} catch (\Exception $e) {
}

try {
    $monProvider = new \App\Services\Banking\Providers\MonnifyProvider();
    $allProviderBanks['monnify'] = $monProvider->getBanks() ?? [];
} catch (\Exception $e) {
}

echo "Counts: Xixapay(" . count($allProviderBanks['xixapay']) . "), Paystack(" . count($allProviderBanks['paystack']) . "), Monnify(" . count($allProviderBanks['monnify']) . ")\n";

// --- 2. Bidirectional Multi-Match Sync ---
$countUpdated = 0;
$countNew = 0;

foreach ($allProviderBanks as $slug => $banks) {
    echo "Processing $slug banks...\n";
    foreach ($banks as $bank) {
        $name = $bank['bank_name'] ?? $bank['name'] ?? null;
        $code = $bank['bankCode'] ?? $bank['code'] ?? null;
        if (!$name || !$code)
            continue;

        $stdName = standardizeBankName($name);
        if (empty($stdName))
            continue;

        // Find ALL matching records in DB
        $records = DB::table('unified_banks')
            ->where('code', $code)
            ->orWhere("{$slug}_code", $code)
            ->orWhereRaw('LOWER(name) = ?', [strtolower($name)])
            ->get();

        if ($records->isEmpty()) {
            // Aggressive fuzzy match
            $allUnified = DB::table('unified_banks')->get();
            foreach ($allUnified as $ub) {
                $ubStd = standardizeBankName($ub->name);
                if ($ubStd === $stdName || (strlen($ubStd) > 4 && strpos($stdName, $ubStd) !== false) || (strlen($stdName) > 4 && strpos($ubStd, $stdName) !== false)) {
                    $records->push($ub);
                }
            }
        }

        if ($records->isNotEmpty()) {
            foreach ($records as $record) {
                if ($record->{"{$slug}_code"} !== $code) {
                    DB::table('unified_banks')->where('id', $record->id)->update(["{$slug}_code" => $code]);
                    $countUpdated++;
                }
            }
        } else {
            // New entry
            DB::table('unified_banks')->insert([
                'name' => $name,
                'code' => $code,
                "{$slug}_code" => $code,
                'active' => 1,
                'primary_provider' => $slug,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $countNew++;
        }
    }
}

// --- 3. Global Override Pass (Ensure consistency for major banks) ---
echo "\nApplying Global Overrides...\n";
$finalOverrides = [
    'opay' => ['xixapay' => '100004', 'paystack' => '999992', 'monnify' => '100004', 'code' => '100004'],
    'kuda' => ['xixapay' => '090267', 'paystack' => '090267', 'monnify' => '090267', 'code' => '090267'],
    'zenith' => ['xixapay' => '000015', 'paystack' => '057', 'monnify' => '000015', 'code' => '057'],
    'gtbank' => ['xixapay' => '000013', 'paystack' => '058', 'monnify' => '000013', 'code' => '058'],
    'access' => ['xixapay' => '000014', 'paystack' => '044', 'monnify' => '000014', 'code' => '044'],
    'first bank' => ['xixapay' => '000016', 'paystack' => '011', 'monnify' => '000016', 'code' => '011'],
    'alat' => ['xixapay' => '000017', 'paystack' => '035A', 'monnify' => '000017', 'code' => '035A'],
];

foreach ($finalOverrides as $key => $codes) {
    echo " - Overriding $key...\n";
    $affected = DB::table('unified_banks')
        ->whereRaw('LOWER(name) LIKE ?', ["%$key%"])
        ->update([
            'xixapay_code' => $codes['xixapay'],
            'paystack_code' => $codes['paystack'],
            'monnify_code' => $codes['monnify'],
            'code' => $codes['code']
        ]);
    echo "   Affected: $affected rows.\n";
}

echo "\nDone! Total Updated: $countUpdated | New: $countNew\n";
echo "Final DB Total: " . DB::table('unified_banks')->count() . "\n";
