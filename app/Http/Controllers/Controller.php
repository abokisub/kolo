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
        $sets = DB::table('settings');
        if ($sets->count() == 1) {
            return $sets->first();
        } else {
            return null;
        }
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
        $check = DB::table('user')->where(function ($query) use ($key) {
            $query->where('app_key', $key)
                ->orWhere('habukhan_key', $key)
                ->orWhere('apikey', $key);
        });

        if ($check->count() == 1) {
            $user = $check->first();
            return $user->id;
        } else {
            return null;
        }
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
            $this_Controller = new Controller;
            $check_first = DB::table('user')->where('username', $username);
            if ($check_first->count() == 1) {
                $get_user = $check_first->get()[0];
                $setting = $this_Controller->core();
                $habukhan_key = $this_Controller->habukhan_key();

                if (is_null($get_user->palmpay)) {
                    \Log::info("Xixapay: Attempting to generate OPay account for user $username");
                    $xixa = config('services.xixapay');
                    $response = Http::timeout(10)->withOptions(['connect_timeout' => 5])->withHeaders([
                        'Authorization' => $xixa['authorization'],
                        'api-key' => $xixa['api_key']
                    ])->post('https://api.xixapay.com/api/v1/createVirtualAccount', [
                                'email' => $get_user->email,
                                'name' => $get_user->username,
                                'phoneNumber' => $get_user->phone,
                                'bankCode' => ['20867'],
                                'businessId' => $xixa['business_id']
                            ]);
                    $responseData = $response->json();
                    \Log::info("Xixapay: Response for $username: " . json_encode($responseData));
                    file_put_contents('response_h.json', json_encode($responseData));
                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['bankAccounts'])) {
                            foreach ($data['bankAccounts'] as $bank) {
                                if ($bank['bankCode'] == '20867') {
                                    DB::table('user')->where('id', $get_user->id)->update(['palmpay' => $bank['accountNumber']]);
                                }
                            }
                        }
                    }
                }
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

            $keys = $this->habukhan_key();
            if (!$keys || empty($keys->mon_app_key) || empty($keys->mon_sk_key) || empty($keys->mon_con_num)) {
                \Log::error("Monnify: Keys missing in habukhan_key table for $username");
                return;
            }

            if (empty($user->wema) || empty($user->sterlen)) {
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
                    \Log::info("Monnify: Create Account Status: " . $response->status());

                    if ($response->successful()) {
                        $responseBody = $response->json()['responseBody'];
                        $accounts = [];

                        // Handle both single account and array of accounts
                        if (isset($responseBody['accounts'])) {
                            $accounts = $responseBody['accounts'];
                        } elseif (isset($responseBody['accountNumber'])) {
                            $accounts[] = $responseBody;
                        }

                        if (!empty($accounts)) {
                            \Log::info("Monnify: Accounts retrieved for $username: " . json_encode($accounts));
                            $updateData = [];

                            foreach ($accounts as $account) {
                                $bankName = strtoupper($account['bankName']);
                                $accountNumber = $account['accountNumber'];

                                if (strpos($bankName, 'WEMA') !== false) {
                                    $updateData['wema'] = $accountNumber;
                                } elseif (strpos($bankName, 'STERLING') !== false) {
                                    $updateData['sterlen'] = $accountNumber;
                                } elseif (strpos($bankName, 'FIDELITY') !== false || strpos($bankName, 'ROLEX') !== false) {
                                    $updateData['rolex'] = $accountNumber;
                                } elseif (strpos($bankName, 'MONIEPOINT') !== false) {
                                    // Map Moniepoint to 'sterlen' for correct "Moniepoint" label in AuthController
                                    $updateData['sterlen'] = $accountNumber;
                                } else {
                                    // Default generic monnify field if no specific match
                                    if (!isset($updateData['fed'])) {
                                        $updateData['fed'] = $accountNumber;
                                    }
                                }
                            }

                            if (!empty($updateData)) {
                                DB::table('user')->where('id', $user->id)->update($updateData);
                            }
                        } else {
                            \Log::error("Monnify: No accounts found in response for $username. Response: " . $response->body());
                        }
                    } else {
                        \Log::error("Monnify: Failed to create reserved account for $username. Response: " . $response->body());
                    }
                } else {
                    \Log::error("Monnify: Auth failed for $username. Response: " . $authResponse->body());
                }
            }
        } catch (\Exception $e) {
            \Log::error("Monnify Error for $username: " . $e->getMessage());
        }
    }

    public function paymentpoint_account($username)
    {
        // try {
        //     $check_first = DB::table('user')->where('username', $username);
        //     if ($check_first->count() == 1) {
        //         $get_user = $check_first->get()[0];
        //         // Provision only if at least one account is missing
        //         if (is_null($get_user->palmpay) || is_null($get_user->opay)) {
        //             $response = Http::timeout(10)->withOptions(['connect_timeout' => 5])->withHeaders([
        //                 // 'Authorization' => 'Bearer de6fa807e97867a89055958086bef7b13ba16ef1905a291443f682580e7414ab64f8ab9afd0e2d6512a5a9ed6d886272fb2fcc01e0d31d40a9486bca',
        //                 // 'api-key' => '812c9e04c7760e4389a1e013d09fd4e5a8537358'
        //             ])->post('https://api.paymentpoint.co/api/v1/createVirtualAccount', [
        //                         'email' => $get_user->email,
        //                         'name' => $get_user->username,
        //                         'phoneNumber' => $get_user->phone,
        //                         'bankCode' => ['20946', '20897'],
        //                         // 'businessId' => '06735513118eab2bcbaef7b90c8422328e121fcf'
        //                     ]);

        //             if ($response->successful()) {
        //                 $data = $response->json();
        //                 if (isset($data['bankAccounts'])) {
        //                     $update = [];
        //                     foreach ($data['bankAccounts'] as $bank) {
        //                         if ($bank['bankCode'] == '20946') {
        //                             $update['palmpay'] = $bank['accountNumber'];
        //                         }
        //                         if ($bank['bankCode'] == '20897') {
        //                             $update['opay'] = $bank['accountNumber'];
        //                         }
        //                     }
        //                     if (!empty($update)) {
        //                         DB::table('user')->where('id', $get_user->id)->update($update);
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // } catch (\Exception $e) {
        //     \Log::error("PaymentPoint Error for $username: " . $e->getMessage());
        // }
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
                \Log::info('Paystack: Account assigned for user: ' . $username);
                return true;
            } else {
                \Log::error('Paystack: Failed to assign account for user: ' . $username . '. Response: ' . $accountResponse->body());
            }
        } catch (\Exception $e) {
            \Log::error("Paystack Error for $username: " . $e->getMessage());
        }
        return false;
    }
}
