<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Banking\Providers\XixapayProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

$username = 'developer';
$logFile = __DIR__ . '/fix_log.txt';
file_put_contents($logFile, "Starting fix for $username at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

$user = DB::table('user')->where('username', $username)->first();

if (!$user) {
    file_put_contents($logFile, "User not found\n", FILE_APPEND);
    exit;
}

$kycDocs = json_decode($user->kyc_documents, true);
$meta = $kycDocs['submitted_metadata'];
$idCardPath = base_path('storage/app/public/' . $kycDocs['id_card']);
$utilityPath = base_path('storage/app/public/' . $kycDocs['utility_bill']);

function simulateFile($path)
{
    return new UploadedFile(
        $path,
        basename($path),
        'image/jpeg',
        null,
        true
    );
}

$payload = [
    'first_name' => 'Developer',
    'last_name' => 'Abokisub',
    'email' => $user->email,
    'phone_number' => $meta['phone'],
    'address' => $meta['address'],
    'state' => $meta['state'],
    'city' => $meta['city'],
    'postal_code' => $meta['postal_code'],
    'date_of_birth' => $meta['dob'],
    'id_type' => $kycDocs['id_type'],
    'id_number' => $kycDocs['id_number'],
    'id_card' => simulateFile($idCardPath),
    'utility_bill' => simulateFile($utilityPath),
];

$nameParts = explode(' ', $user->name, 2);
if (count($nameParts) >= 1)
    $payload['first_name'] = $nameParts[0];
if (isset($nameParts[1]))
    $payload['last_name'] = $nameParts[1];

$config = config('services.xixapay');
file_put_contents($logFile, "Config Business ID: " . ($config['business_id'] ?? 'MISSING') . "\n", FILE_APPEND);

file_put_contents($logFile, "Checking Business Balance...\n", FILE_APPEND);
try {
    $provider = new XixapayProvider();

    // Manual check to see raw response
    $response = Http::timeout(10)->withHeaders([
        'Authorization' => 'Bearer ' . str_replace('Bearer ', '', $config['authorization']),
        'api-key' => $config['api_key'],
        'Content-Type' => 'application/json'
    ])->get('https://api.xixapay.com/api/get/balance');

    file_put_contents($logFile, "Balance Status: " . $response->status() . "\n", FILE_APPEND);
    file_put_contents($logFile, "Balance Body: " . $response->body() . "\n", FILE_APPEND);

    $providerBalance = $provider->getBalance();
    file_put_contents($logFile, "Provider getBalance(): " . $providerBalance . "\n", FILE_APPEND);
} catch (\Exception $e) {
    file_put_contents($logFile, "Balance Check ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}

file_put_contents($logFile, "Proceeding with Customer Update...\n", FILE_APPEND);
try {
    $provider = new XixapayProvider();
    $result = $provider->updateCustomer($payload);

    if ($result['status'] === 'success') {
        $newCustomerId = $result['customer_id'];
        file_put_contents($logFile, "SUCCESS! New ID: $newCustomerId\n", FILE_APPEND);
        file_put_contents($logFile, "Full Result: " . json_encode($result) . "\n", FILE_APPEND);

        DB::table('user')->where('id', $user->id)->update([
            'customer_id' => $newCustomerId,
            'customer_data' => json_encode($result['full_response']),
            'address' => $meta['address']
        ]);
        file_put_contents($logFile, "Database updated.\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, "FAILED: " . ($result['message'] ?? 'No message') . "\n", FILE_APPEND);
        file_put_contents($logFile, "Full Response: " . json_encode($result['full_response'] ?? []) . "\n", FILE_APPEND);
    }
} catch (\Exception $e) {
    file_put_contents($logFile, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}
echo "Done. Check fix_log.txt\n";
