<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('india_states_cities_data_v2');
    }

    public function test_checkout_renders_states_without_embedding_the_city_catalog(): void
    {
        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('Andaman and Nicobar Islands')
            ->assertSee('stateField')
            ->assertDontSee('citiesByState')
            ->assertDontSee('allCities');
    }

    public function test_checkout_city_endpoint_returns_cities_for_a_valid_state(): void
    {
        $this->getJson(route('checkout.cities', ['stateCode' => '01']))
            ->assertOk()
            ->assertJsonPath('cities.0', 'Aberdeen');

        $this->getJson(route('checkout.cities', ['stateCode' => 'unknown']))
            ->assertNotFound()
            ->assertExactJson(['cities' => []]);
    }

    public function test_user_can_place_cod_order_and_token_prevents_duplicate_orders(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $user = User::factory()->create();
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
            'stock_qty' => 10,
            'reserved_qty' => 0,
        ]);

        CustomerAddress::create([
            'user_id' => $user->id,
            'label' => 'Home',
            'full_name' => $user->name,
            'phone' => '9999999999',
            'email' => $user->email,
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

        Coupon::create([
            'code' => 'SAVE20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
            'used_count' => 0,
        ]);

        $this->actingAs($user);

        $this->postJson(route('user.cart.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertOk();

        $summary = $this->postJson(route('user.checkout.summary'), [
            'coupon_code' => 'SAVE20',
        ])->assertOk()->json();

        $checkoutToken = $summary['checkout_token'];
        $addressId = $user->customerAddresses()->first()->id;

        $placePayload = [
            'address_id' => $addressId,
            'coupon_code' => 'SAVE20',
            'payment_method' => 'cod',
            'checkout_token' => $checkoutToken,
        ];

        $this->postJson(route('user.checkout.place-order'), $placePayload)
            ->assertOk()
            ->assertJsonPath('order.payment_method', 'cod');

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(9, (int) $product->inventory()->first()->stock_qty);
        $this->assertSame(0, (int) $user->fresh()->coins_balance);
        $this->assertDatabaseMissing('coin_transactions', [
            'order_id' => Order::query()->value('id'),
            'type' => 'earned',
        ]);

        $this->postJson(route('user.checkout.place-order'), $placePayload)
            ->assertOk()
            ->assertJsonPath('message', 'Order already placed for this checkout request.');

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(9, (int) $product->inventory()->first()->stock_qty);
    }

    public function test_checkout_fails_when_stock_is_insufficient(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $user = User::factory()->create();
        $category = Category::factory()->create();
        $taxRate = TaxRate::factory()->create(['rate' => 18, 'code' => 'GST18B']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'is_active' => true,
        ]);
        Inventory::factory()->create([
            'product_id' => $product->id,
            'stock_qty' => 1,
            'reserved_qty' => 0,
            'track_stock' => true,
        ]);
        CustomerAddress::create([
            'user_id' => $user->id,
            'label' => 'Home',
            'full_name' => $user->name,
            'phone' => '9999999998',
            'email' => $user->email,
            'address_line_1' => 'Street 1',
            'city' => 'Noida',
            'state' => 'UP',
            'postal_code' => '201301',
            'country' => 'India',
        ]);

        $this->actingAs($user);
        $this->postJson(route('user.cart.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertStatus(422);
    }
}
