<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Subscription;

class PaymentController extends Controller
{
    private $merchantId = 'M221AEW7ARW15';
    private $saltKey = '1d8c7b88-710d-4c48-a70a-cdd08c8cabac';

    protected $subscription;

    public function __construct(SubscriptionController $subscription)
    {
        $this->subscription = $subscription;
    }

    public function checkPaymentStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string',
        ]);

        $userId = $request->query('user_id');

        // Get all pending subscriptions with a payment_id
        $pendingSubs = Subscription::where('user_id', $userId)
            ->where('status', 'pending')
            ->whereNotNull('payment_id')
            ->get(); // Do NOT use select() or pluck() here, so Eloquent models are returned

        if ($pendingSubs->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No pending payments found'
            ]);
        }

        $results = [];

        try {
            foreach ($pendingSubs as $sub) {
                $transactionId = $sub->payment_id;

                $response = $this->checkPhonePePaymentStatus($transactionId);
                $state = $response['data']['state'] ?? null;

                if ($response['success'] && $state === 'COMPLETED') {
                    // Set as active
                    $sub->status = 'active';
                    $sub->save();
                } else {
                    // Delete the subscription if payment not completed
                    $sub->delete();
                }

                $results[] = [
                    'subscription_id' => $sub->id,
                    'transaction_id' => $transactionId,
                    'payment_status' => $state ?? 'UNKNOWN',
                    'action' => ($state === 'COMPLETED') ? 'activated' : 'deleted',
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Payment status checked.',
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error checking payment status: ' . $e->getMessage()
            ]);
        }
    }

    private function checkPhonePePaymentStatus($transactionId)
    {
        $path = "/pg/v1/status/{$this->merchantId}/{$transactionId}";
        $checksum = hash('sha256', $path . $this->saltKey) . "###1";
        $url = "https://api.phonepe.com/apis/hermes{$path}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-VERIFY' => $checksum,
            'X-MERCHANT-ID' => $this->merchantId
        ])->get($url);

        return $response->json();
    }
}
