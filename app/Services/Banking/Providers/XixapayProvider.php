<?php

namespace App\Services\Banking\Providers;

use App\Services\Banking\BankingProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XixapayProvider implements BankingProviderInterface
{
    protected $apiKey;
    protected $secretKey;
    protected $businessId;

    public function __construct()
    {
        $config = config('services.xixapay');
        $this->apiKey = $config['api_key'];
        // The Authorization header comes with "Bearer " prefix in config
        $this->secretKey = str_replace('Bearer ', '', $config['authorization']);
        $this->businessId = $config['business_id'];
    }

    public function getProviderSlug(): string
    {
        return 'xixapay';
    }

    public function getBanks(): array
    {
        $response = Http::timeout(30)->get('https://api.xixapay.com/api/get/banks');

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch banks from Xixapay: ' . $response->status());
        }

        $data = $response->json();

        // Validation of response format
        if (!isset($data['status']) || $data['status'] !== 'success' || !isset($data['data'])) {
            // If implicit success (just array), handle here if we discover that format
            // For now assuming documented format or failing
            throw new \Exception('Invalid response structure from Xixapay Banks API');
        }

        return collect($data['data'])->map(function ($bank) {
            return [
                'name' => $bank['bankName'],
                'code' => $bank['bankCode'], // Xixapay Code
                'slug' => strtolower(str_replace(' ', '-', $bank['bankName'])),
                'active' => true,
                'xixapay_code' => $bank['bankCode']
            ];
        })->values()->toArray();
    }

    public function verifyAccount(string $accountNumber, string $bankCode): array
    {
        $response = Http::timeout(5)->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->post('https://api.xixapay.com/api/verify/bank', [
                    'bank' => $bankCode,
                    'accountNumber' => $accountNumber
                ]);

        if (!$response->successful()) {
            throw new \Exception('Xixapay verification failed: ' . $response->body());
        }

        $data = $response->json();

        // Xixapay returns AccountName at top level
        $accountName = $data['AccountName'] ?? $data['data']['account_name'] ?? null;

        if (!$accountName) {
            throw new \Exception('Could not resolve account name from Xixapay response');
        }

        return [
            'status' => 'success',
            'data' => [
                'account_name' => $accountName,
                'account_number' => $accountNumber,
                'bank_code' => $bankCode
            ]
        ];
    }

    public function transfer(array $details): array
    {
        // Xixapay Transfer
        $response = Http::timeout(60)->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->post('https://api.xixapay.com/api/v1/transfer', [
                    'businessId' => $this->businessId,
                    'amount' => $details['amount'],
                    'bank' => $details['bank_code'],
                    'accountNumber' => $details['account_number'],
                    'narration' => $details['narration'] ?? 'Transfer'
                ]);

        if ($response->successful()) {
            $data = $response->json();
            // Check specific status in body
            if (isset($data['status']) && $data['status'] === 'success') {
                return [
                    'status' => 'success',
                    'message' => $data['message'] ?? 'Transfer successful',
                    'reference' => $details['reference'],
                    'provider_reference' => $data['reference'] ?? null
                ];
            }

            return [
                'status' => 'fail',
                'message' => $data['message'] ?? 'Transfer failed'
            ];
        }

        return [
            'status' => 'fail',
            'message' => 'Xixapay API Error: ' . $response->body()
        ];
    }
    public function getBalance(): float
    {
        $response = Http::timeout(10)->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->get('https://api.xixapay.com/api/get/balance'); // Assuming endpoint based on conv.

        if ($response->successful()) {
            $data = $response->json();
            return (float) ($data['balance'] ?? 0);
        }

        return 0.0;
    }

    public function queryTransfer(string $reference): array
    {
        // Xixapay status check
        // Assuming endpoint: /api/v1/transfer/status or similar.
        // Documentation not fully provided, so using best guess or standard pattern.
        // User checklist says "Correct States: initiated -> processing -> pending ... " via webhook usually.
        // But for query:

        $response = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->get('https://api.xixapay.com/api/v1/transfer/status', [
                    'reference' => $reference,
                    'businessId' => $this->businessId
                ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'status' => $data['status'] ?? 'unknown',
                'message' => $data['message'] ?? 'Status retrieved',
                'data' => $data
            ];
        }

        return [
            'status' => 'failed',
            'message' => 'Query failed: ' . $response->body()
        ];
    }
}

