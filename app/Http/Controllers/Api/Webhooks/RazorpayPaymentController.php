<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Webhooks\VerifyRazorpayPaymentRequest;
use App\Models\Order;
use App\Services\Payments\RazorpayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RazorpayPaymentController extends Controller
{
    public function __construct(
        private readonly RazorpayPaymentService $razorpay,
    ) {
    }

    public function verify(VerifyRazorpayPaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $order = Order::findOrFail($validated['order_id']);

        if (! $this->razorpay->verifySignature($validated)
            || ! $this->razorpay->belongsToOrder((int) $order->id, $validated['razorpay_order_id'])) {
            Log::warning('Razorpay signature verification failed.', [
                'order_id' => $order->id,
            ]);
            return response()->json(['success' => false, 'message' => 'Payment signature verification failed.'], 400);
        }

        try {
            if (! $this->razorpay->isCapturedPaymentForOrder($order, $validated)) {
                return response()->json(['success' => false, 'message' => 'Payment is not captured or does not match this order.'], 400);
            }

            $this->razorpay->confirmVerifiedPayment($order, $validated);
        } catch (\Throwable $exception) {
            Log::error('Error updating order status for Razorpay.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to complete order processing.'], 500);
        }

        return response()->json(['success' => true]);
    }

}
