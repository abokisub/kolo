<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.xixapay.com/api/get/banks");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

// Disable SSL verify if certificate is issue (temporary debug)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$output = curl_exec($ch);
if ($output === FALSE) {
    echo "cURL Error: " . curl_error($ch);
} else {
    file_put_contents('xixa_banks.json', $output);
    echo "Saved to xixa_banks.json. Size: " . strlen($output);
}
curl_close($ch);
