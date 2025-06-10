<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    // Get all subscriptions with related plan and user
    public function index()
    {
        $subscriptions = Subscription::with('plan', 'user')->get();

        if ($subscriptions->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No subscriptions found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Subscriptions retrieved successfully.',
            'data' => $subscriptions,
        ], 200);
    }

    // Store a new subscription
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'subscription_plan_id' => 'required|exists:subscription_plans,id',
                'payment_id' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'status' => 'in:active,expired,cancelled,pending',
            ]);

            // Create the subscription
            $subscription = Subscription::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Subscription created successfully.',
                'data' => $subscription,
            ], 201); // HTTP 201 Created
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422); // HTTP 422 Unprocessable Entity
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while creating the subscription.',
                'error' => $e->getMessage(),
            ], 500); // HTTP 500 Internal Server Error
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
            ], 404);
        }

        $subscription->load('plan', 'user');

        return response()->json([
            'status' => true,
            'message' => 'Latest subscription retrieved successfully.',
            'data' => $subscription,
        ], 200);
    }

    // Update an existing subscription by ID
    public function update(Request $request, $id)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'user_id' => 'sometimes|exists:users,id',
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
                ], 404); // HTTP 404 Not Found
            }

            // Update the subscription
            $subscription->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Subscription updated successfully.',
                'data' => $subscription,
            ], 200); // HTTP 200 OK
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422); // HTTP 422 Unprocessable Entity
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the subscription.',
                'error' => $e->getMessage(),
            ], 500); // HTTP 500 Internal Server Error
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
