<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminCouponReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_report_shows_usage_and_total_discount_per_coupon(): void
    {
        $this->withoutVite();
        $admin = $this->adminWith(['view promotions']);
        $used = $this->coupon('SAVE10');
        $this->coupon('UNUSED20');
        $buyerOne = User::factory()->create();
        $buyerTwo = User::factory()->create();

        $this->order($used, $buyerOne, 50, 'delivered');
        $this->order($used, $buyerTwo, 100, 'confirmed');
        // Cancelled orders must not count.
        $this->order($used, $buyerOne, 999, 'cancelled');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.coupons.report'))
            ->assertOk()
            ->assertSee('SAVE10')
            ->assertSee('UNUSED20')
            ->assertSee('INR 150.00')
            ->assertDontSee('INR 999.00')
            ->assertDontSee('INR 1,149.00');
    }

    public function test_date_filter_limits_the_report_to_the_selected_period(): void
    {
        $this->withoutVite();
        $admin = $this->adminWith(['view promotions']);
        $coupon = $this->coupon('SAVE10');
        $buyer = User::factory()->create();

        $this->order($coupon, $buyer, 70, 'delivered', '2026-01-15 10:00:00');
        $this->order($coupon, $buyer, 30, 'delivered', '2026-11-10 10:00:00');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.coupons.report', ['from' => '2026-11-01', 'to' => '2026-11-30']))
            ->assertOk()
            ->assertSee('INR 30.00')
            ->assertDontSee('INR 70.00')
            ->assertDontSee('INR 100.00');
    }

    public function test_invalid_date_range_is_rejected(): void
    {
        $admin = $this->adminWith(['view promotions']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.coupons.report', ['from' => '2026-11-30', 'to' => '2026-11-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_admin_without_promotions_permission_cannot_open_the_report(): void
    {
        $admin = $this->adminWith(['view customers']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.coupons.report'))
            ->assertForbidden();
    }

    private function coupon(string $code): Coupon
    {
        return Coupon::create([
            'code' => $code,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
        ]);
    }

    private function order(Coupon $coupon, User $user, float $discount, string $status, ?string $placedAt = null): Order
    {
        return Order::factory()->create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'status' => $status,
            'discount_total' => $discount,
            'subtotal' => 500,
            'tax_total' => 0,
            'placed_at' => $placedAt ?? now(),
        ]);
    }

    /** @param list<string> $permissions */
    private function adminWith(array $permissions): User
    {
        foreach (['view promotions', 'manage promotions', 'view customers'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $role = Role::create(['name' => 'Role '.uniqid(), 'guard_name' => 'admin']);
        $role->givePermissionTo($permissions);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        return $admin;
    }
}
