<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SubscriptionPlanControllerRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::create(['name' => 'manage diet chart', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Nutrition Manager', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage diet chart');
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => 1])->assignRole($role), 'admin');
    }

    public function test_admin_can_update_a_subscription_plan_and_uncheck_active(): void
    {
        $plan = SubscriptionPlan::create(['name' => 'Old Plan', 'slug' => 'old-plan', 'price' => 0, 'is_active' => true]);

        $response = $this->patch(route('admin.ecommerce.diet-plans.update', $plan), [
            'name' => 'Updated Plan',
            'monthly_price' => '12.50',
            'quarterly_price' => '30',
            'annual_price' => '100',
        ]);

        $response->assertRedirect(route('admin.ecommerce.diet-plans.index'))
            ->assertSessionHas('success', 'Diet Plan updated successfully.');
        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'name' => 'Updated Plan',
            'monthly_price' => 12.5,
            'quarterly_price' => 30,
            'annual_price' => 100,
            'is_active' => false,
        ]);
    }

    public function test_plan_update_validates_the_name(): void
    {
        $plan = SubscriptionPlan::create(['name' => 'Old Plan', 'slug' => 'old-plan', 'price' => 0, 'is_active' => true]);

        $this->patch(route('admin.ecommerce.diet-plans.update', $plan), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id, 'name' => 'Old Plan']);
    }

    public function test_admin_can_open_and_create_a_subscription_plan(): void
    {
        $this->get(route('admin.ecommerce.diet-plans.create'))
            ->assertOk()
            ->assertViewIs('admin.diet-plans.create');

        $this->post(route('admin.ecommerce.diet-plans.store'), [
            'name' => 'Starter Plan',
            'slug' => 'starter-plan',
            'price' => 499,
            'level' => 'basic',
            'monthly_price' => 499,
            'features_text' => "Weekly diet chart\nNutritionist review",
            'is_active' => 1,
        ])->assertRedirect(route('admin.ecommerce.diet-plans.index'))
            ->assertSessionHas('success', 'Diet Plan created successfully.');

        $this->assertDatabaseHas('subscription_plans', [
            'slug' => 'starter-plan',
            'level' => 'basic',
            'is_active' => 1,
        ]);
        $this->assertSame(
            ['Weekly diet chart', 'Nutritionist review'],
            SubscriptionPlan::where('slug', 'starter-plan')->firstOrFail()->features,
        );
    }

    public function test_plan_delete_is_available_only_before_subscription_history_exists(): void
    {
        $plan = SubscriptionPlan::create(['name' => 'Unused Plan', 'slug' => 'unused-plan', 'price' => 0]);

        $this->delete(route('admin.ecommerce.diet-plans.destroy', $plan))
            ->assertRedirect(route('admin.ecommerce.diet-plans.index'))
            ->assertSessionHas('success', 'Diet Plan deleted successfully.');

        $this->assertDatabaseMissing('subscription_plans', ['id' => $plan->id]);
    }
}
