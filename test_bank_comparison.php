<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== STARTING BANK PROVIDER COMPARISON ===\n";

// 1. PAYSTACK
echo "\n[1/3] Fetching Paystack Banks...\n";
$paystackKey = config('services.paystack.secret_key') ?? config('app.paystack_secret_key'); // Fallback check
if (empty($paystackKey) && isset($_ENV['PAYSTACK_SECRET_KEY']))
    $paystackKey = $_ENV['PAYSTACK_SECRET_KEY'];

$paystackBanks = [];
try {
    if (!$paystackKey)
        throw new Exception("Paystack Secret Key is missing.");

    // Using the exact endpoint user verified
    $response = Http::withToken($paystackKey)->get('https://api.paystack.co/bank', ['country' => 'nigeria', 'perPage' => 100]);

    if ($response->successful()) {
        $data = $response->json()['data'] ?? [];
        foreach ($data as $bank) {
            $paystackBanks[strtoupper($bank['name'])] = $bank['code'];
        }
        echo "SUCCESS: Fetched " . count($paystackBanks) . " banks from Paystack.\n";
    } else {
        echo "FAIL: Paystack API Error: " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
}

// 2. MONNIFY
echo "\n[2/3] Fetching Monnify Banks...\n";
$monnifyConfig = config('services.monnify'); // access from config/services.php
$monnifyBanks = [];

try {
    $apiKey = $monnifyConfig['api_key'] ?? env('MONNIFY_API_KEY');
    $secretKey = $monnifyConfig['secret_key'] ?? env('MONNIFY_SECRET_KEY');
    $baseUrl = ($monnifyConfig['mode'] ?? env('MONNIFY_MODE') === 'live')
        ? 'https://api.monnify.com'
        : 'https://sandbox.monnify.com';

    if (!$apiKey || !$secretKey)
        throw new Exception("Monnify Credentials missing.");

    // A. Authenticate
    echo "    Authenticating with Monnify ($baseUrl)...\n";
    $authString = base64_encode("$apiKey:$secretKey");
    $authResponse = Http::withHeaders(['Authorization' => 'Basic ' . $authString])
        ->post("$baseUrl/api/v1/auth/login");

    if (!$authResponse->successful()) {
        throw new Exception("Monnify Login Failed: " . $authResponse->body());
    }

    $accessToken = $authResponse->json()['responseBody']['accessToken'];

    // B. Fetch Banks
    $response = Http::withToken($accessToken)->get("$baseUrl/api/v1/banks");

    if ($response->successful()) {
        $body = $response->json();
        $list = $body['responseBody'] ?? [];
        foreach ($list as $bank) {
            // Monnify names might slightly differ, normalizing
            $monnifyBanks[strtoupper($bank['name'])] = $bank['code'];
        }
        echo "SUCCESS: Fetched " . count($monnifyBanks) . " banks from Monnify.\n";
    } else {
        echo "FAIL: Monnify API Error: " . $response->body() . "\n";
    }

} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
}

// 3. XIXAPAY
echo "\n[3/3] Fetching Xixapay Banks...\n";
$xixapayBanks = [];
try {
    // Attempting simple get as per documentation provided (GET https://api.xixapay.com/api/get/banks)
    // Doc didn't mention auth for *getting* banks, but let's see. 
    // Usually fetching banks is public, but user doc said Payout requires headers.
    // Let's try public first, if fail, add headers.

    $response = Http::timeout(10)->get('https://api.xixapay.com/api/get/banks');

    if ($response->successful()) {
        $data = $response->json();
        $list = $data['data'] ?? [];

        foreach ($list as $bank) {
            // Xixapay returns 'bankName' and 'bankCode'
            $name = $bank['bankName'] ?? $bank['name'] ?? 'Unknown';
            $code = $bank['bankCode'] ?? $bank['code'] ?? '???';
            $xixapayBanks[strtoupper($name)] = $code;
        }
        echo "SUCCESS: Fetched " . count($xixapayBanks) . " banks from Xixapay.\n";
    } else {
        echo "FAIL: Xixapay API Error: " . $response->status() . "\n";
    }

} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
}

// 4. COMPARISON
echo "\n=== CODE COMPARISON (Sample) ===\n";
$samples = ['ACCESS BANK', 'GUARANTY TRUST BANK', 'ZENITH BANK', 'OPAY', 'PALMPAY']; // Common ones

echo str_pad("BANK", 25) . str_pad("PAYSTACK", 10) . str_pad("MONNIFY", 10) . str_pad("XIXAPAY", 10) . "\n";
echo str_repeat("-", 60) . "\n";

// Merge all keys to find matches if names differ slightly, but for now exact match on upper case
$allNames = array_unique(array_merge(array_keys($paystackBanks), array_keys($monnifyBanks), array_keys($xixapayBanks)));

// Filter for our samples or just iterate limited list? 
// Let's do samples first.
foreach ($samples as $name) {
    // Fuzy match? NO, simple direct check first
    // Names might be "Access Bank" vs "Access Bank Plc"

    $pCode = findCode($paystackBanks, $name);
    $mCode = findCode($monnifyBanks, $name);
    $xCode = findCode($xixapayBanks, $name);

    echo str_pad(substr($name, 0, 24), 25)
        . str_pad($pCode ?? 'N/A', 10)
        . str_pad($mCode ?? 'N/A', 10)
        . str_pad($xCode ?? 'N/A', 10)
        . "\n";
}

function findCode($list, $target)
{
    foreach ($list as $name => $code) {
        if (strpos($name, $target) !== false)
            return $code;
    }
    return null;
}

echo "\nDone.\n";
