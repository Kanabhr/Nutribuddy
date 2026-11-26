<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Setting;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionProperty;
use Tests\TestCase;

/** R1 (coin base), R9 (COD fee), R10 (online-switch discount) at the pricing layer. */
class PricingPaymentAdjustmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearSettingCache();
        Setting::set('loyalty_enabled', 1);
        Setting::set('loyalty_conversion_rate', 10);
        Setting::set('loyalty_max_redemption_percent', 30);
        Setting::set('loyalty_max_redeemable_coins', 0);
        Setting::set('loyalty_earn_percent', 0.5);
        Setting::set('cod_fee', 0);
        Setting::set('online_switch_discount_enabled', '0');
        Setting::set('online_switch_discount_amount', 20);
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

    /** 2 x Rs 500 = Rs 1000 products, 18% GST (180), shipping Rs 50 x 2 = 100. */
    private function cartItems(float $price = 500, int $qty = 2, float $shippingPerUnit = 50)
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $taxRate = TaxRate::query()->where('code', 'GST18')->first()
            ?? TaxRate::factory()->create(['rate' => 18, 'code' => 'GST18']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'base_price' => $price,
            'shipping_price' => $shippingPerUnit,
            'coins_reward' => 999, // must be ignored after R1
        ]);
        $cart = Cart::create(['user_id' => $user->id, 'currency' => 'INR']);
        $cartItem = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => $qty]);
        $cartItem->load(['product.taxRate', 'productVariant']);

        return collect([$cartItem]);
    }

    // ---------------------------------------------------------------- R1

    public function test_coins_earned_use_the_product_total_and_ignore_per_product_reward(): void
    {
        $result = app(PricingService::class)->calculate($this->cartItems());

        $this->assertSame(1000.0, $result['subtotal']);
        $this->assertSame(50, $result['total_coins_earned']); // 0.5% x 1000 x 10, not 999
    }

    public function test_coins_earned_exclude_gst_and_shipping(): void
    {
        $withShipping = app(PricingService::class)->calculate($this->cartItems(500, 2, 50));
        $noShipping = app(PricingService::class)->calculate($this->cartItems(500, 2, 0));

        $this->assertGreaterThan($noShipping['grand_total'], $withShipping['grand_total']);
        $this->assertSame(50, $withShipping['total_coins_earned']);
        $this->assertSame(50, $noShipping['total_coins_earned']);
    }

    public function test_coins_earned_are_the_same_before_and_after_coupon_and_coin_discounts(): void
    {
        $coupon = Coupon::create([
            'code' => 'SAVE20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
        ]);

        $plain = app(PricingService::class)->calculate($this->cartItems());
        $discounted = app(PricingService::class)->calculate($this->cartItems(), $coupon, 100000);

        $this->assertGreaterThan(0.0, $discounted['discount_total']);
        $this->assertGreaterThan(0, $discounted['coins_redeemed']);
        $this->assertSame($plain['total_coins_earned'], $discounted['total_coins_earned']);
        $this->assertSame(50, $discounted['total_coins_earned']);
    }

    // ---------------------------------------------------------------- R9

    public function test_cod_fee_is_added_for_cod_only_and_earns_no_coins(): void
    {
        Setting::set('cod_fee', 30);
        $service = app(PricingService::class);
        $base = $service->calculate($this->cartItems());

        $cod = $service->applyPaymentMethodAdjustments($base, 'cod', false);

        $this->assertSame(30.0, $cod['cod_fee']);
        $this->assertSame($base['grand_total'] + 30.0, $cod['grand_total']);
        $this->assertSame(0.0, $cod['online_switch_discount']);
        $this->assertSame($base['total_coins_earned'], $cod['total_coins_earned']);
        $this->assertSame($base['tax_total'], $cod['tax_total']); // fee is non-taxable
    }

    public function test_online_orders_do_not_pay_the_cod_fee(): void
    {
        Setting::set('cod_fee', 30);
        $service = app(PricingService::class);
        $base = $service->calculate($this->cartItems());

        foreach (['cashfree', 'razorpay'] as $method) {
            $result = $service->applyPaymentMethodAdjustments($base, $method, false);
            $this->assertSame(0.0, $result['cod_fee']);
            $this->assertSame($base['grand_total'], $result['grand_total']);
        }
    }

    public function test_no_payment_method_leaves_totals_unchanged(): void
    {
        Setting::set('cod_fee', 30);
        Setting::set('online_switch_discount_enabled', '1');
        $service = app(PricingService::class);
        $base = $service->calculate($this->cartItems());

        $result = $service->applyPaymentMethodAdjustments($base, null, true);

        $this->assertSame($base['grand_total'], $result['grand_total']);
        $this->assertSame(0.0, $result['cod_fee']);
        $this->assertSame(0.0, $result['online_switch_discount']);
    }

    public function test_zero_fee_default_changes_nothing_for_cod(): void
    {
        $service = app(PricingService::class);
        $base = $service->calculate($this->cartItems());

        $cod = $service->applyPaymentMethodAdjustments($base, 'cod', false);

        $this->assertSame(0.0, $cod['cod_fee']);
        $this->assertSame($base['grand_total'], $cod['grand_total']);
    }

    public function test_invalid_fee_setting_is_treated_as_zero(): void
    {
        Setting::set('cod_fee', 'abc');
        $this->assertSame(0.0, PricingService::codFeeAmount());

        Setting::set('cod_fee', -10);
        $this->assertSame(0.0, PricingService::codFeeAmount());
    }

    // --------------------------------------------------------------- R10

    public function test_switch_discount_applies_only_when_cod_was_selected_and_setting_is_on(): void
    {
        Setting::set('cod_fee', 30);
        Setting::set('online_switch_discount_enabled', '1');
        $service = app(PricingService::class);
        $base = $service->calculate($this->cartItems());

        $switched = $service->applyPaymentMethodAdjustments($base, 'cashfree', true);
        $this->assertSame(20.0, $switched['online_switch_discount']);
        $this->assertSame(0.0, $switched['cod_fee']); // COD fee dropped on the switch
        $this->assertSame($base['grand_total'] - 20.0, $switched['grand_total']);

        $direct = $service->applyPaymentMethodAdjustments($base, 'cashfree', false);
        $this->assertSame(0.0, $direct['online_switch_discount']);
        $this->assertSame($base['grand_total'], $direct['grand_total']);
    }

    public function test_switch_discount_is_off_by_default_and_when_disabled(): void
    {
        $service = app(PricingService::class);
        $base = $service->calculate($this->cartItems());

        $result = $service->applyPaymentMethodAdjustments($base, 'razorpay', true);

        $this->assertSame(0.0, $result['online_switch_discount']);
        $this->assertSame($base['grand_total'], $result['grand_total']);
    }

    public function test_switch_discount_never_takes_the_total_below_zero(): void
    {
        Setting::set('online_switch_discount_enabled', '1');
        Setting::set('online_switch_discount_amount', 5000);
        $service = app(PricingService::class);
        $base = $service->calculate($this->cartItems(10, 1, 0));

        $result = $service->applyPaymentMethodAdjustments($base, 'cashfree', true);

        $this->assertSame(0.0, $result['grand_total']);
        $this->assertSame($base['grand_total'], $result['online_switch_discount']); // only what was payable
    }

    public function test_switch_discount_stacks_after_coupon_and_coins(): void
    {
        Setting::set('online_switch_discount_enabled', '1');
        $coupon = Coupon::create([
            'code' => 'SAVE20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
        ]);
        $service = app(PricingService::class);
        $base = $service->calculate($this->cartItems(), $coupon, 100000);

        $result = $service->applyPaymentMethodAdjustments($base, 'cashfree', true);

        $this->assertGreaterThan(0.0, $base['discount_total']);
        $this->assertGreaterThan(0.0, $base['coin_discount']);
        $this->assertSame($base['discount_total'], $result['discount_total']);
        $this->assertSame($base['coin_discount'], $result['coin_discount']);
        $this->assertSame($base['grand_total'] - 20.0, $result['grand_total']);
    }
}