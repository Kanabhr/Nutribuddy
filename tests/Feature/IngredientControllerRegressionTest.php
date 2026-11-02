<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IngredientControllerRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_admin_can_create_ingredient_and_sync_its_benefits_and_products(): void
    {
        $admin = $this->productManager();
        $products = Product::factory()->count(2)->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.ingredients.store'), [
                'main_heading' => 'Vitamin C',
                'short_heading' => 'Immune support',
                'benefits' => ['  Supports immunity  ', '', 'Antioxidant support'],
                'products' => $products->modelKeys(),
            ])
            ->assertRedirect(route('admin.ecommerce.ingredients.index'))
            ->assertSessionHas('success', 'Ingredient created successfully.');

        $ingredient = Ingredient::with('benefits')->firstOrFail();
        $this->assertTrue($ingredient->is_active);
        $this->assertSame(['Supports immunity', 'Antioxidant support'], $ingredient->benefits->pluck('heading')->all());
        $this->assertEqualsCanonicalizing($products->modelKeys(), $ingredient->products()->pluck('products.id')->all());
    }

    public function test_admin_update_replaces_benefits_and_product_links_and_preserves_omitted_active_default(): void
    {
        $admin = $this->productManager();
        $ingredient = Ingredient::create([
            'main_heading' => 'Original ingredient',
            'short_heading' => 'Original summary',
            'is_active' => true,
        ]);
        $oldProduct = Product::factory()->create();
        $newProduct = Product::factory()->create();
        $ingredient->benefits()->create(['heading' => 'Old benefit', 'sort_order' => 0]);
        $ingredient->products()->attach($oldProduct);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.ecommerce.ingredients.update', $ingredient), [
                'main_heading' => 'Updated ingredient',
                'short_heading' => 'Updated summary',
                'benefits' => ['New benefit'],
                'products' => [$newProduct->id],
            ])
            ->assertRedirect(route('admin.ecommerce.ingredients.index'))
            ->assertSessionHas('success', 'Ingredient updated successfully.');

        $ingredient->refresh();
        $this->assertFalse($ingredient->is_active);
        $this->assertSame(['New benefit'], $ingredient->benefits()->pluck('heading')->all());
        $this->assertSame([$newProduct->id], $ingredient->products()->pluck('products.id')->all());
    }

    private function productManager(): User
    {
        Permission::create(['name' => 'manage products', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Ingredient Manager', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage products');

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        return $admin;
    }
}
