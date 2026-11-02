<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\RazorpayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    public function handle(Request $request, RazorpayPaymentService $razorpay): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('x-razorpay-signature');
        $webhookSecret = config('services.razorpay.webhook_secret');

        if (! $signature || ! $webhookSecret) {
            return response()->json(['ok' => false, 'message' => 'Missing signature or webhook secret'], 401);
        }

        $expected = hash_hmac('sha256', $rawBody, $webhookSecret);
        if (! hash_equals($expected, $signature)) {
            return response()->json(['ok' => false, 'message' => 'Invalid signature'], 401);
        }

        $payload = $request->json()->all();
        $event = $payload['event'] ?? '';
        if (in_array($event, ['order.paid', 'payment.captured'], true)) {
            $razorpayOrderId = data_get($payload, 'payload.payment.entity.order_id')
                ?: data_get($payload, 'payload.order.entity.id');
            $paymentEntity = data_get($payload, 'payload.payment.entity');
            $orderEntity = data_get($payload, 'payload.order.entity');
            $razorpayPaymentId = data_get($paymentEntity, 'id');
            $payment = $razorpayOrderId
                ? Payment::where('provider', 'razorpay')->where('gateway_order_id', $razorpayOrderId)->first()
                : null;
            $order = $payment?->order;

            if ($payment && $order) {
                $expectedAmount = (int) round((float) $order->grand_total * 100);
                $paymentMatches = $event === 'payment.captured'
                    && is_array($paymentEntity)
                    && ($paymentEntity['order_id'] ?? null) === $payment->gateway_order_id
                    && ($paymentEntity['status'] ?? null) === 'captured'
                    && ($paymentEntity['captured'] ?? false) === true
                    && strtoupper((string) ($paymentEntity['currency'] ?? '')) === 'INR'
                    && (int) ($paymentEntity['amount'] ?? -1) === $expectedAmount;
                $orderMatches = $event === 'order.paid'
                    && is_array($orderEntity)
                    && ($orderEntity['id'] ?? null) === $payment->gateway_order_id
                    && ($orderEntity['status'] ?? null) === 'paid'
                    && strtoupper((string) ($orderEntity['currency'] ?? '')) === 'INR'
                    && (int) ($orderEntity['amount_paid'] ?? -1) === $expectedAmount;

                if ($paymentMatches || $orderMatches) {
                    $razorpay->confirmWebhookPayment($payment, $razorpayPaymentId);
                } else {
                    Log::warning('Razorpay webhook payment does not match the local order.', [
                        'order_id' => $order->id,
                        'razorpay_order_id' => $razorpayOrderId,
                    ]);
                }
            }
        }

        return response()->json(['ok' => true]);
    }
}
