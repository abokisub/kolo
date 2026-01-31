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
     */
    public function resolveProvider(string $slug): BankingProviderInterface
    {
        return match (strtolower($slug)) {
            'xixapay' => new XixapayProvider(),
            'monnify' => new MonnifyProvider(),
            default => new PaystackProvider(),
        };
    }

    /**
     * Get the currently active primary transfer provider.
     */
    public function getActiveProvider(): BankingProviderInterface
    {
        $slug = DB::table('settings')->value('primary_transfer_provider') ?? 'paystack';

        // Handle 'smart_routing' or other logic if needed, simplify to mapped provider for now
        if ($slug === 'smart_routing') {
            // Pick first unlocked provider
            $provider = DB::table('transfer_providers')->where('is_locked', 0)->orderBy('priority')->first();
            $slug = $provider ? $provider->slug : 'paystack';
        }

        return $this->resolveProvider($slug);
    }

    /**
     * Verify an account number with Smart Failover.
     * 
     * Logic:
     * 1. Try Primary Provider.
     * 2. If Primary fails (Exception), try Backup Provider (Paystack).
     * 3. This resolves the Xixapay Timeout issue transparently.
     */
    public function verifyAccount(string $accountNumber, string $bankCode): array
    {
        $primary = $this->getActiveProvider();

        try {
            // Need to map the generic bank code to the provider-specific code?
            // For now assuming the passed $bankCode is compatible or we use the Unified Table to resolve it.
            // Let's look up the provider-specific code from Unified Table if possible.
            $providerCode = $this->resolveBankCode($bankCode, $primary->getProviderSlug());

            return $primary->verifyAccount($accountNumber, $providerCode);

        } catch (\Exception $e) {
            Log::warning("BankingService: Primary Provider ({$primary->getProviderSlug()}) verification failed: " . $e->getMessage());

            // SMART FAILOVER: Try Paystack (Reference Implementation)
            if ($primary->getProviderSlug() !== 'paystack') {
                try {
                    $backup = new PaystackProvider();
                    $backupCode = $this->resolveBankCode($bankCode, 'paystack');

                    Log::info("BankingService: Failing over to Paystack for verification.");
                    return $backup->verifyAccount($accountNumber, $backupCode);
                } catch (\Exception $ex) {
                    Log::error("BankingService: Backup Provider (Paystack) verification also failed: " . $ex->getMessage());
                }
            }
            throw $e; // Re-throw original exception if backup fails
        }
    }

    /**
     * Initiate a transfer using the Primary Provider.
     * Includes BALANCE GUARD and STRICT FAILOVER rules.
     */
    public function transfer(array $details): array
    {
        $primary = $this->getActiveProvider();

        // --- BALANCE GUARD ---
        // Check if provider has enough balance
        try {
            $balance = $primary->getBalance();
            if ($balance < $details['amount']) {
                // Low Balance: Log and SWITCH to backup?
                Log::warning("BankingService: Primary Provider ({$primary->getProviderSlug()}) has insufficient balance (₦$balance). Request Amount: ₦{$details['amount']}");

                // Optional: Failover to backup if allowed by settings
                // For now, fail safely to avoid sticking logic
                return [
                    'status' => 'fail',
                    'message' => 'Service temporarily unavailable (Low Liquidity). Please try again later.'
                ];
            }
        } catch (\Exception $e) {
            // Balance check failed (network/auth)? Log but proceed cautiously or fail?
            Log::warning("BankingService: Could not check provider balance: " . $e->getMessage());
            // Proceeding... assuming it might work.
        }

        // Resolve bank code for this provider
        $details['bank_code'] = $this->resolveBankCode($details['bank_code'], $primary->getProviderSlug());

        try {
            return $primary->transfer($details);
        } catch (\Exception $e) {
            // CRITICAL: Do NOT auto-retry different provider on generic exception without checking nature of error.
            // If timeout -> Status is UNKNOWN. Do NOT retry.
            // If 500 -> Status is UNKNOWN. Do NOT retry.
            // If 400 (Bad Request) -> Failed. Safe to retry? Maybe. But explicit is better.

            Log::error("BankingService: Transfer Error ({$primary->getProviderSlug()}): " . $e->getMessage());

            return [
                'status' => 'fail', // or 'pending' if timeout?
                'message' => 'Transfer failed. Please check status later. ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get list of supported banks from the Unified Database.
     */
    public function getSupportedBanks()
    {
        return DB::table('unified_banks')
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Sync banks from a specific provider to the Unified Database.
     * This should be run via Artisan Command or Scheduler.
     */
    public function syncBanksFromProvider(string $providerSlug)
    {
        $provider = $this->resolveProvider($providerSlug);
        $banks = $provider->getBanks();

        $count = 0;
        foreach ($banks as $bank) {
            // Logic: Match by Code (if standard CBN) or Name
            // Prefer Code.

            $existing = DB::table('unified_banks')->where('code', $bank['code'])->first();

            if (!$existing) {
                // Try fuzzy name match?
                // For safety, let's stick to code creation or update
                DB::table('unified_banks')->insert([
                    'name' => $bank['name'],
                    'code' => $bank['code'], // Internal/CBN Code
                    "{$providerSlug}_code" => $bank['code'], // Provider specific code
                    'active' => $bank['active'] ?? true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $count++;
            } else {
                // Update the provider specific code AND the active status
                // This handles if CBN revokes a bank (Paystack will mark active=false)
                DB::table('unified_banks')->where('id', $existing->id)->update([
                    "{$providerSlug}_code" => $bank['code'],
                    'active' => $bank['active'] ?? true, // Auto-Deactivate if provider says so
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
        // Look up in unified_banks table
        $bank = DB::table('unified_banks')->where('code', $genericCode)->first();

        if ($bank && !empty($bank->{"{$providerSlug}_code"})) {
            return $bank->{"{$providerSlug}_code"};
        }

        // Fallback: Return original code (Assume it matches)
        return $genericCode;
    }
}
