<?php

namespace Tests\Feature;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RazorpayOrderPaymentRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.razorpay.key_id' => 'test-key-id',
            'services.razorpay.key_secret' => 'test-key-secret',
            'services.razorpay.webhook_secret' => 'test-webhook-secret',
            'services.aisensy.enabled' => false,
        ]);
    }

    public function test_valid_verification_confirms_only_the_matching_local_order(): void
    {
        Event::fake();
        $order = Order::factory()->create([
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'razorpay',
            'grand_total' => 250,
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'razorpay',
            'status' => 'initiated',
            'amount' => 250,
            'gateway_order_id' => 'order_gateway_123',
        ]);
        Http::fake([
            'https://api.razorpay.com/v1/payments/payment_gateway_123' => Http::response([
                'id' => 'payment_gateway_123',
                'order_id' => 'order_gateway_123',
                'amount' => 25000,
                'currency' => 'INR',
                'status' => 'captured',
            ]),
        ]);

        $this->postJson(route('razorpay.verify'), $this->signedPayload($order->id, 'order_gateway_123'))
            ->assertOk()
            ->assertExactJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'razorpay',
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'success',
            'gateway_payment_id' => 'payment_gateway_123',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'pending',
            'to_status' => 'confirmed',
        ]);
        Event::assertDispatchedTimes(OrderPaid::class, 1);
    }

    public function test_valid_signature_does_not_confirm_an_uncaptured_or_mismatched_payment(): void
    {
        Event::fake();
        $order = Order::factory()->create([
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'razorpay',
            'grand_total' => 250,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'provider' => 'razorpay',
            'status' => 'initiated',
            'amount' => 250,
            'gateway_order_id' => 'order_gateway_123',
        ]);
        Http::fake([
            'https://api.razorpay.com/v1/payments/payment_gateway_123' => Http::response([
                'id' => 'payment_gateway_123',
                'order_id' => 'order_gateway_123',
                'amount' => 25000,
                'currency' => 'INR',
                'status' => 'authorized',
            ]),
        ]);

        $this->postJson(route('razorpay.verify'), $this->signedPayload($order->id, 'order_gateway_123'))
            ->assertStatus(400)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'pending',
        ]);
        Event::assertNotDispatched(OrderPaid::class);
    }

    public function test_razorpay_order_creation_uses_the_server_amount_and_reuses_the_gateway_order(): void
    {
        $order = Order::factory()->create([
            'payment_status' => 'pending',
            'payment_method' => 'razorpay',
            'grand_total' => 250.50,
        ]);
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_gateway_create_123',
                'amount' => 25050,
                'currency' => 'INR',
            ]),
        ]);

        $service = app(\App\Services\Payments\RazorpayPaymentService::class);
        $firstResult = $service->createOrder($order);
        $secondResult = $service->createOrder($order->fresh());

        $this->assertSame('order_gateway_create_123', $firstResult['id']);
        $this->assertSame($firstResult, $secondResult);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'razorpay',
            'gateway_order_id' => 'order_gateway_create_123',
        ]);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.razorpay.com/v1/orders'
            && (int) $request['amount'] === 25050
            && $request['currency'] === 'INR');
    }

    public function test_valid_signature_for_an_unrelated_gateway_order_cannot_mark_an_order_paid(): void
    {
        Event::fake();
        $order = Order::factory()->create([
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'razorpay',
        ]);
        $attackerOrder = Order::factory()->create([
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'razorpay',
        ]);
        Payment::create([
            'order_id' => $order->id,
            'provider' => 'razorpay',
            'status' => 'initiated',
            'amount' => 250,
            'gateway_order_id' => 'order_for_this_local_order',
        ]);
        Payment::create([
            'order_id' => $attackerOrder->id,
            'provider' => 'razorpay',
            'status' => 'initiated',
            'amount' => 250,
            'gateway_order_id' => 'order_for_some_other_order',
        ]);

        $this->postJson(route('razorpay.verify'), $this->signedPayload($order->id, 'order_for_some_other_order'))
            ->assertStatus(400)
            ->assertExactJson([
                'success' => false,
                'message' => 'Payment signature verification failed.',
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseCount('order_status_histories', 0);
        Event::assertNotDispatched(OrderPaid::class);
    }

    public function test_replayed_signed_webhook_does_not_duplicate_order_paid_side_effects(): void
    {
        Event::fake();
        $order = Order::factory()->create([
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'razorpay',
            'grand_total' => 250,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'provider' => 'razorpay',
            'status' => 'initiated',
            'amount' => 250,
            'gateway_order_id' => 'order_webhook_123',
        ]);
        $payload = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => ['entity' => [
                    'order_id' => 'order_webhook_123',
                    'id' => 'payment_webhook_123',
                    'status' => 'captured',
                    'captured' => true,
                    'amount' => 25000,
                    'currency' => 'INR',
                ]],
            ],
        ];
        $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);
        $headers = [
            'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $rawBody, 'test-webhook-secret'),
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->call('POST', route('razorpay.webhook'), [], [], [], $headers, $rawBody)->assertOk();
        $this->call('POST', route('razorpay.webhook'), [], [], [], $headers, $rawBody)->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseCount('order_status_histories', 1);
        Event::assertDispatchedTimes(OrderPaid::class, 1);
    }

    public function test_razorpay_webhook_rejects_invalid_signatures_and_does_not_confirm_mismatched_amounts(): void
    {
        Event::fake();
        $order = Order::factory()->create([
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'razorpay',
            'grand_total' => 250,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'provider' => 'razorpay',
            'status' => 'initiated',
            'amount' => 250,
            'gateway_order_id' => 'order_webhook_mismatch',
        ]);
        $payload = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'order_id' => 'order_webhook_mismatch',
                'id' => 'payment_webhook_mismatch',
                'status' => 'captured',
                'captured' => true,
                'amount' => 24900,
                'currency' => 'INR',
            ]]],
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->call('POST', route('razorpay.webhook'), [], [], [], [
            'HTTP_X_RAZORPAY_SIGNATURE' => 'invalid',
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertUnauthorized();

        $this->call('POST', route('razorpay.webhook'), [], [], [], [
            'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $body, 'test-webhook-secret'),
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'pending']);
        Event::assertNotDispatched(OrderPaid::class);
    }

    /** @return array{order_id: int, razorpay_order_id: string, razorpay_payment_id: string, razorpay_signature: string} */
    private function signedPayload(int $localOrderId, string $gatewayOrderId): array
    {
        $gatewayPaymentId = 'payment_gateway_123';

        return [
            'order_id' => $localOrderId,
            'razorpay_order_id' => $gatewayOrderId,
            'razorpay_payment_id' => $gatewayPaymentId,
            'razorpay_signature' => hash_hmac(
                'sha256',
                $gatewayOrderId . '|' . $gatewayPaymentId,
                'test-key-secret',
            ),
        ];
    }
}
