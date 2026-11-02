<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Services\Notifications\AiSensyService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CashfreePaymentService
{
    public function __construct(private readonly AiSensyService $aiSensy)
    {
    }

    public function createOrder(Order $order): array
    {
        $amount = round((float) $order->grand_total, 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Order amount must be greater than zero.');
        }

        $this->validateConfiguration();

        $payment = $order->payments()->where('provider', 'cashfree')->latest()->first();
        $existingSessionId = $payment?->gateway_payload['payment_session_id'] ?? null;

        if ($existingSessionId) {
            return [
                'payment_session_id' => $existingSessionId,
                'cashfree_order' => $payment->gateway_payload,
                'mode' => $this->environment(),
            ];
        }

        $payload = [
            'order_id' => $order->order_number,
            'order_amount' => $amount,
            'order_currency' => $order->currency ?: 'INR',
            'customer_details' => [
                'customer_id' => 'user_' . $order->user_id,
                'customer_name' => $order->customer_name ?: 'Customer',
                'customer_email' => $order->customer_email ?: 'customer-' . $order->id . '@nutribuddy.local',
                'customer_phone' => preg_replace('/\D+/', '', (string) $order->customer_phone) ?: '9999999999',
            ],
            'order_meta' => [
                'return_url' => route('cashfree.return') . '?order_id={order_id}',
                'notify_url' => route('cashfree.webhook'),
            ],
            'order_note' => 'NutriBuddy order ' . $order->order_number,
            'order_tags' => [
                'local_order_id' => (string) $order->id,
                'checkout_token' => (string) $order->checkout_token,
            ],
        ];

        $response = $this->client()
            ->withHeaders(['x-idempotency-key' => hash('sha256', 'cashfree-order:' . $order->order_number)])
            ->post($this->baseUrl() . '/orders', $payload);

        if (!$response->successful()) {
            $body = $response->json() ?: $response->body();
            Log::error('Cashfree order creation failed.', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => is_array($body) ? $body : ['raw' => (string) $body],
            ]);

            $gatewayMessage = is_array($body) && !empty($body['message'])
                ? $body['message']
                : 'Unable to start Cashfree payment. Please try again.';

            throw new RuntimeException($gatewayMessage);
        }

        $cashfreeOrder = $response->json();
        if (! is_array($cashfreeOrder)
            || empty($cashfreeOrder['order_id'])
            || empty($cashfreeOrder['payment_session_id'])) {
            throw new RuntimeException('Cashfree did not return a usable payment session. Please try again.');
        }

        $order->payments()->updateOrCreate(
            ['provider' => 'cashfree', 'transaction_type' => 'capture'],
            [
                'status' => 'initiated',
                'currency' => $order->currency ?: 'INR',
                'amount' => $order->grand_total,
                'gateway_order_id' => $cashfreeOrder['order_id'] ?? $order->order_number,
                'gateway_payment_id' => isset($cashfreeOrder['cf_order_id']) ? (string) $cashfreeOrder['cf_order_id'] : null,
                'gateway_payload' => $cashfreeOrder,
                'notes' => 'Cashfree payment initiated',
            ]
        );

        return [
            'payment_session_id' => $cashfreeOrder['payment_session_id'] ?? null,
            'cashfree_order' => $cashfreeOrder,
            'mode' => $this->environment(),
        ];
    }

    public function fetchOrder(string $cashfreeOrderId): array
    {
        $response = $this->client()->get($this->baseUrl() . '/orders/' . urlencode($cashfreeOrderId));

        if (!$response->successful()) {
            throw new RuntimeException('Unable to verify Cashfree order.');
        }

        return $response->json();
    }

    public function syncOrderStatus(Order $order, ?array $cashfreeOrder = null): Order
    {
        $cashfreeOrder ??= $this->fetchOrder($order->order_number);

        if ($order->payment_status === 'paid') {
            return $order->refresh();
        }

        $status = strtoupper((string) ($cashfreeOrder['order_status'] ?? ''));

        $payment = $order->payments()->where('provider', 'cashfree')->latest()->first();

        if ($status === 'PAID') {
            $gatewayAmount = (int) round((float) ($cashfreeOrder['order_amount'] ?? -1) * 100);
            $localAmount = (int) round((float) $order->grand_total * 100);
            $gatewayCurrency = strtoupper((string) ($cashfreeOrder['order_currency'] ?? ''));
            $localCurrency = strtoupper((string) ($order->currency ?: 'INR'));

            if (($cashfreeOrder['order_id'] ?? null) !== $order->order_number
                || $gatewayAmount !== $localAmount
                || $gatewayCurrency !== $localCurrency) {
                Log::warning('Cashfree payment does not match the local order.', [
                    'order_id' => $order->id,
                    'cashfree_order_id' => $cashfreeOrder['order_id'] ?? null,
                    'cashfree_currency' => $gatewayCurrency,
                ]);

                return $order->refresh();
            }

            $previousStatus = $order->status;
            $previousFulfillmentStatus = $order->fulfillment_status;
            $previousPaymentStatus = $order->payment_status;
            $paymentPayload = $payment?->gateway_payload ?? [];
            $aiSensyAlreadySent = !empty($paymentPayload['aisensy_order_paid_sent_at']);

            $order->forceFill([
                'status' => $order->status === 'pending' ? 'confirmed' : $order->status,
                'payment_status' => 'paid',
                'payment_method' => 'cashfree',
            ])->save();

            if ($payment) {
                $payment->forceFill([
                    'status' => 'success',
                    'gateway_payload' => array_merge($payment->gateway_payload ?? [], ['verified_order' => $cashfreeOrder]),
                    'paid_at' => now(),
                    'notes' => 'Cashfree payment successful',
                ])->save();
            }

            if ($payment && !$aiSensyAlreadySent && $this->aiSensy->sendOrderPaid($order->refresh())) {
                $payment->forceFill([
                    'gateway_payload' => array_merge($payment->gateway_payload ?? [], [
                        'aisensy_order_paid_sent_at' => now()->toIso8601String(),
                    ]),
                ])->save();
            }

            if ($previousStatus !== $order->status) {
                $order->statusHistories()->create([
                    'from_status' => $previousStatus,
                    'to_status' => $order->status,
                    'from_fulfillment_status' => $previousFulfillmentStatus,
                    'to_fulfillment_status' => $order->fulfillment_status,
                    'updated_by' => $order->user_id,
                    'note' => 'Cashfree payment confirmed.',
                ]);
            }

            if ($previousPaymentStatus !== 'paid') {
                event(new \App\Events\OrderPaid($order->refresh()));
            }

            return $order->refresh();
        }

        if (in_array($status, ['EXPIRED', 'TERMINATED', 'TERMINATION_REQUESTED'], true)) {
            $order->forceFill(['payment_status' => 'failed'])->save();
            if ($payment) {
                $payment->forceFill([
                    'status' => 'failed',
                    'gateway_payload' => array_merge($payment->gateway_payload ?? [], ['verified_order' => $cashfreeOrder]),
                    'notes' => 'Cashfree payment not completed',
                ])->save();
            }
        }

        return $order->refresh();
    }

    public function webhookSignatureIsValid(string $rawBody, ?string $signature, ?string $timestamp): bool
    {
        if (!$signature || !$timestamp) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $timestamp . $rawBody, $this->secretKey(), true));

        return hash_equals($expected, $signature);
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withOptions(['proxy' => ''])
            ->withHeaders([
                'x-client-id' => $this->appId(),
                'x-client-secret' => $this->secretKey(),
                'x-api-version' => (string) config('services.cashfree.api_version', '2025-01-01'),
            ])
            ->timeout(20);
    }

    public static function resolveEnvironment(): string
    {
        $configEnv = (string) config('services.cashfree.env', '');
        if (in_array(strtolower(trim($configEnv)), ['production', 'sandbox'], true)) {
            return strtolower(trim($configEnv));
        }

        try {
            $dbEnv = \App\Models\Setting::get('cashfree_env');
            if (is_string($dbEnv) && in_array(strtolower(trim($dbEnv)), ['production', 'sandbox'], true)) {
                return strtolower(trim($dbEnv));
            }
        } catch (\Throwable) {
            // DB not ready or during early boot
        }

        $appId = (string) config('services.cashfree.app_id', '');
        if ($appId !== '') {
            if (stripos($appId, 'TEST') === 0) {
                return 'sandbox';
            }
            if (stripos($appId, 'CF') === 0) {
                return 'production';
            }
        }

        return 'sandbox';
    }

    public function environment(): string
    {
        return self::resolveEnvironment();
    }

    public static function resolveBaseUrl(): string
    {
        return self::resolveEnvironment() === 'production'
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';
    }

    private function baseUrl(): string
    {
        return self::resolveBaseUrl();
    }

    private function appId(): string
    {
        $appId = (string) config('services.cashfree.app_id');
        if ($appId === '') {
            throw new RuntimeException('Cashfree app id is not configured.');
        }

        return $appId;
    }

    public static function bootCheck(): void
    {
        $appId = (string) config('services.cashfree.app_id');
        $secretKey = (string) config('services.cashfree.secret_key');
        $env = self::resolveEnvironment();
        $baseUrl = self::resolveBaseUrl();

        if (empty($appId)) {
            throw new RuntimeException('Cashfree configuration error: CASHFREE_APP_ID is missing.');
        }

        if (empty($secretKey)) {
            throw new RuntimeException('Cashfree configuration error: CASHFREE_SECRET_KEY is missing.');
        }

        if (str_starts_with($secretKey, 'cfsk_ma_test_') && $env === 'production') {
            throw new RuntimeException('Cashfree configuration mismatch: Sandbox test secret key (cfsk_ma_test_...) cannot be used with production base URL (' . $baseUrl . ').');
        }

        if (str_starts_with($secretKey, 'cfsk_ma_prod_') && $env === 'sandbox') {
            throw new RuntimeException('Cashfree configuration mismatch: Production secret key (cfsk_ma_prod_...) cannot be used with sandbox base URL (' . $baseUrl . ').');
        }

    }

    public function validateConfiguration(): void
    {
        self::bootCheck();
    }

    private function secretKey(): string
    {
        $secretKey = (string) config('services.cashfree.secret_key');
        if ($secretKey === '') {
            throw new RuntimeException('Cashfree secret key is not configured.');
        }

        return $secretKey;
    }
}
