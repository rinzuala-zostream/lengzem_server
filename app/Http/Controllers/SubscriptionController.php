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
        $userId = $request->query('user_id'); // user_id param

        $query = Subscription::with('plan', 'user');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $subscriptions = $query->get();

        if ($subscriptions->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No subscriptions found',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Subscriptions retrieved successfully.',
            'data' => $subscriptions,
        ]);
    }

    // Store a new subscription
    public function store(Request $request)
    {
        try {
            // Step 1: Validate input (except end_date/start_date logic)
            $validated = $request->validate([
                'user_id' => 'required|exists:user,id',
                'subscription_plan_id' => 'required|exists:subscription_plans,id',
                'payment_id' => 'required|string',
                'start_date' => 'nullable|date',
                'status' => 'nullable|in:active,expired,cancelled,pending',
            ]);

            // Step 2: Use current date if start_date is not provided
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : now();

            // Step 3: Get plan and calculate end_date
            $plan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);
            $intervalValue = $plan->interval_value;
            $intervalUnit = $plan->interval_unit;

            // Add duration to get end_date
            $endDate = $startDate->copy()->add($intervalUnit, $intervalValue);

            // Step 4: Create the subscription
            $subscription = Subscription::create([
                'user_id' => $validated['user_id'],
                'subscription_plan_id' => $validated['subscription_plan_id'],
                'payment_id' => $validated['payment_id'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => empty($validated['status']) ? 'pending' : $validated['status'],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Subscription created successfully.',
                'data' => $subscription,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while creating the subscription.',
                'error' => $e->getMessage(),
            ]);
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
