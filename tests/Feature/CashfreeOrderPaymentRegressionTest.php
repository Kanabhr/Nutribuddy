<?php

namespace Tests\Feature;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\CashfreePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class CashfreeOrderPaymentRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.cashfree.app_id' => 'test-app-id',
            'services.cashfree.secret_key' => 'test-secret-key',
            'services.cashfree.env' => 'sandbox',
            'services.aisensy.enabled' => false,
        ]);
    }

    public function test_order_creation_uses_the_sandbox_and_persists_the_payment_session(): void
    {
        $order = Order::factory()->create([
            'payment_status' => 'pending',
            'payment_method' => 'cashfree',
            'grand_total' => 123.45,
        ]);
        Http::fake([
            'https://sandbox.cashfree.com/pg/orders' => Http::response([
                'order_id' => (string) $order->order_number,
                'cf_order_id' => 789123,
                'payment_session_id' => 'session_test_123',
            ]),
        ]);

        $result = app(CashfreePaymentService::class)->createOrder($order);
        $retryResult = app(CashfreePaymentService::class)->createOrder($order->fresh());

        $this->assertSame('session_test_123', $result['payment_session_id']);
        $this->assertSame($result, $retryResult);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'cashfree',
            'status' => 'initiated',
            'gateway_order_id' => (string) $order->order_number,
            'gateway_payment_id' => '789123',
        ]);
        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox.cashfree.com/pg/orders'
            && $request['order_amount'] === 123.45
            && $request['order_currency'] === 'INR'
            && $request->hasHeader('x-idempotency-key'));
        Http::assertSentCount(1);
    }

    public function test_order_creation_rejects_a_response_without_a_payment_session(): void
    {
        $order = Order::factory()->create([
            'payment_status' => 'pending',
            'payment_method' => 'cashfree',
            'grand_total' => 123.45,
        ]);
        Http::fake([
            'https://sandbox.cashfree.com/pg/orders' => Http::response([
                'order_id' => (string) $order->order_number,
            ]),
        ]);

        try {
            app(CashfreePaymentService::class)->createOrder($order);
            $this->fail('Cashfree must return a payment session before the checkout can continue.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('usable payment session', $exception->getMessage());
        }

        $this->assertDatabaseMissing('payments', [
            'order_id' => $order->id,
            'provider' => 'cashfree',
        ]);
    }

    public function test_paid_cashfree_order_with_a_mismatched_amount_is_not_confirmed(): void
    {
        Event::fake();
        $order = Order::factory()->create([
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cashfree',
            'grand_total' => 100,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'provider' => 'cashfree',
            'transaction_type' => 'capture',
            'status' => 'initiated',
            'amount' => 100,
            'currency' => 'INR',
            'gateway_order_id' => (string) $order->order_number,
        ]);

        app(CashfreePaymentService::class)->syncOrderStatus($order, [
            'order_id' => (string) $order->order_number,
            'order_status' => 'PAID',
            'order_amount' => 99,
            'order_currency' => 'INR',
        ]);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'pending']);
        Event::assertNotDispatched(OrderPaid::class);
    }

    public function test_signed_cashfree_webhook_confirms_only_the_matching_paid_order(): void
    {
        Event::fake();
        $order = Order::factory()->create([
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cashfree',
            'grand_total' => 100,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'provider' => 'cashfree',
            'transaction_type' => 'capture',
            'status' => 'initiated',
            'amount' => 100,
            'currency' => 'INR',
            'gateway_order_id' => (string) $order->order_number,
        ]);
        Http::fake([
            'https://sandbox.cashfree.com/pg/orders/' . $order->order_number => Http::response([
                'order_id' => (string) $order->order_number,
                'order_status' => 'PAID',
                'order_amount' => 100,
                'order_currency' => 'INR',
            ]),
        ]);
        $body = json_encode([
            'data' => ['order' => ['order_id' => (string) $order->order_number]],
        ], JSON_THROW_ON_ERROR);
        $timestamp = '1730000000';
        $signature = base64_encode(hash_hmac('sha256', $timestamp . $body, 'test-secret-key', true));

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->call('POST', route('cashfree.webhook'), [], [], [], [
                'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
                'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
                'CONTENT_TYPE' => 'application/json',
            ], $body)->assertOk()->assertExactJson(['ok' => true]);
        }

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'cashfree',
        ]);
        Event::assertDispatchedTimes(OrderPaid::class, 1);
    }

    public function test_cashfree_webhook_rejects_an_invalid_signature(): void
    {
        $this->call('POST', route('cashfree.webhook'), [], [], [], [
            'HTTP_X_WEBHOOK_SIGNATURE' => 'invalid',
            'HTTP_X_WEBHOOK_TIMESTAMP' => '1730000000',
            'CONTENT_TYPE' => 'application/json',
        ], '{"data":{}}')->assertUnauthorized();
    }
}
