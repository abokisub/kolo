<?php

echo "Testing Card Webhook (Phase 6)...\n";
$url = "http://127.0.0.1:8000/api/webhooks/xixapay/card";

// Mock Payload
$payload = json_encode([
    'card_id' => 'CARD_TEST_123',
    'transaction_id' => 'TXN_' . time(),
    'status' => 'success',
    'amount' => -15.50,
    'currency' => 'USD',
    'merchant_name' => 'Netflix',
    'type' => 'transaction'
]);

echo "Sending Payload: $payload\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Result Code: $httpCode\n";
echo "Response: $response\n";

// Expected: 200 OK (with 'ignored' message because CARD_TEST_123 doesn't exist locally)
