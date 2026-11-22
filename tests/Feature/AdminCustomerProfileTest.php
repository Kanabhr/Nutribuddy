<?php

namespace Tests\Feature;

use App\Models\CoinTransaction;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminCustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_admin_can_view_the_full_customer_profile(): void
    {
        $this->withoutVite();
        $admin = $this->adminWith(['view customers', 'view orders']);
        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Anita Verma',
            'email' => 'anita@example.com',
            'phone' => '9999900000',
            'coins_balance' => 120,
        ]);
        $delivered = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'grand_total' => 500,
        ]);
        Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'cancelled',
            'payment_status' => 'paid',
            'grand_total' => 300,
        ]);
        Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'grand_total' => 200,
        ]);
        CoinTransaction::create([
            'user_id' => $customer->id,
            'order_id' => $delivered->id,
            'type' => 'earned',
            'amount' => 40,
            'description' => 'Order reward',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.customers.show', $customer))
            ->assertOk()
            ->assertSee('Anita Verma')
            ->assertSee('anita@example.com')
            ->assertSee('#'.$delivered->order_number)
            ->assertSee('Order reward')
            ->assertSee('120')
            // Spend counts only the paid, non-cancelled order.
            ->assertSee('INR 500.00')
            ->assertDontSee('INR 800.00')
            ->assertDontSee('INR 1,000.00');
    }

    public function test_customer_without_orders_shows_empty_states(): void
    {
        $this->withoutVite();
        $admin = $this->adminWith(['view customers']);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.customers.show', $customer))
            ->assertOk()
            ->assertSee('No orders yet.')
            ->assertSee('No coin activity yet.')
            ->assertSee('No subscriptions.')
            ->assertSee('No reviews yet.');
    }

    public function test_admin_without_customer_permission_cannot_view_a_profile(): void
    {
        $admin = $this->adminWith(['view orders']);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.customers.show', $customer))
            ->assertForbidden();
    }

    public function test_profile_page_is_not_available_for_non_customer_accounts(): void
    {
        $admin = $this->adminWith(['view customers']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.customers.show', $otherAdmin))
            ->assertNotFound();
    }

    /** @param list<string> $permissions */
    private function adminWith(array $permissions): User
    {
        foreach (['view customers', 'manage customers', 'view orders', 'manage orders'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $role = Role::create(['name' => 'Role '.uniqid(), 'guard_name' => 'admin']);
        $role->givePermissionTo($permissions);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        return $admin;
    }
}
