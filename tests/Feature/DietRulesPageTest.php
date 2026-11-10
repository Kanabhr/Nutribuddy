<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DietRulesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_generation_flow_safety_rules_and_active_engine_boundaries(): void
    {
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::create(['name' => 'view diet chart', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Diet Rules Viewer', 'guard_name' => 'admin']);
        $role->givePermissionTo('view diet chart');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.nutrition.diet-rules.index'))
            ->assertOk()
            ->assertViewIs('admin.nutrition.rules.index')
            ->assertSee('How a diet chart is created')
            ->assertSee('Diet and allergy safety')
            ->assertSee('How foods are ranked')
            ->assertSee('Flags and approval gate')
            ->assertSee('generator now reads questionnaire answer 18 and excludes disliked foods')
            ->assertSee('foods marked as containing dairy are excluded')
            ->assertSee('an unset or empty allergen flag is treated as unsafe for that allergy');
    }
}