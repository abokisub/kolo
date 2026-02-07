<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ProfileController extends Controller
{
    /**
     * Get Account Limits based on KYC Tier
     * GET /api/profile/limits
     */
    public function getLimits(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');

        if (!$origin || in_array($origin, $explode_url)) {
            $user = DB::table('user')->where('id', $request->user()->id)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            // Determine tier based on KYC status
            $tier = $this->getUserTier($user);
            $limits = $this->getTierLimits($tier);

            // Calculate daily usage
            $dailyUsed = $this->calculateDailyUsage($user->username);

            // Get next tier limits
            $nextTier = $tier < 3 ? $tier + 1 : null;
            $nextTierLimits = $nextTier ? $this->getTierLimits($nextTier) : null;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'tier' => $tier,
                    'tier_name' => $this->getTierName($tier),
                    'single_limit' => $limits['single'],
                    'daily_limit' => $limits['daily'],
                    'daily_used' => $dailyUsed,
                    'daily_remaining' => max(0, $limits['daily'] - $dailyUsed),
                    'usage_percentage' => $limits['daily'] > 0 ? min(100, round(($dailyUsed / $limits['daily']) * 100, 2)) : 0,
                    'next_tier_single' => $nextTierLimits ? $nextTierLimits['single'] : null,
                    'next_tier_daily' => $nextTierLimits ? $nextTierLimits['daily'] : null,
                    'kyc_status' => $user->kyc_status ?? 'pending',
                    'can_upgrade' => $tier < 3,
                    'upgrade_message' => $this->getUpgradeMessage($tier, $user->kyc_status),
                    'theme' => $this->getUserTheme($user->id)
                ]
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to Authenticate System'
            ], 403);
        }
    }

    /**
     * Generate Account Statement
     * POST /api/profile/statement
     */
    public function generateStatement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|in:email,download'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()->first()
            ], 400);
        }

        $user = $request->user();

        $type = $request->input('type', 'email');

        // Get transactions for date range
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $transactions = DB::table('message')
            ->where('username', $user->username)
            ->whereBetween('habukhan_date', [$startDate, $endDate])
            ->orderBy('habukhan_date', 'desc')
            ->get();

        $general = $this->general();
        $emailData = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'username' => $user->username,
            'app_name' => config('app.name'),
            'start_date' => $startDate->format('d M Y'),
            'end_date' => $endDate->format('d M Y'),
            'transactions' => $transactions,
            'total_debit' => $transactions->sum('amount'),
            'opening_balance' => $user->bal, // In a real app, this would be calculated
            'closing_balance' => $user->bal
        ];

        if ($type === 'download') {
            $pdf = \PDF::loadView('pdf.statement', $emailData);
            return $pdf->download('Statement_' . $user->username . '_' . date('Ymd') . '.pdf');
        }

        // Send statement via email with PDF attachment
        $this->sendStatementEmailWithPdf($user, $emailData);

        return response()->json([
            'status' => 'success',
            'message' => 'Statement has been sent to your email address',
            'transactions_count' => count($transactions)
        ]);
    }
    public function updateTheme(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme' => 'required|in:light,dark,system',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()->first()
            ], 400);
        }

        $user = $request->user();


        DB::table('user_settings')->updateOrInsert(
            ['user_id' => $user->id],
            ['theme' => $request->theme, 'updated_at' => now()]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Theme updated successfully',
            'theme' => $request->theme
        ]);
    }

    /**
     * Get user theme from settings
     */
    private function getUserTheme($userId): string
    {
        $setting = DB::table('user_settings')->where('user_id', $userId)->first();
        return $setting->theme ?? 'light';
    }

    /**
     * Determine user tier based on KYC status
     */
    private function getUserTier($user): int
    {
        $kycStatus = $user->kyc_status ?? 'pending';

        switch ($kycStatus) {
            case 'approved':
                return 3; // Gold
            case 'submitted':
                return 2; // Silver
            default:
                return 1; // Bronze
        }
    }

    /**
     * Get tier limits
     */
    private function getTierLimits(int $tier): array
    {
        $limits = [
            1 => ['single' => 50000, 'daily' => 200000],      // Bronze
            2 => ['single' => 200000, 'daily' => 1000000],    // Silver
            3 => ['single' => 500000, 'daily' => 5000000]     // Gold
        ];

        return $limits[$tier] ?? $limits[1];
    }

    /**
     * Get tier name
     */
    private function getTierName(int $tier): string
    {
        $names = [
            1 => 'Bronze',
            2 => 'Silver',
            3 => 'Gold'
        ];

        return $names[$tier] ?? 'Bronze';
    }

    /**
     * Calculate daily usage
     */
    private function calculateDailyUsage(string $username): float
    {
        $today = Carbon::today('Africa/Lagos');

        $transactions = DB::table('message')
            ->where('username', $username)
            ->whereDate('habukhan_date', $today)
            ->whereIn('role', ['DATA', 'AIRTIME', 'CABLE', 'BILL', 'TRANSFER', 'EXAM', 'BULKSMS', 'transfer_sent'])
            ->where('plan_status', 1)
            ->sum('amount');

        return (float) $transactions;
    }

    /**
     * Get upgrade message based on tier and KYC status
     */
    private function getUpgradeMessage(int $tier, ?string $kycStatus): ?string
    {
        if ($tier >= 3) {
            return null; // Already at max tier
        }

        if ($tier === 1 && ($kycStatus === null || $kycStatus === 'pending')) {
            return 'Complete KYC verification to upgrade to Silver tier';
        }

        if ($tier === 2 && $kycStatus === 'submitted') {
            return 'Your KYC is under review. You will be upgraded to Gold tier once approved';
        }

        return 'Complete KYC to unlock higher limits';
    }

    /**
     * Send statement email with PDF attachment
     */
    private function sendStatementEmailWithPdf($user, $emailData)
    {
        try {
            $pdf = \PDF::loadView('pdf.statement', $emailData);
            $attachment = [
                'data' => $pdf->output(),
                'name' => 'Account_Statement_' . date('Ymd') . '.pdf',
                'mime' => 'application/pdf'
            ];

            $emailData['title'] = 'Account Statement - ' . config('app.name');
            \App\Http\Controllers\MailController::send_mail($emailData, 'email.statement', $attachment);
        } catch (\Exception $e) {
            \Log::error('Statement Email Error: ' . $e->getMessage());
        }
    }
}
