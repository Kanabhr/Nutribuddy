<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\Checkout\CheckoutSummaryService;
use App\Services\Checkout\OrderPlacementService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionProperty;
use Tests\TestCase;

/**
 * R9 (COD fee) and R10 (online-switch discount) through the checkout summary and order placement.
 * Cart: 2 x Rs 500 = Rs 1000, GST 18% = 180, shipping 2 x 50 = 100. Payable without extras = 1280.
 */
class CodFeeOnlineSwitchCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_TOTAL = 1280.0;

    private User $user;

    private CustomerAddress $address;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->clearSettingCache();

        Setting::set('cod_enabled', '1');
        Setting::set('cod_otp_enabled', '0');
        Setting::set('cod_fee', 30);
        Setting::set('online_switch_discount_enabled', '1');
        Setting::set('online_switch_discount_amount', 20);
        Setting::set('loyalty_earn_percent', 0.5);
        Setting::set('loyalty_conversion_rate', 10);

        $this->user = User::factory()->create();
        $category = Category::factory()->create();
        $taxRate = TaxRate::factory()->create(['rate' => 18, 'code' => 'GST18']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'base_price' => 500,
            'coins_reward' => 100,
            'shipping_price' => 50,
            'is_active' => true,
        ]);
        Inventory::factory()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'track_stock' => true,
            'stock_qty' => 50,
            'reserved_qty' => 0,
        ]);
        $this->address = CustomerAddress::create([
            'user_id' => $this->user->id,
            'label' => 'Home',
            'full_name' => $this->user->name,
            'phone' => '9999999999',
            'email' => $this->user->email,
            'address_line_1' => 'Street 1',
            'address_line_2' => null,
            'landmark' => null,
            'city' => 'Noida',
            'state' => 'UP',
            'postal_code' => '201301',
            'country' => 'India',
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]);

        $this->actingAs($this->user);
        $this->postJson(route('user.cart.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertOk();
    }

    protected function tearDown(): void
    {
        $this->clearSettingCache();
        parent::tearDown();
    }

    private function clearSettingCache(): void
    {
        $property = new ReflectionProperty(Setting::class, 'cachedValues');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    private function summary(?string $method = null): array
    {
        $payload = $method === null ? [] : ['payment_method' => $method];

        return $this->postJson(route('user.checkout.summary'), $payload)->assertOk()->json();
    }

    private function place(string $method): Order
    {
        $result = app(OrderPlacementService::class)->place($this->user, [
            'address_id' => $this->address->id,
            'payment_method' => $method,
            'checkout_token' => 'tok-'.uniqid(),
        ]);

        return $result['order'];
    }

    // ------------------------------------------------------ flag handling

    public function test_page_load_summary_without_payment_method_sets_no_flag_and_adds_nothing(): void
    {
        $data = $this->summary();

        $this->assertFalse(session()->has(CheckoutSummaryService::COD_SELECTED_SESSION_KEY));
        $this->assertEquals(self::BASE_TOTAL, $data['pricing']['grand_total']);
        $this->assertEquals(0, $data['pricing']['cod_fee']);
        $this->assertEquals(0, $data['pricing']['online_switch_discount']);
    }

    public function test_online_summary_without_ever_selecting_cod_gets_no_discount(): void
    {
        $data = $this->summary('cashfree');

        $this->assertFalse(session()->has(CheckoutSummaryService::COD_SELECTED_SESSION_KEY));
        $this->assertEquals(self::BASE_TOTAL, $data['pricing']['grand_total']);
        $this->assertEquals(0, $data['pricing']['online_switch_discount']);
    }

    public function test_selecting_cod_sets_the_flag_and_shows_the_fee(): void
    {
        $data = $this->summary('cod');

        $this->assertTrue(session()->get(CheckoutSummaryService::COD_SELECTED_SESSION_KEY));
        $this->assertEquals(30, $data['pricing']['cod_fee']);
        $this->assertEquals(self::BASE_TOTAL + 30, $data['pricing']['grand_total']);
    }

    public function test_flag_is_not_set_when_cod_is_disabled(): void
    {
        Setting::set('cod_enabled', '0');

        $this->summary('cod');

        $this->assertFalse(session()->has(CheckoutSummaryService::COD_SELECTED_SESSION_KEY));
    }

    public function test_summary_shows_the_switch_discount_when_flag_is_set_and_online_is_selected(): void
    {
        $this->summary('cod');
        $online = $this->summary('cashfree');

        $this->assertEquals(20, $online['pricing']['online_switch_discount']);
        $this->assertEquals(0, $online['pricing']['cod_fee']);
        $this->assertEquals(self::BASE_TOTAL - 20, $online['pricing']['grand_total']);
    }

    public function test_summary_total_equals_the_charged_total_for_the_switch(): void
    {
        $this->summary('cod');
        $shown = $this->summary('cashfree');

        $order = $this->place('cashfree');

        $this->assertEquals($shown['pricing']['grand_total'], (float) $order->grand_total);
        $this->assertEquals(20, data_get($order->pricing_snapshot, 'online_switch_discount'));
        $this->assertEquals(0, data_get($order->pricing_snapshot, 'cod_fee'));
        $this->assertEquals((float) $order->grand_total, (float) $order->payments()->first()->amount);
    }

    public function test_switch_discount_is_not_given_when_the_setting_is_off(): void
    {
        Setting::set('online_switch_discount_enabled', '0');

        $this->summary('cod');
        $online = $this->summary('cashfree');
        $order = $this->place('razorpay');

        $this->assertEquals(0, $online['pricing']['online_switch_discount']);
        $this->assertEquals(self::BASE_TOTAL, (float) $order->grand_total);
        $this->assertEquals(0, data_get($order->pricing_snapshot, 'online_switch_discount', 0));
    }

    public function test_direct_online_order_gets_no_discount_and_no_fee(): void
    {
        $order = $this->place('cashfree');

        $this->assertEquals(self::BASE_TOTAL, (float) $order->grand_total);
        $this->assertEquals(0, data_get($order->pricing_snapshot, 'online_switch_discount', 0));
        $this->assertEquals(0, data_get($order->pricing_snapshot, 'cod_fee', 0));
    }

    public function test_flag_is_cleared_after_an_order_is_placed(): void
    {
        $this->summary('cod');
        $this->assertTrue(session()->has(CheckoutSummaryService::COD_SELECTED_SESSION_KEY));

        $this->place('cashfree');

        $this->assertFalse(session()->has(CheckoutSummaryService::COD_SELECTED_SESSION_KEY));
    }

    public function test_a_second_direct_online_order_after_a_switch_gets_no_discount(): void
    {
        $this->summary('cod');
        $first = $this->place('cashfree');
        $this->assertEquals(self::BASE_TOTAL - 20, (float) $first->grand_total);

        // New cart for the next checkout.
        $product = Product::query()->first();
        $this->postJson(route('user.cart.store'), ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        $second = $this->place('cashfree');

        $this->assertEquals(self::BASE_TOTAL, (float) $second->grand_total);
    }

    // ------------------------------------------------------------- COD orders

    public function test_cod_order_total_includes_the_fee_and_snapshot_stores_it(): void
    {
        $order = $this->place('cod');

        $this->assertEquals(self::BASE_TOTAL + 30, (float) $order->grand_total);
        $this->assertEquals(30, data_get($order->pricing_snapshot, 'cod_fee'));
        $this->assertEquals(0, data_get($order->pricing_snapshot, 'online_switch_discount'));
        $this->assertEquals((float) $order->grand_total, (float) $order->payments()->first()->amount);
        // Components that must not carry the fee.
        $this->assertEquals(1000.0, (float) $order->subtotal);
        $this->assertEquals(180.0, (float) $order->tax_total);
        $this->assertEquals(100.0, (float) $order->shipping_total);
    }

    public function test_cod_fee_does_not_change_coins_earned(): void
    {
        $cod = $this->place('cod');

        $this->postJson(route('user.cart.store'), ['product_id' => Product::query()->value('id'), 'quantity' => 2])->assertOk();
        $online = $this->place('cashfree');

        $this->assertSame(50, (int) data_get($cod->pricing_snapshot, 'coins_earned'));
        $this->assertSame(50, (int) data_get($online->pricing_snapshot, 'coins_earned'));
    }

    public function test_cod_order_through_the_place_order_route_keeps_the_existing_response_shape(): void
    {
        $summary = $this->summary('cod');

        $this->postJson(route('user.checkout.place-order'), [
            'address_id' => $this->address->id,
            'payment_method' => 'cod',
            'checkout_token' => $summary['checkout_token'],
        ])->assertOk()
            ->assertJsonPath('message', 'Order placed successfully.')
            ->assertJsonPath('order.payment_method', 'cod');

        $order = Order::query()->firstOrFail();
        $this->assertEquals(self::BASE_TOTAL + 30, (float) $order->grand_total);
    }

    // ------------------------------------------------------------ old orders

    public function test_old_orders_without_the_new_snapshot_keys_read_as_zero(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'pricing_snapshot' => ['coins_earned' => 5],
        ]);

        $this->assertSame(0, (int) data_get($order->pricing_snapshot, 'cod_fee', 0));
        $this->assertSame(0, (int) data_get($order->pricing_snapshot, 'online_switch_discount', 0));

        $nullSnapshot = Order::factory()->create(['user_id' => $this->user->id, 'pricing_snapshot' => null]);
        $this->assertSame(0, (int) data_get($nullSnapshot->pricing_snapshot, 'cod_fee', 0));
    }

    public function test_invoice_page_shows_the_extra_rows_only_when_present(): void
    {
        $withFee = $this->place('cod');
        $this->get(route('user.orders.invoice-page', $withFee))
            ->assertOk()
          ->assertSee('COD Fee');

        // An order stored before this change (no keys in the snapshot) renders without the rows.
        $snapshot = $withFee->pricing_snapshot;
        unset($snapshot['cod_fee'], $snapshot['online_switch_discount']);
        $withFee->forceFill(['pricing_snapshot' => $snapshot])->save();

        $this->get(route('user.orders.invoice-page', $withFee))
            ->assertOk()
            ->assertDontSee('COD Fee (non-taxable)');
    }
}
