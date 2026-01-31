<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Services\TransferRouter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TransferPurchase extends Controller
{
    protected $transferRouter;

    public function __construct(TransferRouter $transferRouter)
    {
        $this->transferRouter = $transferRouter;
    }

    public function TransferRequest(Request $request, $id = null)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $authHeader = $request->header('Authorization');
        $deviceKey = config('app.habukhan_device_key');

        // --- AUTHENTICATION LOGIC ---
        // Recognizing App regardless of whether it sends raw Device Key or Bearer Token (interceptor adds X-Device-Key)
        if ($deviceKey == $authHeader || $request->header('X-Device-Key') == $deviceKey || str_starts_with($authHeader ?? '', 'Bearer ')) {
            // APP AUTH

            // If user_id is missing in request but id is present in route/URL (Mobile App Route)
            if (!$request->has('user_id') && $id) {
                $request->merge(['user_id' => $id]);
            }

            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|gt:0',
                'account_number' => 'required|numeric|digits:10',
                'bank_code' => 'required',
                'account_name' => 'required',
                'user_id' => 'required', // App sends user_id or token in URL
                'pin' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'fail', 'message' => $validator->errors()->first()])->setStatusCode(400);
            }

            $transid = $this->purchase_ref('TF_'); // Transfer Reference

            $verified_id = $this->verifyapptoken($request->user_id);
            $check = DB::table('user')->where(['id' => $verified_id, 'status' => 1]);

            if ($check->count() == 1) {
                $user = $check->first();
                if (trim($user->pin) == trim($request->pin)) {
                    $accessToken = $user->apikey;
                } else {
                    return response()->json(['status' => 'fail', 'message' => 'Invalid Transaction Pin'])->setStatusCode(403);
                }
            } else {
                return response()->json(['status' => 'fail', 'message' => 'User not found or blocked'])->setStatusCode(403);
            }

            $system = "APP";

        } else if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            // WEB AUTH
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|gt:0',
                'account_number' => 'required|numeric|digits:10',
                'bank_code' => 'required',
                'account_name' => 'required',
                'token' => 'required', // Web sends token
                'pin' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'fail', 'message' => $validator->errors()->first()])->setStatusCode(400);
            }

            $transid = $this->purchase_ref('TF_');
            $accessToken = null;

            if ($this->core()->allow_pin == 1) {
                $check = DB::table('user')->where(['id' => $this->verifytoken($request->token)]);
                if ($check->count() == 1) {
                    $user = $check->first();
                    if (trim($user->pin) == trim($request->pin)) {
                        $accessToken = $user->apikey;
                    } else {
                        return response()->json(['status' => 'fail', 'message' => 'Invalid Transaction Pin'])->setStatusCode(403);
                    }
                } else {
                    return response()->json(['status' => 'fail', 'message' => 'User not found'])->setStatusCode(403);
                }
            } else {
                // Pin not required config
                $check = DB::table('user')->where(['id' => $this->verifytoken($request->token)]);
                if ($check->count() == 1) {
                    $user = $check->first();
                    $accessToken = $user->apikey;
                }
            }
            $system = config('app.name');

        } else {
            // API AUTH
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|gt:0',
                'account_number' => 'required|numeric|digits:10',
                'bank_code' => 'required',
                'account_name' => 'required',
                'request-id' => 'required|unique:transfers,reference'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'fail', 'message' => $validator->errors()->first()])->setStatusCode(400);
            }

            $transid = $request->input('request-id');
            $d_token = $request->header('Authorization');
            $accessToken = trim(str_replace("Token", "", $d_token));

            $check = DB::table('user')->where(function ($query) use ($accessToken) {
                $query->where('apikey', $accessToken)
                    ->orWhere('app_key', $accessToken)
                    ->orWhere('habukhan_key', $accessToken);
            })->where('status', 1);

            if ($check->count() == 1) {
                $user = $check->first();
            } else {
                return response()->json(['status' => 'fail', 'message' => 'Invalid Authorization Token'])->setStatusCode(403);
            }
            $system = "API";
        }

        if (!isset($user) || !$user) {
            return response()->json(['status' => 'fail', 'message' => 'Authentication Failed'])->setStatusCode(403);
        }

        // 1. Calculate Charges (Prepare Data OUTSIDE transaction if possible to minimize lock time)
        $amount = $request->amount;

        // --- PROCESSING LOGIC ---

        // --- CRITICAL SECURITY: WRAP IN TRANSACTION & LOCK ROW ---
        // This prevents race conditions and guarantee atomic updates.

        try {
            // 1. DEDUCT BALANCE AND RECORD PENDING TRANSACTION
            $transactionResult = DB::transaction(function () use ($request, $user, $system, $amount, $transid) {
                // RE-FETCH USER WITH PESSIMISTIC LOCK
                $lockedUser = DB::table('user')->where('id', $user->id)->lockForUpdate()->first();

                $charge = $this->calculateTransferCharge($amount);
                $total_deduction = $amount + $charge;

                if ($lockedUser->bal < $total_deduction) {
                    return ['status' => 'fail', 'message' => 'Insufficient Funds', 'code' => 403];
                }

                // Deduct Balance
                $new_wallet = $lockedUser->bal - $total_deduction;
                DB::table('user')->where('id', $lockedUser->id)->update(['bal' => $new_wallet]);

                // Record Transaction (PENDING)
                DB::table('transfers')->insert([
                    'user_id' => $lockedUser->id,
                    'reference' => $transid,
                    'amount' => $amount,
                    'charge' => $charge,
                    'bank_code' => $request->bank_code,
                    'account_number' => $request->account_number,
                    'account_name' => $request->account_name,
                    'narration' => $request->narration ?? 'Transfer',
                    'status' => 'PENDING',
                    'oldbal' => $lockedUser->bal,
                    'newbal' => $new_wallet,
                    'system' => $system,
                    'created_at' => Carbon::now("Africa/Lagos"),
                    'updated_at' => Carbon::now("Africa/Lagos")
                ]);

                // Log to message table
                DB::table('message')->insert([
                    'username' => $lockedUser->username,
                    'amount' => $total_deduction,
                    'message' => 'Bank Transfer to ' . $request->account_name . ' (' . $request->account_number . ') - ' . ($request->narration ?? 'Transfer'),
                    'phone_account' => $request->account_number,
                    'oldbal' => $lockedUser->bal,
                    'newbal' => $new_wallet,
                    'habukhan_date' => $this->system_date(),
                    'plan_status' => 2, // Processing
                    'transid' => $transid,
                    'role' => 'transfer'
                ]);

                return ['status' => 'success', 'user_id' => $lockedUser->id, 'total_deduction' => $total_deduction];
            });

            if ($transactionResult['status'] !== 'success') {
                return response()->json(['status' => 'fail', 'message' => $transactionResult['message']])->setStatusCode($transactionResult['code']);
            }

            // 2. CALL ROUTER (OUTSIDE TRANSACTION - LOCK RELEASED)
            $transferDetails = [
                'amount' => $amount,
                'bank_code' => $request->bank_code,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'narration' => $request->narration ?? 'Transfer',
                'reference' => $transid
            ];

            try {
                $routerResponse = $this->transferRouter->processTransfer($transferDetails);

                if ($routerResponse['status'] == 'success' || $routerResponse['status'] == 'pending') {
                    $finalStatus = ($routerResponse['status'] == 'success') ? 'SUCCESS' : 'PENDING';

                    DB::table('transfers')->where('reference', $transid)->update([
                        'status' => $finalStatus,
                        'updated_at' => Carbon::now("Africa/Lagos")
                    ]);

                    DB::table('message')->where('transid', $transid)->update([
                        'plan_status' => 1,
                        'message' => 'Successfully transferred ₦' . number_format($amount) . ' to ' . $request->account_name . ' (' . $request->account_number . ')'
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Transfer Successful',
                        'reference' => $transid
                    ]);

                } else {
                    throw new \Exception($routerResponse['message'] ?? 'Provider Error');
                }

            } catch (\Exception $e) {
                // 3. REFUND ON FAILURE
                Log::error("TransferPurchase: API Call Failed, refunding user. Error: " . $e->getMessage());

                DB::transaction(function () use ($transactionResult, $transid, $e) {
                    $u = DB::table('user')->where('id', $transactionResult['user_id'])->lockForUpdate()->first();
                    $new_bal = $u->bal + $transactionResult['total_deduction'];
                    DB::table('user')->where('id', $u->id)->update(['bal' => $new_bal]);

                    DB::table('transfers')->where('reference', $transid)->update([
                        'status' => 'FAILED',
                        'updated_at' => Carbon::now("Africa/Lagos")
                    ]);

                    DB::table('message')->where('transid', $transid)->update([
                        'plan_status' => 0, // Failed
                        'message' => 'Transfer FAILED and Refunded. Reason: ' . substr($e->getMessage(), 0, 100),
                        'newbal' => $new_bal
                    ]);
                });

                return response()->json([
                    'status' => 'fail',
                    'message' => 'Transfer failed: ' . $e->getMessage() . '. Funds have been returned to your wallet.'
                ])->setStatusCode(400);
            }

        } catch (\Exception $e) {
            Log::error('TransferRequest Exception: ' . $e->getMessage());
            return response()->json(['status' => 'fail', 'message' => 'Internal Server Error'])->setStatusCode(500);
        }

    }

    private function calculateTransferCharge($amount)
    {
        $settings = $this->core();
        $type = $settings->transfer_charge_type ?? 'FLAT';
        $value = $settings->transfer_charge_value ?? 0;
        $cap = $settings->transfer_charge_cap ?? 0;

        if ($type == 'PERCENTAGE') {
            $charge = ($amount / 100) * $value;
            // Apply cap if it's set and charge exceeds it
            if ($cap > 0 && $charge > $cap) {
                $charge = $cap;
            }
            return $charge;
        }

        // Default to Flat Fee
        return $value;
    }
}
