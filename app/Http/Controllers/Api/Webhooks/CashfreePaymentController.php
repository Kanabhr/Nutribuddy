<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\CashfreePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CashfreePaymentController extends Controller
{
    public function return(Request $request, CashfreePaymentService $cashfree): RedirectResponse
    {
        $cashfreeOrderId = (string) $request->query('order_id', '');
        $order = Order::where('order_number', $cashfreeOrderId)->first();

        if (! $order) {
            return redirect()->route('checkout.index')->with('error', 'Unable to verify payment order.');
        }

        try {
            $cashfreeOrder = $cashfree->fetchOrder($order->order_number);
            $order = $cashfree->syncOrderStatus($order, $cashfreeOrder);
        } catch (\Throwable $exception) {
            Log::error('Cashfree return verification failed.', [
                'order_number' => $cashfreeOrderId,
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('user.orders.detail-page', $order)
                ->with('warning', 'Payment verification is pending. We will update your order shortly.');
        }

        if ($order->payment_status === 'paid') {
            return redirect()
                ->route('user.orders.detail-page', $order)
                ->with('success', 'Payment successful. Your order is confirmed.');
        }

        return redirect()
            ->route('user.orders.detail-page', $order)
            ->with('warning', 'Payment was not completed. Please contact support if money was deducted.');
    }

    public function webhook(Request $request, CashfreePaymentService $cashfree): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('x-webhook-signature');
        $timestamp = $request->header('x-webhook-timestamp');

        if (!$signature || !$timestamp) {
            abort(401, 'Missing webhook signature or timestamp');
        }

        abort_unless($cashfree->webhookSignatureIsValid($rawBody, $signature, $timestamp), 401);

        $payload = $request->json()->all();
        $cashfreeOrderId = data_get($payload, 'data.order.order_id')
            ?: data_get($payload, 'data.order_id')
            ?: data_get($payload, 'order_id');

        if (! $cashfreeOrderId) {
            return response()->json(['ok' => true]);
        }

        $order = Order::where('order_number', $cashfreeOrderId)->first();
        if (! $order) {
            Log::warning('Cashfree webhook received for unknown order.', ['order_id' => $cashfreeOrderId]);
            return response()->json(['ok' => true]);
        }

        try {
            $cashfreeOrder = $cashfree->fetchOrder($order->order_number);
            $cashfree->syncOrderStatus($order, $cashfreeOrder);
        } catch (\Throwable $exception) {
            Log::error('Cashfree webhook sync failed.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['ok' => false], 500);
        }

        return response()->json(['ok' => true]);
    }
}
