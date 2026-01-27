<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    public function Simserver(Request $request)
    {
        if ($request->status and $request->user_reference and $request->true_response) {
            if (DB::table('data')->where(['transid' => $request->status])->count() == 1) {
                $trans = DB::table('data')->where(['transid' => $request->user_reference])->first();
                $user = DB::table('user')->where(['username' => $trans->username, 'status' => 1])->first();
                if ($request->status == 'Done') {
                    $status = 'success';
                    DB::table('data')->where(['transid' => $trans->transid])->update(['plan_status' => 1, 'api_response' => $request->true_response]);
                    DB::table('message')->where(['transid' => $trans->transid])->update(['plan_status' => 1, 'message' => $request->true_response]);
                } else {
                    if ($trans->plan_status !== 2) {

                        if (strtolower($trans->wallet) == 'wallet') {
                            DB::table('user')->where('username', $trans->username)->update(['bal' => $user->bal + $trans->amount]);
                            $user_balance = $user->bal;
                        } else {
                            $wallet_bal = strtolower($trans->wallet) . "_bal";
                            $b = DB::table('wallet_funding')->where(['username' => $trans->username])->first();
                            $user_balance = $b->$wallet_bal;
                            DB::table('wallet_funding')->where('username', $trans->username)->update([$wallet_bal => $user_balance + $trans->amount]);
                        }



                        $status = "fail";
                        DB::table('data')->where(['transid' => $trans->transid])->update(['plan_status' => 2, 'api_response' => $request->true_response, 'oldbal' => $user_balance, 'newbal' => $user_balance + $trans->amount]);
                        DB::table('message')->where(['transid' => $trans->transid])->update(['plan_status' => 2, 'message' => $request->true_response, 'oldbal' => $user_balance, 'newbal' => $user_balance + $trans->amount]);
                    }
                }
                if ($status) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $user->webhook);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['status' => $status, 'request-id' => $trans->transid, 'response' => $request->true_response]));  //Post Fields
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($ch);
                    curl_close($ch);
                }
            }
        } else {
            return ['status' => 'fail'];
        }
    }
    public function HabukhanWebhook()
    {
        $response = json_decode(file_get_contents("php://input"), true);
        if ((isset($response['status'])) and (isset($response['request-id'])) and isset($response['response'])) {

            if (DB::table('data')->where(['transid' => $response['request-id']])->count() == 1) {
                $trans = DB::table('data')->where(['transid' => $response['request-id']])->first();
                $user = DB::table('user')->where(['username' => $trans->username, 'status' => 1])->first();

                if ($response['status'] == 'success') {
                    $status = "success";
                    DB::table('data')->where(['transid' => $trans->transid])->update(['plan_status' => 1, 'api_response' => $response['response']]);
                    DB::table('message')->where(['transid' => $trans->transid])->update(['plan_status' => 1, 'message' => $response['response']]);
                } else {
                    if ($trans->plan_status !== 2) {
                        $status = "fail";

                        if (strtolower($trans->wallet) == 'wallet') {
                            $user_balance = $user->bal;
                            DB::table('user')->where('username', $trans->username)->update(['bal' => $user->bal + $trans->amount]);
                        } else {
                            $wallet_bal = strtolower($trans->wallet) . "_bal";
                            $b = DB::table('wallet_funding')->where(['username' => $trans->username])->first();
                            $user_balance = $b->$wallet_bal;
                            DB::table('wallet_funding')->where('username', $trans->username)->update([$wallet_bal => $user_balance + $trans->amount]);
                        }


                        DB::table('data')->where(['transid' => $trans->transid])->update(['plan_status' => 2, 'api_response' => $response['response'], 'oldbal' => $user_balance, 'newbal' => $user_balance + $trans->amount]);
                        DB::table('message')->where(['transid' => $trans->transid])->update(['plan_status' => 2, 'message' => $response['response'], 'oldbal' => $user_balance, 'newbal' => $user_balance + $trans->amount]);
                    }
                }
                if ($status) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $user->webhook);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['status' => $status, 'request-id' => $trans->transid, 'response' => $response['response']]));  //Post Fields
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($ch);
                    curl_close($ch);
                }
            }
        }
    }

    public function MegasubWebhook()
    {
        $response = json_decode(file_get_contents("php://input"), true);
        if ($response['status'] and $response['id'] and $response['msg']) {
            if (
                DB::table('data')->where(['mega_trans' => $response['id']])->where(function ($query) {
                    $query->where('plan_status', 1)->orwhere('plan_status', 0);
                })->count() == 1
            ) {
                $trans = DB::table('data')->where(['mega_trans' => $response['id']])->first();
                $user = DB::table('user')->where(['username' => $trans->username, 'status' => 1])->first();
                if ($response['status'] == 'success') {
                    $status = "success";
                    DB::table('data')->where(['transid' => $trans->transid])->update(['plan_status' => 1, 'api_response' => $response['msg']]);
                    DB::table('message')->where(['transid' => $trans->transid])->update(['plan_status' => 1, 'message' => $response['msg']]);
                } else {
                    if ($trans->plan_status !== 2) {
                        if (strtolower($trans->wallet) == 'wallet') {
                            DB::table('user')->where('username', $trans->username)->update(['bal' => $user->bal + $trans->amount]);
                            $user_balance = $user->bal;
                        } else {
                            $wallet_bal = strtolower($trans->wallet) . "_bal";
                            $b = DB::table('wallet_funding')->where(['username' => $trans->username])->first();
                            $user_balance = $b->$wallet_bal;
                            DB::table('wallet_funding')->where('username', $trans->username)->update([$wallet_bal => $user_balance + $trans->amount]);
                        }
                        $status = "fail";
                        DB::table('data')->where(['transid' => $trans->transid])->update(['plan_status' => 2, 'api_response' => $response['msg'], 'oldbal' => $user_balance, 'newbal' => $user_balance + $trans->amount]);
                        DB::table('message')->where(['transid' => $trans->transid])->update(['plan_status' => 2, 'message' => $response['msg'], 'oldbal' => $user_balance, 'newbal' => $user_balance + $trans->amount]);
                    }
                }
                if ($status) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $user->webhook);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['status' => $status, 'request-id' => $trans->transid, 'response' => $response['msg']]));  //Post Fields
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($ch);
                    curl_close($ch);
                }
            }
        }
    }

    public function AutopilotWebhook(Request $request)
    {
        $response = $request->all();
        \Log::info('📞 Autopilot Webhook Received:', $response);

        // Autopilot sometimes sends 'status' or 'request_status' or 'data.status'
        $status_text = strtolower($response['status'] ?? $response['request_status'] ?? ($response['data']['status'] ?? ''));
        $reference = $response['reference'] ?? ($response['data']['reference'] ?? ($response['yourReference'] ?? ''));

        if (!$reference || !$status_text) {
            \Log::warning('📞 Autopilot Webhook: Missing reference or status', $response);
            return response()->json(['status' => 'ignored', 'reason' => 'missing fields']);
        }

        // Search in cash table by api_reference or custom reference
        $cash = DB::table('cash')->where('api_reference', $reference)->orWhere('transid', $reference)->first();

        if ($cash) {
            $user = DB::table('user')->where('username', $cash->username)->first();
            if (!$user) {
                \Log::error('📞 Autopilot Webhook: User not found for conversion', ['username' => $cash->username]);
                return response()->json(['status' => 'fail', 'message' => 'User not found']);
            }

            if ($status_text == 'completed' || $status_text == 'success' || $status_text == 'delivered') {
                if ($cash->plan_status == 0) { // Still pending
                    $new_bal = $user->bal + $cash->amount_credit;

                    DB::beginTransaction();
                    try {
                        // Update transaction status
                        DB::table('cash')->where('id', $cash->id)->update([
                            'plan_status' => 1,
                            'oldbal' => $user->bal,
                            'newbal' => $new_bal
                        ]);

                        // Update user balance
                        DB::table('user')->where('id', $user->id)->update(['bal' => $new_bal]);

                        // Update corresponding message/history record
                        DB::table('message')->where('transid', $cash->transid)->update([
                            'plan_status' => 1,
                            'oldbal' => $user->bal,
                            'newbal' => $new_bal,
                            'message' => 'Airtime conversion successful and wallet credited'
                        ]);

                        DB::commit();
                        \Log::info('✅ Autopilot Webhook: Successfully credited user', ['username' => $user->username, 'amount' => $cash->amount_credit]);
                        return response()->json(['status' => 'success']);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        \Log::error('❌ Autopilot Webhook: DB Error during crediting', ['error' => $e->getMessage()]);
                        return response()->json(['status' => 'fail', 'message' => $e->getMessage()]);
                    }
                } else {
                    \Log::info('📞 Autopilot Webhook: Transaction already processed', ['transid' => $cash->transid]);
                    return response()->json(['status' => 'success', 'message' => 'already processed']);
                }
            } else if ($status_text == 'failed' || $status_text == 'cancelled' || $status_text == 'rejected') {
                if ($cash->plan_status == 0) {
                    DB::table('cash')->where('id', $cash->id)->update(['plan_status' => 2]);
                    DB::table('message')->where('transid', $cash->transid)->update([
                        'plan_status' => 2,
                        'message' => 'Airtime conversion failed: ' . ($response['message'] ?? $response['data']['message'] ?? 'Rejected by provider')
                    ]);
                    \Log::warning('❌ Autopilot Webhook: Conversion failed', ['reference' => $reference]);
                    return response()->json(['status' => 'success']);
                }
            }
        } else {
            \Log::warning('📞 Autopilot Webhook: No matching transaction found in database for reference: ' . $reference);
        }

        return response()->json(['status' => 'ignored']);
    }
}
