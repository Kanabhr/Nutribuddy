<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminOrderCustomerLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_order_page_links_the_customer_name_for_admins_who_can_view_customers(): void
    {
        $this->withoutVite();
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['user_id' => $customer->id, 'customer_name' => 'Anita Verma']);
        $link = route('admin.ecommerce.customers.show', $customer->id);

        $this->actingAs($this->adminWith(['view orders', 'view customers']), 'admin')
            ->get(route('admin.ecommerce.orders.show', $order))
            ->assertOk()
            ->assertSee('Anita Verma')
            ->assertSee('href="'.$link.'"', false);
    }

    public function test_order_page_shows_plain_text_without_customer_permission_or_for_guest_orders(): void
    {
        $this->withoutVite();
        $customer = User::factory()->create(['role' => 'customer']);
        $linked = Order::factory()->create(['user_id' => $customer->id, 'customer_name' => 'Anita Verma']);
        $guest = Order::factory()->create(['user_id' => null, 'customer_name' => 'Guest Buyer']);
        $link = route('admin.ecommerce.customers.show', $customer->id);

        $viewer = $this->adminWith(['view orders']);
        $this->actingAs($viewer, 'admin')
            ->get(route('admin.ecommerce.orders.show', $linked))
            ->assertOk()
            ->assertSee('Anita Verma')
            ->assertDontSee('href="'.$link.'"', false);

        $this->actingAs($this->adminWith(['view orders', 'view customers']), 'admin')
            ->get(route('admin.ecommerce.orders.show', $guest))
            ->assertOk()
            ->assertSee('Guest Buyer');
    }

    /** @param list<string> $permissions */
    private function adminWith(array $permissions): User
    {
        foreach (['view orders', 'manage orders', 'view customers', 'manage customers'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $role = Role::create(['name' => 'Role '.uniqid(), 'guard_name' => 'admin']);
        $role->givePermissionTo($permissions);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        return $admin;
    }
}