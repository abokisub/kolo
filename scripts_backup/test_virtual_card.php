<?php

$baseUrl = 'http://127.0.0.1:8000';

echo "Testing Virtual Card API...\n";

// 1. Test NGN Card (No Auth)
echo "\n[TEST 1] NGN Card (No Auth)\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/api/user/card/ngn");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "Result: " . ($httpCode == 401 ? "PASS" : "FAIL ($httpCode)") . "\n";
echo "Body: $response\n";

// 2. Test USD Card (No Auth)
echo "\n[TEST 2] USD Card (No Auth)\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/api/user/card/usd");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "Result: " . ($httpCode == 401 ? "PASS" : "FAIL ($httpCode)") . "\n";
echo "Body: $response\n";

// 3. Test Get Cards (No Auth)
echo "\n[TEST 3] Get Cards (No Auth)\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/api/user/cards");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "Result: " . ($httpCode == 401 ? "PASS" : "FAIL ($httpCode)") . "\n";
