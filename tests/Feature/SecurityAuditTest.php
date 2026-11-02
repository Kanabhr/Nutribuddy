<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_cart_endpoint_requires_authentication(): void
    {
        $this->getJson(route('user.cart.index'))->assertUnauthorized();
    }

    public function test_customer_cannot_access_admin_order_routes(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->getJson(route('admin.ecommerce.orders.index'))
            ->assertUnauthorized();
    }

    public function test_cart_rejects_invalid_quantities(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $user = User::factory()->create();
        $product = Product::factory()->create([
            'category_id' => Category::factory(),
            'is_active' => true,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'track_stock' => false,
            'stock_qty' => 100,
        ]);

        $this->actingAs($user);

        foreach ([0, -1, 99999] as $quantity) {
            $this->postJson(route('user.cart.store'), [
                'product_id' => $product->id,
                'quantity' => $quantity,
            ])->assertUnprocessable();
        }
    }

    public function test_user_cannot_modify_another_users_cart_item(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $product = Product::factory()->create([
            'category_id' => Category::factory(),
            'is_active' => true,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'track_stock' => false,
            'stock_qty' => 100,
        ]);

        $this->actingAs($owner);
        $this->postJson(route('user.cart.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertOk();

        $itemId = $owner->cart()->first()->items()->first()->id;

        $this->actingAs($attacker);
        $this->patchJson(route('user.cart.items.update', $itemId), [
            'quantity' => 2,
        ])->assertNotFound();

        $this->assertSame(1, (int) $owner->cart()->first()->items()->first()->quantity);
    }

    public function test_user_cannot_delete_or_set_default_another_users_address(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $address = CustomerAddress::create([
            'user_id' => $owner->id,
            'full_name' => 'Address Owner',
            'phone' => '9876543210',
            'address_line_1' => '1 Main Road',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110001',
        ]);

        $this->actingAs($attacker)
            ->patchJson(route('user.addresses.set-default', $address))
            ->assertForbidden();

        $this->deleteJson(route('user.addresses.destroy', $address))
            ->assertForbidden();

        $this->assertDatabaseHas('customer_addresses', ['id' => $address->id, 'user_id' => $owner->id]);
    }
}
