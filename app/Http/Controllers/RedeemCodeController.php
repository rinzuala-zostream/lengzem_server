<?php

namespace App\Http\Controllers;

use App\Models\RedeemCode;
use App\Models\UserRedeem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class RedeemCodeController extends Controller
{
    /**
     * Generate and store a redeem code for a user.
     */
    public static function createRedeemCode(string $userId, ?Carbon $benefitEndDate = null): ?RedeemCode
    {
        try {
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $redeemCode = '';
            for ($i = 0; $i < 8; $i++) {
                $redeemCode .= $characters[random_int(0, strlen($characters) - 1)];
            }

            return RedeemCode::create([
                'user_id' => $userId,
                'redeem_code' => $redeemCode,
                'no_of_apply' => 0,
                'is_active' => true,
                'benefit_end_month' => $benefitEndDate,
                'expire_date' => now()->addDays(30),
            ]);
        } catch (Exception $e) {
            Log::error("RedeemCodeController createRedeemCode error: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Manual API endpoint for generating redeem codes.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:user,id',
                'benefit_end_month' => 'nullable|date',
            ]);

            $benefitEndDate = $validated['benefit_end_month']
                ? Carbon::parse($validated['benefit_end_month'])
                : null;

            $redeem = self::createRedeemCode($validated['user_id'], $benefitEndDate);

            if (!$redeem) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to generate redeem code.',
                ], 500);
            }

            return response()->json([
                'status' => true,
                'message' => 'Redeem code generated successfully.',
                'data' => $redeem,
            ]);
        } catch (Exception $e) {
            Log::error("RedeemCodeController store error: {$e->getMessage()}");
            return response()->json([
                'status' => false,
                'message' => 'Error generating redeem code.',
            ], 500);
        }
    }

    /**
     * Apply a redeem code for a user
     */
    public function apply(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:user,id',
                'redeem_code' => 'required|string|max:20',
            ]);

            $userId = $validated['user_id'];
            $codeInput = strtoupper(trim($validated['redeem_code']));

            $redeem = RedeemCode::where('redeem_code', $codeInput)->first();

            if (!$redeem) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid redeem code.',
                ], 404);
            }

            // Check expiry and deactivate if expired
            if ($redeem->expire_date && now()->greaterThan($redeem->expire_date)) {
                $this->deactivateRedeemCode($redeem->id);

                return response()->json([
                    'status' => false,
                    'message' => 'This redeem code has expired.',
                ], 400);
            }

            $alreadyUsed = UserRedeem::where('user_id', $userId)
                ->where('redeem_id', $redeem->id)
                ->exists();

            if ($alreadyUsed) {
                return response()->json([
                    'status' => false,
                    'message' => 'You have already used this redeem code.',
                ], 409);
            }

            $userRedeem = UserRedeem::create([
                'user_id' => $userId,
                'redeem_id' => $redeem->id,
                'apply_date' => now(),
            ]);

            $redeem->incrementApplyCount();

            return response()->json([
                'status' => true,
                'message' => 'Redeem code applied successfully.',
                'data' => [
                    'redeem' => $redeem->fresh(),
                    'user_redeem' => $userRedeem,
                ],
            ]);
        } catch (Exception $e) {
            Log::error("RedeemCodeController apply error: {$e->getMessage()}");
            return response()->json([
                'status' => false,
                'message' => 'Failed to apply redeem code.',
            ], 500);
        }
    }

    /**
     * ✅ Deactivate a redeem code by ID
     */
    public function deactivateRedeemCode(int $id): bool
    {
        try {
            $redeem = RedeemCode::find($id);

            if (!$redeem) {
                return false; // Code not found
            }

            $redeem->update(['is_active' => false]);

            return true; // Successfully deactivated
        } catch (Exception $e) {
            Log::error("RedeemCodeController deactivateRedeemCode error: {$e->getMessage()}");
            return false;
        }
    }
}
