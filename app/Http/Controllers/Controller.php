<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function core()
    {
        $sets = DB::table('settings')->first();
        if ($sets) {
            $cardSets = DB::table('card_settings')->where('id', 1)->first();
            if ($cardSets) {
                // Map DB snake_case fields to frontend expected vcard_* fields
                $sets->vcard_ngn_fee = $cardSets->ngn_creation_fee;
                $sets->vcard_usd_fee = $cardSets->usd_creation_fee;
                $sets->vcard_usd_rate = $cardSets->ngn_rate;
                $sets->vcard_fund_fee = $cardSets->funding_fee_percent; // Legacy
                $sets->vcard_usd_failed_fee = $cardSets->usd_failed_tx_fee;
                $sets->vcard_ngn_fund_fee = $cardSets->ngn_funding_fee_percent;
                $sets->vcard_usd_fund_fee = $cardSets->usd_funding_fee_percent;
                $sets->vcard_ngn_failed_fee = $cardSets->ngn_failed_tx_fee;
            }
            return $sets;
        }
        return null;
    }

    public function habukhan_key()
    {
        $sets = DB::table('habukhan_key');
        if ($sets->count() == 1) {
            return $sets->first();
        } else {
            return null;
        }
    }

    public function autopilot_request($endpoint, $payload)
    {
        $key = str_replace(' ', '', $this->habukhan_key()->autopilot_key);
        // Determine if we should use test or live based on key prefix
        $baseUrl = 'https://autopilotng.com/api/live';
        if (str_starts_with($key, 'test_')) {
            $baseUrl = 'https://autopilotng.com/api/test';
        }

        // Log API key info (first 10 chars only for security)
        \Log::info('Autopilot Request', [
            'endpoint' => $endpoint,
            'baseUrl' => $baseUrl,
            'key_preview' => substr($key, 0, 10) . '...',
            'key_type' => str_starts_with($key, 'test_') ? 'TEST' : 'LIVE'
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($baseUrl . $endpoint, $payload);

        if (!$response->successful()) {
            \Log::error('Autopilot API Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'payload' => $payload,
                'response' => $response->body()
            ]);
        }

        return $response->json();
    }

    public function generateAutopilotReference()
    {
        $date = Carbon::now('Africa/Lagos')->format('YmdHi');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 15));
        return $date . $random; // 12 (date) + 15 (random) = 27 chars (min 25, max 30)
    }

    public function general()
    {
        $sets = DB::table('general');
        if ($sets->count() == 1) {
            return $sets->first();
        } else {
            return null;
        }
    }

    public function feature()
    {
        return DB::table('feature')->get();
    }


    public function updateData($data, $tablename, $tableid)
    {
        DB::table($tablename)
            ->where($tableid)
            ->update($data);
        return true;
    }


    public function generatetoken($req)
    {
        if (DB::table('user')->where('id', $req)->count() == 1) {
            $secure_key = bin2hex(random_bytes(32));
            DB::table('user')->where('id', $req)->update(['habukhan_key' => $secure_key]);
            return $secure_key;
        } else {
            return null;
        }
    }

    public function generateapptoken($key)
    {
        if (DB::table('user')->where('id', $key)->count() == 1) {
            $secure_key = bin2hex(random_bytes(32));
            DB::table('user')->where('id', $key)->update(['app_key' => $secure_key]);
            return $secure_key;
        } else {
            return null;
        }
    }
    public function verifyapptoken($key)
    {
        if (empty($key)) {
            return null;
        }

        // Strip Bearer prefix if present
        if (str_starts_with($key, 'Bearer ')) {
            $key = substr($key, 7);
        }

        $id = null;

        // 1. Check for Sanctum Token (ID|SECRET)
        if (strpos($key, '|') !== false) {
            $parts = explode('|', $key, 2);
            $tokenId = $parts[0];
            $tokenPlainText = $parts[1];

            // Safety: Handle URL encoded pipes if they sneak in
            if (strpos($tokenId, '%7C') !== false) {
                $parts = explode('%7C', $key, 2);
                $tokenId = $parts[0];
                $tokenPlainText = $parts[1];
            }

            $sanctumToken = DB::table('personal_access_tokens')
                ->where('id', $tokenId)
                ->first();

            if ($sanctumToken && hash_equals($sanctumToken->token, hash('sha256', $tokenPlainText))) {
                $id = $sanctumToken->tokenable_id;
            }

            // 1.5 Fallback for Legacy ID|KEY format
            if (!$id) {
                $key = $tokenPlainText; // Use only the secret part for legacy check
            }
        }

        // 2. Fallback to Legacy Columns
        if (!$id) {
            $check = DB::table('user')->where(function ($query) use ($key) {
                $query->where('app_key', $key)
                    ->orWhere('habukhan_key', $key)
                    ->orWhere('apikey', $key);
            })->first();

            if ($check) {
                $id = $check->id;
            }
        }

        return $id;
    }

    public function verifytoken($request)
    {
        if (DB::table('user')->where('habukhan_key', $request)->count() == 1) {
            $user = DB::table('user')->where('habukhan_key', $request)->first();
            return $user->id;
        } else {
            return null;
        }
    }


    public function generate_ref($title)
    {
        $code = random_int(100000, 999999);
        $me = random_int(1000, 9999);
        $app_name = config('app.name');
        $ref = "|$app_name|$title|$code|habukhan-dev-$me";
        return $ref;
    }
    public function purchase_ref($d)
    {
        return uniqid($d);
    }
    public function insert_stock($username)
    {
        $check_first = DB::table('wallet_funding')->where('username', $username);
        if ($check_first->count() == 0) {
            $values = array('username' => $username);
            DB::table('wallet_funding')->insert($values);
        }
    }
    public function inserting_data($table, $data)
    {
        return DB::table($table)->insert($data);
    }
    public function xixapay_account($username)
    {
        try {
            $check_first = DB::table('user')->where('username', $username);

            if ($check_first->count() == 1) {
                $get_user = $check_first->get()[0];

                // Cooldown check: Don't retry more than once every 10 minutes
                $cacheKey = "xixapay_sync_" . $get_user->id;
                if (\Cache::has($cacheKey)) {
                    return;
                }

                $xixa = config('services.xixapay');

                // Check if accounts are missing (PalmPay or Kolomoni)
                // Mapping: PalmPay (20867) -> palmpay column
                // Mapping: PalmPay (20867) -> palmpay column
                // Mapping: Kolomoni (20987) -> kolomoni_mfb column
                if (is_null($get_user->palmpay) || is_null($get_user->kolomoni_mfb)) {
                    \Log::info("Xixapay SYNC: Missing accounts for $username. PalmPay:" . ($get_user->palmpay ?? 'None') . ", Kolomoni MFB:" . ($get_user->kolomoni_mfb ?? 'None'));

                    $payload = [
                        'email' => $get_user->email,
                        'name' => $get_user->username,
                        'phoneNumber' => $get_user->phone,
                        'bankCode' => ['20867', '20987'],
                        'accountType' => 'static',
                        'businessId' => $xixa['business_id']
                    ];

                    if (!empty($get_user->bvn)) {
                        $payload['id_type'] = 'bvn';
                        $payload['id_number'] = $get_user->bvn;
                    } elseif (!empty($get_user->nin)) {
                        $payload['id_type'] = 'nin';
                        $payload['id_number'] = $get_user->nin;
                    }

                    $response = Http::timeout(25)->withHeaders([
                        'Authorization' => $xixa['authorization'],
                        'api-key' => $xixa['api_key']
                    ])->post('https://api.xixapay.com/api/v1/createVirtualAccount', $payload);

                    if ($response->successful()) {
                        $data = $response->json();
                        $updateData = [];

                        if (isset($data['bankAccounts'])) {
                            foreach ($data['bankAccounts'] as $bank) {
                                if ((string) $bank['bankCode'] === '20867' && is_null($get_user->palmpay)) {
                                    $updateData['palmpay'] = $bank['accountNumber'];
                                }
                                if ((string) $bank['bankCode'] === '20987' && is_null($get_user->kolomoni_mfb)) {
                                    $updateData['kolomoni_mfb'] = $bank['accountNumber'];
                                }
                            }
                        }

                        if (!empty($updateData)) {
                            DB::table('user')->where('id', $get_user->id)->update($updateData);
                            \Log::info("Xixapay SYNC: Accounts assigned for $username.");
                        }
                    } else {
                        \Log::warning("Xixapay SYNC FAILED for $username: " . $response->body());
                    }
                }

                // Set default 10min cooldown
                \Cache::put($cacheKey, 'attempted', 10);
            }
        } catch (\Exception $e) {
            \Log::error("Xixapay Error for $username: " . $e->getMessage());
        }
    }

    public function monnify_account($username)
    {
        try {
            $user = DB::table('user')->where('username', $username)->first();
            if (!$user) {
                \Log::error("Monnify: User $username not found");
                return;
            }

            // Cooldown check: Don't retry more than once every 10 minutes if failed or successful
            $cacheKey = "monnify_sync_" . $user->id;
            if (\Cache::has($cacheKey)) {
                return;
            }

            $keys = $this->habukhan_key();
            if (!$keys || empty($keys->mon_app_key) || empty($keys->mon_sk_key) || empty($keys->mon_con_num)) {
                \Log::error("Monnify: Keys missing in habukhan_key table for $username");
                return;
            }

            if (empty($user->paystack_account) || DB::table('user_bank')->where(['username' => $user->username, 'bank' => 'MONIEPOINT'])->count() == 0) {
                \Log::info("Monnify: Attempting to generate Reserved Accounts for user $username");

                // 1. Authenticate
                $authString = base64_encode($keys->mon_app_key . ':' . $keys->mon_sk_key);
                $authResponse = Http::timeout(10)->withOptions(['connect_timeout' => 5])->withHeaders([
                    'Authorization' => 'Basic ' . $authString
                ])->post('https://api.monnify.com/api/v1/auth/login');

                if ($authResponse->successful() && isset($authResponse->json()['responseBody']['accessToken'])) {
                    $accessToken = $authResponse->json()['responseBody']['accessToken'];
                    \Log::info("Monnify: Auth successful for $username");

                    // 2. Create Reserved Account
                    $ref = $keys->mon_con_num . $user->id; // Consistent ref per user
                    $payload = [
                        'accountReference' => $ref,
                        'accountName' => $user->name,
                        'currencyCode' => 'NGN',
                        'contractCode' => $keys->mon_con_num,
                        'customerEmail' => $user->email,
                        'customerName' => $user->name,
                        'getAllAvailableBanks' => true
                    ];

                    if (!empty($user->bvn)) {
                        $payload['customerBvn'] = $user->bvn;
                    } elseif (!empty($keys->mon_bvn)) {
                        $payload['customerBvn'] = $keys->mon_bvn;
                    }

                    $response = Http::timeout(10)->withOptions(['connect_timeout' => 5])->withToken($accessToken)
                        ->post('https://api.monnify.com/api/v1/bank-transfer/reserved-accounts', $payload);

                    $resBody = $response->json();

                    // Handle specific Monnify Limit Error: "You cannot reserve more than 100 account(s) for a customer"
                    if (!$response->successful() && isset($resBody['responseMessage']) && str_contains($resBody['responseMessage'], '100 account')) {
                        \Log::warning("Monnify: 100 Account Limit Reached for $username. Setting long cooldown.");
                        \Cache::put($cacheKey, 'limit_reached', 1440); // 24 hour cooldown for this specific error
                        return;
                    }

                    if ($response->successful()) {
                        $responseBody = $resBody['responseBody'];
                        $accounts = [];

                        if (isset($responseBody['accounts'])) {
                            $accounts = $responseBody['accounts'];
                        } elseif (isset($responseBody['accountNumber'])) {
                            $accounts[] = $responseBody;
                        }

                        if (!empty($accounts)) {
                            \Log::info("Monnify SYNC: Retreived accounts for $username.");
                            $updateData = [];

                            foreach ($accounts as $account) {
                                $bankName = strtoupper($account['bankName']);
                                $accountNumber = $account['accountNumber'];
                                $bankCode = $account['bankCode'];

                                if (strpos($bankName, 'MONIEPOINT') !== false) {
                                    // Monify Table (user_bank) for Moniepoint
                                    if (DB::table('user_bank')->where(['username' => $user->username, 'bank' => 'MONIEPOINT'])->count() == 0) {
                                        DB::table('user_bank')->insert([
                                            'username' => $user->username,
                                            'bank' => 'MONIEPOINT',
                                            'bank_name' => $user->name,
                                            'account_number' => $accountNumber,
                                            'bank_code' => $bankCode,
                                            'date' => $this->system_date()
                                        ]);
                                    }
                                } elseif (strpos($bankName, 'WEMA') !== false) {
                                    // Standardized Wema Field (paystack_account)
                                    if (empty($user->paystack_account)) {
                                        $updateData['paystack_account'] = $accountNumber;
                                    }
                                }
                            }

                            if (!empty($updateData)) {
                                DB::table('user')->where('id', $user->id)->update($updateData);
                                \Log::info("Monnify SYNC: Assigned accounts to $username.");
                            }
                        }
                    } else {
                        \Log::error("Monnify SYNC FAILED for $username: " . $response->body());
                    }
                } else {
                    \Log::error("Monnify: Auth failed for $username. Response: " . $authResponse->body());
                }

                // Set default 10min cooldown to avoid spamming API on reload
                \Cache::put($cacheKey, 'attempted', 10);
            }
        } catch (\Exception $e) {
            \Log::error("Monnify Error for $username: " . $e->getMessage());
        }
    }

    public function paymentpoint_account($username)
    {
        // DISABLED: As per user request to avoid slowdowns and duplicates
        return;
    }
    public function system_date()
    {
        return Carbon::now("Africa/Lagos")->toDateTimeString();
    }

    public function paystack_account($username)
    {
        try {
            $user = DB::table('user')->where('username', $username)->first();
            if (!$user) {
                \Log::error('Paystack: User not found for username: ' . $username);
                return false;
            }

            // Cooldown check: Don't retry more than once every 10 minutes
            $cacheKey = "paystack_sync_" . $user->id;
            if (\Cache::has($cacheKey)) {
                return false;
            }

            $habukhan_key = $this->habukhan_key();
            $paystack_secret = $habukhan_key->psk ?? config('app.paystack_secret_key');
            if (!$paystack_secret) {
                \Log::error('Paystack: Secret key missing for user: ' . $username);
                return false;
            }
            // Only create if not already assigned
            if ($user->paystack_account && $user->paystack_bank) {
                \Log::info('Paystack: Account already exists for user: ' . $username);
                return true;
            }
            // Step 1: Create customer if not exists
            $customerPayload = [
                'email' => $user->email,
                'first_name' => $user->username,
                'phone' => $user->phone,
            ];
            $customerResponse = Http::timeout(10)->withOptions(['connect_timeout' => 5])->withToken($paystack_secret)
                ->post('https://api.paystack.co/customer', $customerPayload);
            \Log::info('Paystack: Customer API response for user ' . $username . ': ' . json_encode($customerResponse->json()));
            if ($customerResponse->successful() && isset($customerResponse['data']['customer_code'])) {
                $customer_code = $customerResponse['data']['customer_code'];
            } elseif (isset($customerResponse['data']['customer_code'])) {
                $customer_code = $customerResponse['data']['customer_code'];
            } else {
                \Log::error('Paystack: Failed to create/find customer for user: ' . $username . '. Response: ' . $customerResponse->body());
                return false;
            }

            // Parse Name for validation and account creation
            $full_name = isset($user->name) && $user->name ? $user->name : $user->username;
            $name_parts = preg_split('/\s+/', trim($full_name));
            $first_name = $name_parts[0];
            $last_name = count($name_parts) > 1 ? $name_parts[count($name_parts) - 1] : $name_parts[0];

            // Step 1.5: Validate customer only if BVN is available
            $bvn_to_use = !empty($user->bvn) ? $user->bvn : ($habukhan_key->psk_bvn ?? $habukhan_key->mon_bvn);
            if (!empty($bvn_to_use) && strlen($bvn_to_use) >= 11) {
                $validatePayload = [
                    'country' => 'NG',
                    'type' => 'bvn',
                    'value' => $bvn_to_use,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                ];
                $validateResponse = Http::timeout(10)->withOptions(['connect_timeout' => 5])->withToken($paystack_secret)
                    ->post("https://api.paystack.co/customer/{$customer_code}/identification", $validatePayload);
                \Log::info("Paystack: Customer Validation Status for {$username}: " . $validateResponse->status() . " Response: " . $validateResponse->body());
            }

            // Step 2: Create dedicated account
            $phone = $user->phone;
            $accountPayload = [
                'customer' => $customer_code,
                'preferred_bank' => 'wema-bank',
                'first_name' => $first_name,
                'last_name' => $last_name,
                'phone' => $phone,
            ];
            $accountResponse = Http::timeout(10)->withOptions(['connect_timeout' => 5])->withToken($paystack_secret)
                ->post('https://api.paystack.co/dedicated_account', $accountPayload);
            \Log::info('Paystack: Dedicated Account API Status: ' . $accountResponse->status() . ' Response: ' . json_encode($accountResponse->json()));
            if ($accountResponse->successful() && isset($accountResponse['data']['account_number'])) {
                $acc = $accountResponse['data'];
                DB::table('user')->where('id', $user->id)->update([
                    'paystack_account' => $acc['account_number'],
                    'paystack_bank' => $acc['bank']['name'] ?? 'Paystack',
                ]);
                \Log::info('Paystack SYNC: Account assigned for user: ' . $username);
                return true;
            } else {
                \Log::error('Paystack SYNC FAILED for user: ' . $username . '. Response: ' . $accountResponse->body());
            }

            // Set default 10min cooldown
            \Cache::put($cacheKey, 'attempted', 10);
        } catch (\Exception $e) {
            \Log::error("Paystack SYNC Exception for $username: " . $e->getMessage());
        }
        return false;
    }
}
