<?php

namespace App\Services\Banking;

use App\Services\Banking\Providers\PaystackProvider;
use App\Services\Banking\Providers\XixapayProvider;
use App\Services\Banking\Providers\MonnifyProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankingService
{
    /**
     * Resolve a provider instance by slug.
     * Keeps ability to instantiate others if ever needed manually, but default flow is Paystack.
     */
    public function resolveProvider(string $slug): BankingProviderInterface
    {
        switch (strtolower($slug)) {
            case 'xixapay':
                return new XixapayProvider();
            case 'monnify':
                return new MonnifyProvider();
            default:
                return new PaystackProvider();
        }
    }

    /**
     * Get the currently active primary transfer provider.
     * STRICTLY PAYSTACK for Transfers as per user requirement.
     */
    public function getActiveProvider(): BankingProviderInterface
    {
        // Enforce Paystack regardless of DB settings to prevent accidental failover
        return new PaystackProvider();
    }

    /**
     * Verify an account number.
     * STRICTLY PAYSTACK. No failover.
     */
    public function verifyAccount(string $accountNumber, string $bankCode): array
    {
        $provider = $this->getActiveProvider(); // Paystack

        try {
            // Resolve to Paystack bank code
            $providerCode = $this->resolveBankCode($bankCode, 'paystack');
            return $provider->verifyAccount($accountNumber, $providerCode);

        }
        catch (\Exception $e) {
            Log::error("BankingService: Verification failed (Paystack): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initiate a transfer.
     * STRICTLY PAYSTACK. No failover.
     */
    public function transfer(array $details): array
    {
        $provider = $this->getActiveProvider(); // Paystack

        // Resolve bank code for Paystack
        $details['bank_code'] = $this->resolveBankCode($details['bank_code'], 'paystack');

        try {
            return $provider->transfer($details);
        }
        catch (\Exception $e) {
            Log::error("BankingService: Transfer Error (Paystack): " . $e->getMessage());
            return [
                'status' => 'fail',
                'message' => 'Transfer failed. ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get list of supported banks from the Unified Database.
     * Filter ensures we only populate UI with banks valid for Paystack transfers.
     */
    public function getSupportedBanks()
    {
        return DB::table('unified_banks')
            ->where('active', true)
            ->whereNotNull('paystack_code') // Ensure it's a Paystack-supported bank
            ->orderBy('name')
            ->get();
    }

    /**
     * Sync banks from a specific provider to the Unified Database.
     */
    public function syncBanksFromProvider(string $providerSlug)
    {
        $provider = $this->resolveProvider($providerSlug);
        $banks = $provider->getBanks();

        $count = 0;
        foreach ($banks as $bank) {
            $existing = DB::table('unified_banks')->where('code', $bank['code'])->first();

            if (!$existing) {
                DB::table('unified_banks')->insert([
                    'name' => $bank['name'],
                    'code' => $bank['code'],
                    "{$providerSlug}_code" => $bank['code'],
                    'active' => $bank['active'] ?? true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $count++;
            }
            else {
                DB::table('unified_banks')->where('id', $existing->id)->update([
                    "{$providerSlug}_code" => $bank['code'],
                    'active' => $bank['active'] ?? true,
                    'updated_at' => now()
                ]);
            }
        }
        return $count;
    }

    /**
     * Helper to resolve generic bank code to provider specific code.
     */
    private function resolveBankCode(string $genericCode, string $providerSlug): string
    {
        $bank = DB::table('unified_banks')->where('code', $genericCode)->first();
        if ($bank && !empty($bank->{ "{$providerSlug}_code"})) {
            return $bank->{ "{$providerSlug}_code"};
        }
        return $genericCode;
    }
}