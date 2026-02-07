<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LimitService
{
    /**
     * Check if a user can perform a transaction of $amount
     * 
     * @param object $user The user record from DB
     * @param float $amount The amount of the transaction
     * @return array ['allowed' => boolean, 'message' => string]
     */
    public static function checkLimit($user, $amount)
    {
        // 1. Refresh user to ensure we have latest limits/usage
        $user = DB::table('user')->where('id', $user->id)->first();

        // 2. Reset daily_used if it's a new day
        $today = Carbon::today('Africa/Lagos')->toDateString();
        if ($user->daily_used_date !== $today) {
            DB::table('user')->where('id', $user->id)->update([
                'daily_used' => 0,
                'daily_used_date' => $today
            ]);
            $user->daily_used = 0;
        }

        // 3. Check Single Transaction Limit
        if ($amount > $user->single_limit) {
            $formattedLimit = number_format($user->single_limit, 2);
            return [
                'allowed' => false,
                'message' => "This transaction exceeds your single limit of ₦$formattedLimit. Please upgrade your KYC to increase limits."
            ];
        }

        // 4. Check Daily Limit
        if (($user->daily_used + $amount) > $user->daily_limit) {
            $formattedLimit = number_format($user->daily_limit, 2);
            $remaining = number_format($user->daily_limit - $user->daily_used, 2);
            return [
                'allowed' => false,
                'message' => "Daily limit reached! Your daily limit is ₦$formattedLimit. Remaining today: ₦$remaining."
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Record a successful transaction amount to user's daily usage
     */
    public static function recordTransaction($user, $amount)
    {
        DB::table('user')->where('id', $user->id)->increment('daily_used', $amount);
    }
}
