<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $explode_url = explode(',', env('HABUKHAN_APP_KEY'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $validator = validator::make($request->all(), [
                'name' => 'required|max:199|min:8',
                'email' => 'required|unique:user,email|max:255|email',
                'phone' => 'required|numeric|unique:user,phone|digits:11',
                'password' => 'required|min:8',
                'username' => 'required|unique:user,username|max:12|string|alpha_num',
                'pin' => 'required|numeric|digits:4'
            ], [
                'name.required' => 'Full Name is Required',
                'email.required' => 'E-mail is Required',
                'phone.required' => 'Phone Number Required',
                'password.required' => 'Password Required',
                'username.required' => 'Username Required',
                'username.unique' => 'Username already Taken',
                'phone.unique' => 'Phone Number already Taken',
                'username.max' => 'Username Maximum Length is 12 ' . $request->username,

                'email.unique' => 'Email Alreay Taken',
                'password.min' => 'Password Not Strong Enough',
                'name.min' => 'Invalid Full Name',
                'name.max' => 'Invalid Full Name',
                'phone.numeric' => 'Phone Number Must be Numeric ' . $request->phone,

                'pin.required' => 'Transaction Pin Required',
                'pin.numeric' => 'Transaction Pin Numeric',
                'pin.digits' => 'Transaction Pin Digits Must Be 4'
            ]);
            // checking referal user details
            if ($request->ref != null) {
                $check_ref = DB::table('user')
                    ->where('username', '=', $request->ref)
                    ->count();
            }
            if ($validator->fails()) {

                return response()->json([
                    'message' => $validator->errors()->first(),
                    'status' => 403
                ])->setStatusCode(403);
            } else if (substr($request->phone, 0, 1) != '0') {
                return response()->json([
                    'message' => 'Invalid Phone Number',
                    'status' => 403
                ])->setStatusCode(403);
            } else
                if ($request->ref != null && $check_ref == 0) {
                    return response()->json([
                        'message' => 'Invalid Referral Username You can Leave the referral Box Empty',
                        'status' => '403'
                    ])->setStatusCode(403);
                } else {
                    $user = new User();
                    $user->name = $request->name;
                    $user->username = $request->username;
                    $user->email = $request->email;
                    $user->phone = $request->phone;
                    $user->password = password_hash($request->password, PASSWORD_DEFAULT, array('cost' => 16));
                    // $user->password = Hash::make($request->password);
                    $user->apikey = bin2hex(openssl_random_pseudo_bytes(30));
                    $user->app_key = $user->apikey;
                    $user->bal = '0.00';
                    $user->refbal = '0.00';
                    $user->ref = $request->ref;
                    $user->type = 'SMART';
                    $user->date = Carbon::now("Africa/Lagos");
                    $user->kyc = '0';
                    $user->status = '0';
                    $user->user_limit = $this->habukhan_key()->default_limit;
                    $user->pin = $request->pin;
                    $user->save();
                    if ($user != null) {
                        $this->xixapay_account($user->username);
                        $this->monnify_account($user->username);
                        $this->paymentpoint_account($user->username);
                        $this->paystack_account($user->username);
                        $this->insert_stock($user->username);
                        $user = DB::table('user')->where(['id' => $user->id])->first();
                        $user_details = [
                            'username' => $user->username,
                            'name' => $user->name,
                            'phone' => $user->phone,
                            'email' => $user->email,
                            'bal' => number_format($user->bal, 2),
                            'refbal' => number_format($user->refbal, 2),
                            'kyc' => $user->kyc,
                            'type' => $user->type,
                            'pin' => $user->pin,
                            'profile_image' => $user->profile_image,
                            'sterlen' => $user->sterlen,
                            'vdf' => $user->vdf,
                            'fed' => $user->fed,
                            'wema' => $user->wema,
                            'opay' => $user->opay,
                            'rolex' => $user->rolex,
                            'palmpay' => $user->palmpay,
                            'paystack_account' => $user->paystack_account,
                            'paystack_bank' => $user->paystack_bank,
                            'address' => $user->address,
                            'webhook' => $user->webhook,
                            'about' => $user->about,
                            'apikey' => $user->apikey,
                            'is_bvn' => $user->bvn == null ? false : true
                        ];

                        $token = $this->generatetoken($user->id);
                        $use = $this->core();
                        $general = $this->general();
                        if ($use != null) {
                            if ($use->is_verify_email) {
                                $otp = random_int(100000, 999999);
                                $data = [
                                    'otp' => $otp
                                ];
                                $tableid = [
                                    'username' => $user->username
                                ];
                                $this->updateData($data, 'user', $tableid);
                                $email_data = [
                                    'name' => $user->name,
                                    'email' => $user->email,
                                    'username' => $user->username,
                                    'title' => 'Account Verification',
                                    'pin' => $user->pin,
                                    'sender_mail' => $general->app_email,
                                    'app_name' => env('APP_NAME'),
                                    'otp' => $otp
                                ];
                                MailController::send_mail($email_data, 'email.verify');
                                return response()->json([
                                    'status' => 'verify',
                                    'username' => $user->username,
                                    'token' => $token,
                                    'user' => $user_details
                                ]);
                            } else {
                                $data = [
                                    'status' => 1
                                ];
                                $tableid = [
                                    'username' => $user->username
                                ];
                                $this->updateData($data, 'user', $tableid);
                                $email_data = [
                                    'name' => $user->name,
                                    'email' => $user->email,
                                    'username' => $user->username,
                                    'title' => 'WELCOME EMAIL',
                                    'sender_mail' => $general->app_email,
                                    'system_email' => $general->app_email,
                                    'app_name' => $general->app_name,
                                    'pin' => $user->pin,
                                ];
                                MailController::send_mail($email_data, 'email.welcome');
                                return response()->json([
                                    'status' => 'success',
                                    'username' => $user->username,
                                    'token' => $token,
                                    'user' => $user_details
                                ]);
                            }
                        } else {
                            $data = [
                                'status' => 1,
                            ];
                            $tableid = [
                                'username' => $user->username
                            ];
                            $this->updateData($data, 'user', $tableid);
                            $email_data = [
                                'name' => $user->name,
                                'email' => $user->email,
                                'username' => $user->username,
                                'title' => 'WELCOME EMAIL',
                                'sender_mail' => $general->app_email,
                                'system_email' => $general->app_email,
                                'app_name' => $general->app_name,
                                'pin' => $user->pin,
                            ];
                            // FIX: Commented out to prevent timeout due to slow SMTP
                            // MailController::send_mail($email_data, 'email.welcome');
                            return response()->json([
                                'status' => 'success',
                                'username' => $user->username,
                                'token' => $token,
                                'user' => $user_details
                            ]);
                        }
                    } else {
                        return response()->json(
                            [
                                'status' => 403,
                                'message' => 'Unable to Register User Please Try Again Later',
                            ]
                        )->setStatusCode(403);
                    }
                }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function account(Request $request)
    {
        $explode_url = explode(',', env('HABUKHAN_APP_KEY'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $user_token = $request->id;
            $real_token = $this->verifytoken($user_token);
            if (!is_null($real_token)) {
                $habukhan_check = DB::table('user')->where('id', $real_token);
                if ($habukhan_check->count() == 1) {
                    $user = $habukhan_check->get()[0];
                    $this->xixapay_account($user->username);
                    $this->monnify_account($user->username);
                    $this->paymentpoint_account($user->username);
                    $this->paystack_account($user->username);
                    $this->insert_stock($user->username);
                    $user = DB::table('user')->where(['id' => $user->id])->first();
                    $user_details = [
                        'username' => $user->username,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'bal' => number_format($user->bal, 2),
                        'refbal' => number_format($user->refbal, 2),
                        'kyc' => $user->kyc,
                        'type' => $user->type,
                        'pin' => $user->pin,
                        'profile_image' => $user->profile_image,
                        'sterlen' => $user->sterlen,
                        'fed' => $user->fed,
                        'wema' => $user->wema,
                        'opay' => $user->opay,
                        'rolex' => $user->rolex,
                        'palmpay' => $user->palmpay,
                        'paystack_account' => $user->paystack_account,
                        'paystack_bank' => $user->paystack_bank,
                        'vdf' => $user->vdf,
                        'address' => $user->address,
                        'webhook' => $user->webhook,
                        'about' => $user->about,
                        'apikey' => $user->apikey,
                        'is_bvn' => $user->bvn == null ? false : true
                    ];

                    if ($user->status == 0) {
                        return response()->json([
                            'status' => 'verify',
                            'message' => 'Account Not Yet Verified',
                            'user' => $user_details
                        ]);
                    } else if ($user->status == 1) {
                        //set up the user over here


                        return response()->json([
                            'status' => 'success',
                            'message' => 'account verified',
                            'user' => $user_details
                        ]);
                    } else if ($user->status == '2') {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Account Banned'
                        ])->setStatusCode(403);
                    } elseif ($user->status == '3') {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Account Deactivated'
                        ])->setStatusCode(403);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Unable to Get User'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Allowed',
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'AccessToken Expired'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(env('ERROR_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System',
            ])->setStatusCode(403);
        }
    }
    public function verify(Request $request)
    {
        $explode_url = explode(',', env('HABUKHAN_APP_KEY'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $habukhan_check = DB::table('user')->where('email', $request->email);
            if ($habukhan_check->count() == 1) {
                $user = $habukhan_check->get()[0];
                // FIX: Commenting out heavy external API calls
                // $this->xixapay_account($user->username);
                // $this->monnify_account($user->username);
                // $this->paymentpoint_account($user->username);
                // $this->paystack_account($user->username);
                // $this->insert_stock($user->username);
                $user = DB::table('user')->where(['id' => $user->id])->first();
                $user_details = [
                    'username' => $user->username,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'bal' => number_format($user->bal, 2),
                    'refbal' => number_format($user->refbal, 2),
                    'kyc' => $user->kyc,
                    'type' => $user->type,
                    'pin' => $user->pin,
                    'profile_image' => $user->profile_image,
                    'sterlen' => $user->sterlen,
                    'fed' => $user->fed,
                    'wema' => $user->wema,
                    'opay' => $user->opay,
                    'rolex' => $user->rolex,
                    'palmpay' => $user->palmpay,
                    'paystack_account' => $user->paystack_account,
                    'paystack_bank' => $user->paystack_bank,
                    'address' => $user->address,
                    'webhook' => $user->webhook,
                    'vdf' => $user->vdf,
                    'about' => $user->about,
                    'apikey' => $user->apikey,
                    'account_name' => isset($user->account_name) ? $user->account_name : null,
                    'is_bvn' => $user->bvn == null ? false : true
                ];
                if ($user->otp == $request->code) {
                    //if success
                    $data = [
                        'status' => '1',
                        'otp' => null
                    ];
                    $tableid = [
                        'id' => $user->id
                    ];
                    $general = $this->general();
                    $this->updateData($data, 'user', $tableid);
                    $email_data = [
                        'name' => $user->name,
                        'email' => $user->email,
                        'username' => $user->username,
                        'title' => 'WELCOME EMAIL',
                        'sender_mail' => $general->app_email,
                        'system_email' => $general->app_email,
                        'app_name' => $general->app_name,
                        'pin' => $user->pin,
                    ];
                    // FIX: Disable Mail to prevent timeout
                    // MailController::send_mail($email_data, 'email.welcome');
                    return response()->json([
                        'status' => 'success',
                        'message' => 'account verified',
                        'user' => $user_details,
                        'token' => $this->generatetoken($user->id)
                    ]);
                } else {
                    // Fix for Connection Error/Timeout Retry Issue
                    // If the previous request succeeded in DB but failed to return response (timeout),
                    // the user is already verified (status == 1) but OTP is null.
                    // We should allow them to proceed.
                    if ($user->status == 1) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'account verified',
                            'user' => $user_details
                        ]);
                    }

                    return response()->json([
                        'status' => 403,
                        'message' => 'Invalid OTP'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to verify user'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(env('ERROR_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System',

            ])->setStatusCode(403);
        }
    }
    public function login(Request $request)
    {
        $explode_url = explode(',', env('HABUKHAN_APP_KEY'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            //our login function over here
            $validator = Validator::make($request->all(), [
                'username' => 'required|string',
                'password' => 'required'
            ], [
                'username.required' => 'Your Username or Phone Number or Email is Required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 403,
                    'message' => $validator->errors()->first()
                ])->setStatusCode(403);
            } else {
                $check_system = User::where('username', $request->username);
                if ($check_system->count() == 1) {
                    $user = $check_system->get()[0];
                    // FIX: Smart Account Generation (Only if missing)
                    // This prevents timeouts by not calling external APIs if the user already has the account.
                    if ($user->wema == null)
                        $this->monnify_account($user->username);
                    if ($user->sterlen == null)
                        $this->paymentpoint_account($user->username);
                    if ($user->paystack_account == null)
                        $this->paystack_account($user->username);
                    if ($user->rolex == null)
                        $this->xixapay_account($user->username);
                    // $this->insert_stock($user->username); // Usually fast, can leave or check logic
                    $user = DB::table('user')->where(['id' => $user->id])->first();
                    $user_details = [
                        'username' => $user->username,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'bal' => number_format($user->bal, 2),
                        'refbal' => number_format($user->refbal, 2),
                        'kyc' => $user->kyc,
                        'type' => $user->type,
                        'pin' => $user->pin,
                        'profile_image' => $user->profile_image,
                        'sterlen' => $user->sterlen,
                        'fed' => $user->fed,
                        'wema' => $user->wema,
                        'opay' => $user->opay,
                        'rolex' => $user->rolex,
                        'palmpay' => $user->palmpay, // Added
                        'paystack_account' => $user->paystack_account, // Added
                        'paystack_bank' => $user->paystack_bank, // Added
                        'address' => $user->address,
                        'webhook' => $user->webhook,
                        'about' => $user->about,
                        'apikey' => $user->apikey,
                        'account_name' => isset($user->account_name) ? $user->account_name : null,
                        'is_bvn' => $user->bvn == null ? false : true
                    ];
                    $hash = substr(sha1(md5($request->password)), 3, 10);
                    $mdpass = md5($request->password);
                    if ((password_verify($request->password, $user->password)) xor ($request->password == $user->password) xor ($hash == $user->password) xor ($mdpass == $user->password)) {
                        //  if(Hash::check($request->password, $user->password)){
                        if ($user->status == 1) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Login successfully',
                                'user' => $user_details,
                                'token' => $this->generatetoken($user->id)
                            ]);
                        } else if ($user->status == 2) {
                            return response()->json([
                                'status' => 403,
                                'message' => $user->username . ' Your Account Has Been Banned'
                            ])->setStatusCode(403);
                        } else if ($user->status == 3) {
                            return response()->json([
                                'status' => 403,
                                'message' => $user->username . ' Your Account Has Been Deactivated'
                            ])->setStatusCode(403);
                        } else if ($user->status == 0) {
                            return response()->json([
                                'status' => 'verify',
                                'message' => $user->username . ' Your Account Not Yet verified',
                                'user' => $user_details,
                                'token' => $this->generatetoken($user->id),
                            ]);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'System is unable to verify user'

                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid Password Note Password is Case Sensitive'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Invalid Username and Password'
                    ])->setStatusCode(403);
                }
            }
        } else {
            return redirect(env('ERROR_500'));
            return response()->json([
                'status' => 403,
                'message' => 'unauntorized'
            ])->setStatusCode(403);
        }
    }
    public function resendOtp(Request $request)
    {
        $explode_url = explode(',', env('HABUKHAN_APP_KEY'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (isset($request->id)) {
                $sel_user = DB::table('user')->where('email', $request->id);
                if ($sel_user->count() == 1) {
                    $user = $sel_user->get()[0];
                    $general = $this->general();
                    $otp = random_int(100000, 999999);
                    $data = [
                        'otp' => $otp
                    ];
                    $tableid = [
                        'username' => $user->username
                    ];
                    $this->updateData($data, 'user', $tableid);
                    $email_data = [
                        'name' => $user->name,
                        'email' => $user->email,
                        'username' => $user->username,
                        'title' => 'Account Verification',
                        'sender_mail' => $general->app_email,
                        'app_name' => env('APP_NAME'),
                        'otp' => $otp
                    ];
                    MailController::send_mail($email_data, 'email.verify');
                    return response()->json([
                        'status' => 'status',
                        'message' => 'New OTP Resent to Your Email'
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unable to Detect User'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'An Error Occured'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(env('ERROR_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System',

            ])->setStatusCode(403);
        }
    }
}
