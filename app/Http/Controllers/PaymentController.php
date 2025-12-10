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

    public RazorpayController $razorpayController;

    public function __construct(SubscriptionController $subscription, RazorpayController $razorpayController)
    {
        $this->subscription = $subscription;
        $this->razorpayController = $razorpayController;
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

                $orderId = $transactionId;
                $h = strtolower(trim((string) $request->header('X-RZ-Env', 'production')));
                $razorpayReq = new Request(['X-RZ-Env' => $h]);
                $razorResponse = $this->razorpayController->checkPaymentStatus($razorpayReq, $orderId);
                $paymentResponse = json_decode($razorResponse->getContent(), true);

                $paymentSuccess = isset($paymentResponse['success']) && $paymentResponse['success'] === true;
                $paymentCompleted = isset($paymentResponse['code']) && $paymentResponse['code'] === 'PAYMENT_SUCCESS' ||
                    isset($paymentResponse['data']['state']) && $paymentResponse['data']['state'] === 'COMPLETED';

                $paymentAmount = ((int)$paymentResponse['data']['payments']['amount'] === (int)($sub->amount * 100));

                if ($paymentSuccess && $paymentCompleted && $paymentAmount) {
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
}
