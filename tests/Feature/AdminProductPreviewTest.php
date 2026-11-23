<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminProductPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_admin_can_preview_an_inactive_product_that_the_shop_hides(): void
    {
        $this->withoutVite();
        $product = Product::factory()->create(['is_active' => false, 'name' => 'Draft Gummies']);

        $this->get(route('product.show', $product->slug))->assertNotFound();

        $this->actingAs($this->adminWith(['view products']), 'admin')
            ->get(route('admin.ecommerce.products.preview', $product))
            ->assertOk()
            ->assertSee('PREVIEW (admin only)', false)
            ->assertSee('Draft Gummies')
            ->assertSee('Inactive: customers cannot see this page yet', false)
            ->assertSee('name="robots" content="noindex, nofollow"', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_active_product_preview_is_marked_live_and_never_cached(): void
    {
        $this->withoutVite();
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->adminWith(['view products']), 'admin')
            ->get(route('admin.ecommerce.products.preview', $product))
            ->assertOk()
            ->assertSee('Live', false);

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_admin_without_product_permission_cannot_preview(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->adminWith(['view orders']), 'admin')
            ->get(route('admin.ecommerce.products.preview', $product))
            ->assertForbidden();
    }

    public function test_trashed_product_cannot_be_previewed(): void
    {
        $product = Product::factory()->create();
        $product->delete();

        $this->actingAs($this->adminWith(['view products']), 'admin')
            ->get(route('admin.ecommerce.products.preview', $product->id))
            ->assertNotFound();
    }

    /** @param list<string> $permissions */
    private function adminWith(array $permissions): User
    {
        foreach (['view products', 'manage products', 'view orders'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $role = Role::create(['name' => 'Role '.uniqid(), 'guard_name' => 'admin']);
        $role->givePermissionTo($permissions);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        return $admin;
    }
}