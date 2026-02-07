<?php

$baseUrl = 'http://127.0.0.1:8000';
$verifyEndpoint = '/api/user/kyc/verify';
$detailsEndpoint = '/api/user/kyc/details';

echo "Testing KYC Verification Logic...\n";

// 1. Test Validation (Missing Fields)
echo "\n[TEST 1] Missing Fields\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/api/user/kyc/verify");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['id_type' => 'bvn'])); // Missing id_number
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "Result: " . ($httpCode == 400 ? "PASS" : "FAIL ($httpCode)") . "\n";
echo "Body: $response\n";

// 2. Test Validation (Invalid Type)
echo "\n[TEST 2] Invalid Type\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/api/user/kyc/verify");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['id_type' => 'xyz', 'id_number' => '1234567890']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "Result: " . ($httpCode == 400 ? "PASS" : "FAIL ($httpCode)") . "\n";
echo "Body: $response\n";

// 3. Test Details (Unauthenticated - Expect 401 or Redirect)
echo "\n[TEST 3] Get Details (No Auth)\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/api/user/kyc/details");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "Result: " . ($httpCode == 401 || $httpCode == 302 ? "PASS" : "FAIL ($httpCode)") . "\n";
