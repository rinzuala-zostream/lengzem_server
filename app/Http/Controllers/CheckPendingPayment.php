<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CheckPendingPayment extends Controller
{
    private $merchantId = 'M221AEW7ARW15';
    private $saltKey = '1d8c7b88-710d-4c48-a70a-cdd08c8cabac';

    public function processUserPayments($userId)
    {
        $pendingSubscriptions = Subscription::where('user_id', $userId)
            ->where('status', 'pending')
            ->get();

        if ($pendingSubscriptions->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No pending subscriptions found.'
            ], 404);
        }

        $results = [];

        foreach ($pendingSubscriptions as $subscription) {
            $paymentStatus = $this->checkPaymentStatus($subscription->payment_id);

            if (!isset($paymentStatus['code']) || $paymentStatus['code'] !== 'PAYMENT_SUCCESS') {
                // Delete failed or unknown payments
                $subscription->delete();

                $results[] = [
                    'subscription_id' => $subscription->id,
                    'status' => 'deleted',
                    'reason' => $paymentStatus['message'] ?? 'Unknown failure'
                ];
            } else {
                // Update successful payment to active
                $subscription->status = 'active';
                $subscription->save();

                $results[] = [
                    'subscription_id' => $subscription->id,
                    'status' => 'activated',
                    'plan' => $subscription->subscription_plan_id
                ];
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Pending payments processed.',
            'data' => $results
        ]);
    }

    private function checkPaymentStatus($transactionId)
    {
        $path = "/pg/v1/status/{$this->merchantId}/{$transactionId}";
        $checksum = hash('sha256', $path . $this->saltKey) . "###1";
        $url = "https://api.phonepe.com/apis/hermes{$path}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-VERIFY' => $checksum,
            'X-MERCHANT-ID' => $this->merchantId
        ])->get($url);

        return $response->json()['data'] ?? ['code' => 'UNKNOWN', 'message' => 'No response from gateway'];
    }
}
