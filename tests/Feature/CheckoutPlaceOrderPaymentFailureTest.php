<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\Payments\CashfreePaymentService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutPlaceOrderPaymentFailureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private CustomerAddress $address;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.cashfree.app_id' => 'test-app-id',
            'services.cashfree.secret_key' => 'cfsk_ma_test_secret_123',
            'services.cashfree.env' => 'sandbox',
            'services.aisensy.enabled' => false,
        ]);

        $this->user = User::factory()->create();
        $category = Category::factory()->create();
        $taxRate = TaxRate::factory()->create(['rate' => 0, 'code' => 'GST0']);
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'base_price' => 9677,
            'shipping_price' => 122,
            'is_active' => true,
        ]);

        Inventory::factory()->create([
            'product_id' => $this->product->id,
            'stock_qty' => 50,
            'reserved_qty' => 0,
            'track_stock' => true,
        ]);

        $this->address = CustomerAddress::create([
            'user_id' => $this->user->id,
            'label' => 'Home',
            'full_name' => $this->user->name,
            'phone' => '9999999999',
            'email' => $this->user->email,
            'address_line_1' => 'Test Street',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'postal_code' => '380001',
            'country' => 'India',
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]);
    }

    public function test_place_order_returns_payment_session_when_cashfree_succeeds(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->actingAs($this->user);

        $this->postJson(route('user.cart.store'), [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ])->assertOk();

        $summary = $this->postJson(route('user.checkout.summary'))->assertOk()->json();

        Http::fake([
            'https://sandbox.cashfree.com/pg/orders' => Http::response([
                'order_id' => 'NB-TEST-1',
                'cf_order_id' => 123456,
                'payment_session_id' => 'session_xyz_789',
                'order_status' => 'ACTIVE',
            ], 200),
        ]);

        $response = $this->postJson(route('user.checkout.place-order'), [
            'address_id' => $this->address->id,
            'payment_method' => 'cashfree',
            'checkout_token' => $summary['checkout_token'],
        ]);

        $response->assertOk()
            ->assertJsonPath('payment.provider', 'cashfree')
            ->assertJsonPath('payment.payment_session_id', 'session_xyz_789');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_place_order_handles_cashfree_401_gracefully_without_500(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->actingAs($this->user);

        $this->postJson(route('user.cart.store'), [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ])->assertOk();

        $summary = $this->postJson(route('user.checkout.summary'))->assertOk()->json();

        Http::fake([
            'https://sandbox.cashfree.com/pg/orders' => Http::response([
                'message' => 'authentication Failed',
                'code' => 'request_failed',
                'type' => 'authentication_error',
            ], 401),
        ]);

        $response = $this->postJson(route('user.checkout.place-order'), [
            'address_id' => $this->address->id,
            'payment_method' => 'cashfree',
            'checkout_token' => $summary['checkout_token'],
        ]);

        // Must NOT be a 500 error
        $this->assertNotEquals(500, $response->status());
        $response->assertStatus(502)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'PAYMENT_INIT_FAILED')
            ->assertJsonPath('message', 'authentication Failed');

        // Order transaction must be rolled back, cart preserved
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_cashfree_configuration_validation_rejects_test_secret_in_production(): void
    {
        config([
            'services.cashfree.app_id' => 'CF_TEST_APP_ID_12345',
            'services.cashfree.secret_key' => 'cfsk_ma_test_dummy_secret_for_testing_only',
            'services.cashfree.env' => 'production',
        ]);

        $service = app(CashfreePaymentService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cashfree configuration mismatch');

        $service->validateConfiguration();
    }

    public function test_cashfree_rejects_zero_or_negative_order_amount(): void
    {
        $order = Order::factory()->create([
            'grand_total' => 0.00,
            'payment_method' => 'cashfree',
        ]);

        $service = app(CashfreePaymentService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order amount must be greater than zero.');

        $service->createOrder($order);
    }

    public function test_cashfree_boot_check_rejects_production_secret_in_sandbox(): void
    {
        config([
            'services.cashfree.app_id' => 'CF_TEST_APP_ID_12345',
            'services.cashfree.secret_key' => 'cfsk_ma_prod_dummy_secret_for_testing_only',
            'services.cashfree.env' => 'sandbox',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Production secret key (cfsk_ma_prod_...) cannot be used with sandbox');

        CashfreePaymentService::bootCheck();
    }

    public function test_cashfree_boot_check_rejects_empty_app_id(): void
    {
        config([
            'services.cashfree.app_id' => '',
            'services.cashfree.secret_key' => 'cfsk_ma_test_123',
            'services.cashfree.env' => 'sandbox',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CASHFREE_APP_ID is missing');

        CashfreePaymentService::bootCheck();
    }

    public function test_cashfree_boot_check_rejects_empty_secret_key(): void
    {
        config([
            'services.cashfree.app_id' => 'CF11073839DBE845R88FKC73CIR9DG',
            'services.cashfree.secret_key' => '',
            'services.cashfree.env' => 'sandbox',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CASHFREE_SECRET_KEY is missing');

        CashfreePaymentService::bootCheck();
    }
}
