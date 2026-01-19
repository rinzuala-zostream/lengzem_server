<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    // Get all subscriptions with related plan and user
    public function index(Request $request)
    {
        try {
            $userId = $request->query('user_id');
            $perPage = $request->query('per_page', 15);

            $query = Subscription::with(['plan', 'user']);

            if ($userId) {
                $query->where('user_id', $userId);
            }

            // Optional: Add ordering
            $query->orderBy('created_at', 'desc');

            $subscriptions = $query->paginate($perPage);

            if ($subscriptions->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No subscriptions found',
                    'data' => [
                        'data' => [],
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $perPage,
                        'total' => 0
                    ]
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Subscriptions retrieved successfully.',
                'data' => $subscriptions,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve subscriptions.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Store a new subscription
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:user,id',
                'subscription_plan_id' => 'required|exists:subscription_plans,id',
                'payment_id' => 'required|string',
                'start_date' => 'nullable|date',
                'status' => 'nullable|in:active,expired,cancelled,pending',
                'amount' => 'nullable|numeric|min:0',
                'redeem_code' => 'nullable|string|max:20',
            ]);

            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : now();

            $plan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);
            $intervalValue = $plan->interval_value;
            $intervalUnit = $plan->interval_unit;

            $endDate = $startDate->copy()->add($intervalUnit, $intervalValue);

            $userId = $validated['user_id'];
            $redeemId = null;

            // ✅ Step 1: If redeem_code is entered, apply it first
            if (!empty($validated['redeem_code'])) {
                $redeemCode = strtoupper(trim($validated['redeem_code']));

                $redeemController = new RedeemCodeController();
                $redeemResponse = $redeemController->apply(new Request([
                    'user_id' => $userId,
                    'redeem_code' => $redeemCode,
                    'subscription_id' => $plan->id,
                    'status' => 'inactive',
                ]));

                $redeemData = $redeemResponse->getData();

                // ❌ Stop immediately if redeem apply failed
                if (empty($redeemData) || !$redeemData->status) {
                    return response()->json([
                        'status' => false,
                        'message' => $redeemData->message ?? 'Failed to apply redeem code.',
                    ], 400);
                }

                // ✅ Apply successful, get redeem_id
                if (isset($redeemData->data->redeem->id)) {
                    $redeemId = $redeemData->data->redeem->id;
                }
            }

            // ✅ Step 2: Create the subscription
            $subscription = Subscription::create([
                'user_id' => $userId,
                'subscription_plan_id' => $validated['subscription_plan_id'],
                'payment_id' => $validated['payment_id'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $validated['status'] ?? 'pending',
                'amount' => $request->get('amount', 0),
                'redeem_id' => $redeemId, // set from applied code or null
            ]);

            return response()->json([
                'status' => true,
                'message' => $redeemId
                    ? 'Subscription created successfully using redeem code.'
                    : 'Subscription and new redeem code created successfully.',
                'data' => [
                    'subscription' => $subscription->fresh('redeemCode'),
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error("Subscription store error: {$e->getMessage()}");
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while creating the subscription.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Show a specific subscription by ID
    public function show($id)
    {
        $subscription = Subscription::where('user_id', $id)
            ->latest('id') // latest subscription based on insertion order
            ->first();

        if (!$subscription) {
            return response()->json([
                'status' => false,
                'message' => 'Subscription not found',
            ]);
        }

        $subscription->load('plan', 'user');

        return response()->json([
            'status' => true,
            'message' => 'Latest subscription retrieved successfully.',
            'data' => $subscription,
        ]);
    }

    // Update an existing subscription by ID
    public function update(Request $request, $id)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'user_id' => 'sometimes|exists:user,id',
                'subscription_plan_id' => 'sometimes|exists:subscription_plans,id',
                'payment_id' => 'sometimes|string',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date|after_or_equal:start_date',
                'status' => 'sometimes|in:active,expired,cancelled,pending',
                'amount' => 'sometimes|numeric|min:0', // Optional amount field
            ]);

            // Find the subscription
            $subscription = Subscription::where($id);

            if (!$subscription) {
                return response()->json([
                    'status' => false,
                    'message' => 'Subscription not found',
                ]); // HTTP 404 Not Found
            }

            // Update the subscription
            $subscription->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Subscription updated successfully.',
                'data' => $subscription,
            ]); // HTTP 200 OK
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ]); // HTTP 422 Unprocessable Entity
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the subscription.',
                'error' => $e->getMessage(),
            ]); // HTTP 500 Internal Server Error
        }
    }

    // Delete a subscription by ID
    public function destroy($id)
    {
        $subscription = Subscription::find($id);

        if (!$subscription) {
            return response()->json([
                'status' => false,
                'message' => 'Subscription not found',
            ], 404); // HTTP 404 Not Found
        }

        try {
            // Delete the subscription
            $subscription->delete();

            return response()->json([
                'status' => true,
                'message' => 'Subscription deleted successfully.',
            ], 200); // HTTP 200 OK
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while deleting the subscription.',
                'error' => $e->getMessage(),
            ], 500); // HTTP 500 Internal Server Error
        }
    }
}
