<?php

namespace App\Services\Payments;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Notifications\AiSensyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RazorpayPaymentService
{
    public function __construct(private readonly AiSensyService $aiSensy) {}

    public function createOrder(Order $order): array
    {
        $existingPayment = $order->payments()
            ->where('provider', 'razorpay')
            ->where('transaction_type', 'capture')
            ->latest()
            ->first();

        if ($existingPayment?->gateway_order_id
            && ($existingPayment->gateway_payload['id'] ?? null) === $existingPayment->gateway_order_id) {
            return $existingPayment->gateway_payload;
        }

        $payload = [
            'amount' => round((float) $order->grand_total * 100), // Razorpay expects amount in paise (1 INR = 100 paise)
            'currency' => 'INR',
            'receipt' => $order->order_number,
            'notes' => [
                'local_order_id' => (string) $order->id,
                'checkout_token' => (string) $order->checkout_token,
            ],
        ];

        $response = Http::withBasicAuth($this->keyId(), $this->keySecret())
            ->post('https://api.razorpay.com/v1/orders', $payload);

        if (!$response->successful()) {
            Log::error('Razorpay order creation failed.', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->json() ?: $response->body(),
            ]);

            throw new RuntimeException('Unable to start Razorpay payment. Please try again.');
        }

        $razorpayOrder = $response->json();
        if (! is_array($razorpayOrder) || empty($razorpayOrder['id'])) {
            throw new RuntimeException('Razorpay did not return a usable order. Please try again.');
        }

        $order->payments()->updateOrCreate(
            ['provider' => 'razorpay', 'transaction_type' => 'capture'],
            [
                'status' => 'initiated',
                'currency' => 'INR',
                'amount' => $order->grand_total,
                'gateway_order_id' => $razorpayOrder['id'],
                'gateway_payload' => $razorpayOrder,
                'notes' => 'Razorpay payment initiated',
            ]
        );

        return $razorpayOrder;
    }

    /** Confirm the gateway reports the signed payment as captured for this exact order and amount. */
    public function isCapturedPaymentForOrder(Order $order, array $data): bool
    {
        $paymentId = (string) ($data['razorpay_payment_id'] ?? '');
        if ($paymentId === '') {
            return false;
        }

        $response = Http::withBasicAuth($this->keyId(), $this->keySecret())
            ->timeout(20)
            ->get('https://api.razorpay.com/v1/payments/' . rawurlencode($paymentId));

        if (! $response->successful()) {
            throw new RuntimeException('Unable to verify Razorpay payment status.');
        }

        $payment = $response->json();

        return is_array($payment)
            && ($payment['id'] ?? null) === $paymentId
            && ($payment['order_id'] ?? null) === ($data['razorpay_order_id'] ?? null)
            && ($payment['status'] ?? null) === 'captured'
            && strtoupper((string) ($payment['currency'] ?? '')) === 'INR'
            && (int) ($payment['amount'] ?? -1) === (int) round((float) $order->grand_total * 100);
    }

    public function verifySignature(array $data): bool
    {
        $razorpayOrderId = $data['razorpay_order_id'] ?? '';
        $razorpayPaymentId = $data['razorpay_payment_id'] ?? '';
        $razorpaySignature = $data['razorpay_signature'] ?? '';

        if (!$razorpayOrderId || !$razorpayPaymentId || !$razorpaySignature) {
            return false;
        }

        $expected = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $this->keySecret());

        return hash_equals($expected, $razorpaySignature);
    }

    public function belongsToOrder(int $orderId, string $razorpayOrderId): bool
    {
        return Payment::query()
            ->where('order_id', $orderId)
            ->where('provider', 'razorpay')
            ->where('gateway_order_id', $razorpayOrderId)
            ->exists();
    }

    /** Confirm a payment only after its gateway order has been matched to this local order. */
    public function confirmVerifiedPayment(Order $order, array $data): void
    {
        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('provider', 'razorpay')
            ->where('gateway_order_id', $data['razorpay_order_id'])
            ->latest()
            ->firstOrFail();

        $result = $this->persistConfirmation(
            $order,
            $payment,
            $data['razorpay_payment_id'],
            'Razorpay payment successful',
            ['verification_response' => $data],
            false,
        );

        $this->dispatchPaidEvent($result);

        if (! $result['notificationAlreadySent'] && $this->aiSensy->sendOrderPaid($result['order'])) {
            $payment = $result['payment'];
            $payment->forceFill([
                'gateway_payload' => array_merge($payment->gateway_payload ?? [], [
                    'aisensy_order_paid_sent_at' => now()->toIso8601String(),
                ]),
            ])->save();
        }
    }

    /** Apply a signed webhook payment event once per local order. */
    public function confirmWebhookPayment(Payment $payment, ?string $razorpayPaymentId): void
    {
        $result = $this->persistConfirmation(
            $payment->order,
            $payment,
            $razorpayPaymentId,
            'Razorpay webhook confirmation',
            [],
            true,
        );

        $this->dispatchPaidEvent($result);
    }

    /** @return array{order: Order, payment: Payment, wasUnpaid: bool, notificationAlreadySent: bool} */
    private function persistConfirmation(
        Order $order,
        Payment $payment,
        ?string $gatewayPaymentId,
        string $notes,
        array $payloadAdditions,
        bool $skipIfAlreadyPaid,
    ): array {
        return DB::transaction(function () use ($order, $payment, $gatewayPaymentId, $notes, $payloadAdditions, $skipIfAlreadyPaid): array {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $lockedPayment = Payment::query()
                ->whereKey($payment->id)
                ->where('order_id', $lockedOrder->id)
                ->where('provider', 'razorpay')
                ->lockForUpdate()
                ->firstOrFail();

            $previousStatus = $lockedOrder->status;
            $previousFulfillmentStatus = $lockedOrder->fulfillment_status;
            $wasUnpaid = $lockedOrder->payment_status !== 'paid';
            $paymentPayload = $lockedPayment->gateway_payload ?? [];

            if ($skipIfAlreadyPaid && ! $wasUnpaid) {
                return [
                    'order' => $lockedOrder,
                    'payment' => $lockedPayment,
                    'wasUnpaid' => false,
                    'notificationAlreadySent' => ! empty($paymentPayload['aisensy_order_paid_sent_at']),
                ];
            }

            $lockedOrder->forceFill([
                'status' => $lockedOrder->status === 'pending' ? 'confirmed' : $lockedOrder->status,
                'payment_status' => 'paid',
                'payment_method' => 'razorpay',
            ])->save();

            $lockedPayment->forceFill([
                'status' => 'success',
                'gateway_payment_id' => $gatewayPaymentId ?: $lockedPayment->gateway_payment_id,
                'gateway_payload' => array_merge($paymentPayload, $payloadAdditions),
                'paid_at' => now(),
                'notes' => $notes,
            ])->save();

            if ($previousStatus !== $lockedOrder->status) {
                $lockedOrder->statusHistories()->create([
                    'from_status' => $previousStatus,
                    'to_status' => $lockedOrder->status,
                    'from_fulfillment_status' => $previousFulfillmentStatus,
                    'to_fulfillment_status' => $lockedOrder->fulfillment_status,
                    'updated_by' => $lockedOrder->user_id,
                    'note' => $skipIfAlreadyPaid
                        ? 'Razorpay payment confirmed via webhook.'
                        : 'Razorpay payment confirmed.',
                ]);
            }

            return [
                'order' => $lockedOrder->fresh(),
                'payment' => $lockedPayment->fresh(),
                'wasUnpaid' => $wasUnpaid,
                'notificationAlreadySent' => ! empty($paymentPayload['aisensy_order_paid_sent_at']),
            ];
        });
    }

    /** @param array{order: Order, payment: Payment, wasUnpaid: bool, notificationAlreadySent: bool} $result */
    private function dispatchPaidEvent(array $result): void
    {
        if ($result['wasUnpaid']) {
            Event::dispatch(new OrderPaid($result['order']));
        }
    }

    private function keyId(): string
    {
        $keyId = (string) config('services.razorpay.key_id');
        if ($keyId === '') {
            throw new RuntimeException('Razorpay Key ID is not configured.');
        }

        return $keyId;
    }

    private function keySecret(): string
    {
        $keySecret = (string) config('services.razorpay.key_secret');
        if ($keySecret === '') {
            throw new RuntimeException('Razorpay Key Secret is not configured.');
        }

        return $keySecret;
    }
}
