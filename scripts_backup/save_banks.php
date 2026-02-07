<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\Http;

echo "Fetching banks...\n";
$response = Http::timeout(60)->get('https://api.xixapay.com/api/get/banks');

if ($response->successful()) {
    file_put_contents('xixa_banks.json', $response->body());
    echo "Saved to xixa_banks.json";
} else {
    echo "Failed: " . $response->status();
}
