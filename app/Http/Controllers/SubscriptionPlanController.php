<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubscriptionPlanController extends Controller
{
    // Get all subscription plans
    public function index(Request $request)
    {
        $userId = $request->query('user_id');
        $plans = SubscriptionPlan::all();

        if ($plans->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No subscription plans found'
            ], 404);
        }

        $activePlanId = null;
        $currentPlan = null;

        if ($userId) {
            $activeSubscription = Subscription::where('user_id', $userId)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            if ($activeSubscription) {
                $activePlanId = $activeSubscription->subscription_plan_id;
                $currentPlan = $plans->firstWhere('id', $activePlanId);
            }
        }

        // Mark the current plan and filter others
        $plans = $plans->map(function ($plan) use ($currentPlan) {
            $plan->current_plan = false;

            if ($currentPlan && $plan->id === $currentPlan->id) {
                $plan->current_plan = true;
            }

            return $plan;
        });

        // Filter to only show upgradeable plans (price > current)
        if ($currentPlan) {
            $plans = $plans->filter(function ($plan) use ($currentPlan) {
                return $plan->price > $currentPlan->price || $plan->current_plan;
            })->values(); // reindex collection
        }

        return response()->json([
            'status' => true,
            'message' => 'Subscription plans retrieved successfully.',
            'data' => $plans
        ], 200);
    }

    // Store a new subscription plan
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'interval_value' => 'required|integer|min:1',
                'interval_unit' => 'required|in:day,week,month,year',
            ]);

            // Create the subscription plan
            $plan = SubscriptionPlan::create($validated);

            // Return success response with the created plan data
            return response()->json([
                'status' => true,
                'message' => 'Subscription plan created successfully.',
                'data' => $plan
            ], 200); // HTTP 200 OK
        } catch (ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422); // HTTP 422 Unprocessable Entity
        } catch (\Exception $e) {
            // Handle other errors
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while creating the subscription plan.',
                'error' => $e->getMessage(),
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    // Show a specific subscription plan by ID
    public function show($id)
    {
        $subscriptionPlan = SubscriptionPlan::find($id);

        if (!$subscriptionPlan) {
            return response()->json([
                'status' => false,
                'message' => 'Subscription plan not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Subscription plan retrieved successfully.',
            'data' => $subscriptionPlan
        ], 200);
    }

    // Update an existing subscription plan by ID
    public function update(Request $request, $id)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'price' => 'sometimes|required|numeric|min:0',
                'interval_value' => 'sometimes|required|integer|min:1',
                'interval_unit' => 'sometimes|required|in:day,week,month,year',
            ]);

            $subscriptionPlan = SubscriptionPlan::find($id);

            if (!$subscriptionPlan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Subscription plan not found'
                ], 404);
            }

            // Update the plan
            $subscriptionPlan->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Subscription plan updated successfully.',
                'data' => $subscriptionPlan
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the subscription plan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Delete a subscription plan by ID
    public function destroy($id)
    {
        $subscriptionPlan = SubscriptionPlan::find($id);

        if (!$subscriptionPlan) {
            return response()->json([
                'status' => false,
                'message' => 'Subscription plan not found'
            ], 404);
        }

        try {
            // Delete the plan
            $subscriptionPlan->delete();

            return response()->json([
                'status' => true,
                'message' => 'Subscription plan deleted successfully'
            ], 200); // HTTP 200 OK
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while deleting the subscription plan.',
                'error' => $e->getMessage(),
            ], 500); // HTTP 500 Internal Server Error
        }
    }
}
