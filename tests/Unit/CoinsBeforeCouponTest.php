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

/** R11: coins are applied first, then the coupon on what is left. */
class CoinsBeforeCouponTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $p = new ReflectionProperty(Setting::class, 'cachedValues');
        $p->setAccessible(true);
        $p->setValue(null, []);
        Setting::set('loyalty_enabled', 1);
        Setting::set('loyalty_conversion_rate', 10);
        Setting::set('loyalty_max_redemption_percent', 30);
        Setting::set('loyalty_max_redeemable_coins', 0);
    }

    private function items()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tax = TaxRate::query()->where('code', 'GST18')->first()
            ?? TaxRate::factory()->create(['rate' => 18, 'code' => 'GST18']);
        $product = Product::factory()->create([
            'category_id' => $category->id, 'tax_rate_id' => $tax->id, 'base_price' => 500, 'shipping_price' => 0,
        ]);
        $cart = Cart::create(['user_id' => $user->id, 'currency' => 'INR']);
        $item = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 2]);
        $item->load(['product.taxRate', 'productVariant']);

        return collect([$item]);
    }

    private function percentCoupon(): Coupon
    {
        return Coupon::create(['code' => 'SAVE20', 'discount_type' => 'percentage', 'discount_value' => 20, 'is_active' => true]);
    }

    public function test_coin_discount_is_the_same_with_or_without_a_coupon(): void
    {
        $service = app(PricingService::class);
        $items = $this->items();

        $coinsOnly = $service->calculate($items, null, 1000);
        $both = $service->calculate($items, $this->percentCoupon(), 1000);

        $this->assertSame(1000, $coinsOnly['coins_redeemed']);
        $this->assertSame($coinsOnly['coin_discount'], $both['coin_discount']);
        $this->assertSame($coinsOnly['coins_redeemed'], $both['coins_redeemed']);
    }

    public function test_percentage_coupon_is_taken_on_the_full_cart_value_not_after_coins(): void
    {
        $service = app(PricingService::class);
        $items = $this->items();

        $coupon = $this->percentCoupon();
        $couponOnly = $service->calculate($items, $coupon, 0);
        $both = $service->calculate($items, $coupon, 1000); // 1000 coins = Rs 100

        $this->assertSame($couponOnly['discount_total'], $both['discount_total']);
        $this->assertSame(100.0, (float) $both['coin_discount']);
        $this->assertSame(
            round($both['subtotal'] + $both['tax_total'] + $both['shipping_total'] - $both['discount_total'] - $both['coin_discount'], 0),
            (float) $both['grand_total']
        );
    }

    public function test_fixed_coupon_is_capped_to_what_is_left_after_coins_and_total_never_goes_negative(): void
    {
        $service = app(PricingService::class);
        $items = $this->items();
        $coupon = Coupon::create(['code' => 'BIG', 'discount_type' => 'fixed', 'discount_value' => 100000, 'is_active' => true]);

        $result = $service->calculate($items, $coupon, 1000);

        $this->assertSame(1000, $result['coins_redeemed']);
        $this->assertGreaterThanOrEqual(0.0, $result['grand_total']);
        $this->assertEqualsWithDelta($result['subtotal'] + $result['tax_total'], $result['discount_total'] + $result['coin_discount'], 1.0);
    }

    public function test_coupon_alone_and_coins_alone_are_unchanged(): void
    {
        $service = app(PricingService::class);
        $items = $this->items();

        $coinsOnly = $service->calculate($items, null, 1000);
        $couponOnly = $service->calculate($items, $this->percentCoupon(), 0);

        $this->assertSame(0.0, (float) $coinsOnly['discount_total']);
        $this->assertSame(0.0, (float) $couponOnly['coin_discount']);
        $this->assertGreaterThan(0.0, $couponOnly['discount_total']);
    }

    public function test_coins_earned_are_not_affected_by_the_order_of_discounts(): void
    {
        $service = app(PricingService::class);
        $items = $this->items();

        $plain = $service->calculate($items);
        $both = $service->calculate($items, $this->percentCoupon(), 1000);

        $this->assertSame($plain['total_coins_earned'], $both['total_coins_earned']);
    }
}
