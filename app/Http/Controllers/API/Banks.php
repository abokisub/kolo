<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Banks extends Controller
{
    public function GetBanksArray(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->header('Origin');
        $authorization = $request->header('Authorization');
        if (!$origin || in_array($origin, $explode_url) || $origin === $request->getSchemeAndHttpHost() || config('app.habukhan_device_key') === $authorization) {
            if (!empty($request->id)) {
                $auth_user = DB::table('user')->where('status', 1)->where(function ($query) use ($request) {
                    $query->orWhere('id', $this->verifytoken($request->id))
                        ->orWhere('id', $this->verifyapptoken($request->id));
                })->first();

                $setting = $this->core();
                if (!$auth_user) {
                    return response()->json(['message' => 'Unable to singin user', 'status' => 'fail'], 403);
                }
                // Use dynamic charges from settings
                $monnify_charge = isset($setting->monnify_charge) ? $setting->monnify_charge : 20;
                $paystack_charge = isset($setting->paystack_charge) ? $setting->paystack_charge : 0;
                $paymentpoint_charge = isset($setting->paymentpoint_charge) ? $setting->paymentpoint_charge : 60;
                $xixapay_charge = isset($setting->xixapay_charge) ? $setting->xixapay_charge : 60;

                // Determine active PalmPay provider charge
                $habukhan_key = DB::table('habukhan_key')->first();
                // If PaymentPoint credentials exist, prioritize its charge for PalmPay entries
                $palmpay_charge = (!empty($habukhan_key->plive)) ? $paymentpoint_charge : $xixapay_charge;

                // Fetch settings to check enabled providers
                try {
                    $settings = DB::table('settings')->select(
                        'palmpay_enabled',
                        'monnify_enabled',
                        'wema_enabled',
                        'xixapay_enabled'
                    )->first();

                    $monnify_enabled = $settings->monnify_enabled ?? true;
                    $wema_enabled = $settings->wema_enabled ?? true;
                    $xixapay_enabled = $settings->xixapay_enabled ?? true;
                    $palmpay_enabled = $settings->palmpay_enabled ?? true;
                } catch (\Exception $e) {
                    $monnify_enabled = true;
                    $wema_enabled = true;
                    $xixapay_enabled = true;
                    $palmpay_enabled = true;
                }

                $banks_array = []; // Initialize array

                if (!is_null($auth_user->palmpay) && $palmpay_enabled) {
                    $banks_array[] = [
                        "name" => "PALMPAY",
                        "account" => $auth_user->palmpay,
                        "accountType" => $auth_user->palmpay === null,
                        'charges' => $palmpay_charge . ' NAIRA',
                    ];
                }
                // Only add Wema if Paystack account is NOT present (to avoid duplicates)
                if (!is_null($auth_user->wema) && $wema_enabled && is_null($auth_user->paystack_account)) {
                    $banks_array[] = [
                        "name" => "WEMA BANK",
                        "account" => $auth_user->wema,
                        "accountType" => $auth_user->wema === null,
                        'charges' => $monnify_charge . '%',
                    ];
                }
                if (!is_null($auth_user->paystack_account)) {
                    $banks_array[] = [
                        "name" => !empty($auth_user->paystack_bank) ? strtoupper($auth_user->paystack_bank) : "WEMA BANK",
                        "account" => $auth_user->paystack_account,
                        "accountType" => false,
                        'charges' => $paystack_charge . ' NAIRA',
                    ];
                }

                if (!is_null($auth_user->sb)) {
                    $banks_array[] = [
                        "name" => "GTBANK",
                        "account" => $auth_user->sb,
                        "accountType" => false,
                        'charges' => '50 NAIRA',
                    ];
                }

                if (!is_null($auth_user->sterlen) && $monnify_enabled) {
                    $banks_array[] = [
                        "name" => "MONIEPOINT",
                        "account" => $auth_user->sterlen,
                        "accountType" => false,
                        'charges' => $monnify_charge . '%',
                    ];
                }

                if (!is_null($auth_user->fed) && $monnify_enabled) {
                    $banks_array[] = [
                        "name" => "MONIEPOINT",
                        "account" => $auth_user->fed,
                        "accountType" => false,
                        'charges' => $monnify_charge . '%',
                    ];
                }

                if (!is_null($auth_user->opay) && $xixapay_enabled) {
                    $banks_array[] = [
                        "name" => "OPAY",
                        "account" => $auth_user->opay,
                        "accountType" => false,
                        'charges' => $paymentpoint_charge . ' NAIRA',
                    ];
                }

                if (!is_null($auth_user->rolex) && $xixapay_enabled) {
                    $banks_array[] = [
                        "name" => "FIDELITY/ROLEX",
                        "account" => $auth_user->rolex,
                        "accountType" => false,
                        'charges' => $monnify_charge . '%',
                    ];
                }

                if (!is_null($auth_user->pro)) {
                    $banks_array[] = [
                        "name" => "PROVIDUS",
                        "account" => $auth_user->pro,
                        "accountType" => false,
                        'charges' => $monnify_charge . ' NAIRA',
                    ];
                }

                if (!is_null($auth_user->safe)) {
                    $banks_array[] = [
                        "name" => "9PSB",
                        "account" => $auth_user->safe,
                        "accountType" => false,
                        'charges' => '50 NAIRA',
                    ];
                }

                return response()->json(['status' => 'success', 'banks' => $banks_array]);
            } else {
                return response()->json(['status' => 'fail', 'message' => 'Hey,Login To Continue'])->setStatusCode(403);
            }
        } else {
            return response()->json(['status' => 'fail', 'message' => 'Cannot Retrieve Banks'])->setStatusCode(403);
        }
    }


}