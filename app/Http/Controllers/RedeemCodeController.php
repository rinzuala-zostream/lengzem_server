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
                'subscription_id' => 'nullable|exists:subscriptions,id',
                'status' => 'nullable|in:active,pending',
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

            // Prevent owner from redeeming their own code
            if ($redeem->user_id === $userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot redeem your own code.',
                ], 403);
            }

            // Check expiry and deactivate if expired
            if ($redeem->expire_date && now()->greaterThan($redeem->expire_date)) {
                $this->deactivateRedeemCode($redeem->id);
                return response()->json([
                    'status' => false,
                    'message' => 'This redeem code has expired.',
                ], 400);
            }

            // Check if user has already used this code
            if (UserRedeem::where('user_id', $userId)->where('redeem_id', $redeem->id)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You have already used this redeem code.',
                ], 409);
            }

            // Apply the redeem code
            $userRedeem = UserRedeem::create([
                'user_id' => $userId,
                'redeem_id' => $redeem->id,
                'apply_date' => now(),
                'subscription_id' => $validated['subscription_id'] ?? null,
                'status' => $validated['status'] ?? 'pending',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Redeem code applied successfully.',
                'data' => [
                    'redeem' => $redeem->fresh(),
                    'user_redeem' => $userRedeem,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\TypeError $e) {
            \Log::error("RedeemCodeController apply type error: {$e->getMessage()}");
            return response()->json([
                'status' => false,
                'message' => 'Invalid data type provided.',
            ], 400);
        } catch (\Exception $e) {
            \Log::error("RedeemCodeController apply error: {$e->getMessage()}");
            return response()->json([
                'status' => false,
                'message' => 'Failed to apply redeem code. ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update redeem details by subscription ID
     */
    public function updateBySubscriptionId(Request $request, $subscriptionId)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:active,pending',
            ]);

            // Step 1: Find all user_redeems for this subscription that need status change
            $userRedeems = UserRedeem::where('subscription_id', $subscriptionId)
                ->where('status', '!=', $validated['status'])
                ->get();

            if ($userRedeems->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No user redeem records found for this subscription ID or already updated.',
                ], 404);
            }

            // Step 2: Update status & count applies per redeem_id
            $redeemCountMap = [];

            foreach ($userRedeems as $userRedeem) {
                // Update user_redeem status
                $userRedeem->update([
                    'status' => $validated['status'],
                    'updated_at' => now(),
                ]);

                // Track redeem_id count if activating
                if ($validated['status'] === 'active') {
                    $redeemCountMap[$userRedeem->redeem_id] = ($redeemCountMap[$userRedeem->redeem_id] ?? 0) + 1;
                }
            }

            // Step 3: Update RedeemCode no_of_apply counts
            foreach ($redeemCountMap as $redeemId => $count) {
                $redeemCode = RedeemCode::find($redeemId);
                if ($redeemCode) {
                    $redeemCode->increment('no_of_apply', $count);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Redeem records updated successfully.',
                'data' => [
                    'updated_redeems' => $userRedeems,
                    'updated_redeem_codes' => array_keys($redeemCountMap)
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error("RedeemCodeController updateBySubscriptionId error: {$e->getMessage()}");
            return response()->json([
                'status' => false,
                'message' => 'Failed to update redeem records.',
                'error' => $e->getMessage(),
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
