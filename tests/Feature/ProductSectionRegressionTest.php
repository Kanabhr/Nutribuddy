<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductSectionRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_storefront_product_catalog_page_renders_successfully(): void
    {
        $category = Category::factory()->create(['name' => 'Vitamins', 'is_active' => true]);
        $taxRate = TaxRate::factory()->create(['rate' => 18.0, 'is_active' => true]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'is_active' => true,
            'base_price' => 499.00,
        ]);

        $response = $this->get(route('product'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.all-products');
        $response->assertViewHas('products');
        $response->assertViewHas('categoryCounts');
    }

    public function test_product_detail_page_loads_with_variants_and_inventory(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'slug' => 'kids-multivitamin-gummies',
            'is_active' => true,
            'product_type' => 'variable',
            'is_variant_enabled' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '60 Gummies Pack',
            'sku' => 'GUMMY-60',
            'price' => 599.00,
            'is_active' => true,
            'is_default' => true,
        ]);

        Inventory::create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'stock_qty' => 50,
            'track_stock' => true,
            'is_in_stock' => true,
        ]);

        $response = $this->get(route('product.show', 'kids-multivitamin-gummies'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.product');
        $response->assertViewHas('product');
    }

    public function test_authenticated_user_can_add_product_to_cart(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $user = User::factory()->create();
        $product = Product::factory()->create([
            'is_active' => true,
            'base_price' => 350.00,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'track_stock' => false,
            'stock_qty' => 10,
        ]);

        $response = $this->actingAs($user)->postJson(route('user.cart.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertSuccessful();
    }

    public function test_admin_with_permission_can_access_products_management_index(): void
    {
        Permission::create(['name' => 'view products', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Catalog Manager', 'guard_name' => 'admin']);
        $role->givePermissionTo('view products');

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.ecommerce.products.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.ecommerce.products.index');
        $response->assertViewHas(['products', 'categories', 'taxRates']);
    }

    public function test_admin_product_resource_pages_use_the_listing_page_without_route_collision(): void
    {
        Permission::create(['name' => 'manage products', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Product Manager', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage products');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);
        $product = Product::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.products.create'))
            ->assertOk()
            ->assertViewIs('admin.ecommerce.products.create');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.products.show', $product))
            ->assertRedirect(route('admin.ecommerce.products.index'));
    }

    public function test_admin_review_status_updates_keep_json_and_redirect_contracts(): void
    {
        Permission::create(['name' => 'manage moderation', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Review Moderator', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage moderation');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $review = ProductReview::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'Great product',
            'is_active' => true,
        ]);
        $videoReview = ProductReview::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'Video review',
            'video_path' => 'reviews/sample.mp4',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.ecommerce.reviews.update', $review), ['is_active' => false])
            ->assertOk()
            ->assertExactJson(['success' => true, 'is_active' => false]);

        $this->patchJson(route('admin.ecommerce.video-reviews.update', $videoReview), ['is_active' => false])
            ->assertOk()
            ->assertExactJson(['success' => true, 'is_active' => false]);

        $this->from(route('admin.ecommerce.reviews.index'))
            ->patch(route('admin.ecommerce.reviews.update', $review), ['is_active' => true])
            ->assertRedirect(route('admin.ecommerce.reviews.index'))
            ->assertSessionHas('success', 'Review status updated successfully.');

        $this->assertDatabaseHas('product_reviews', ['id' => $review->id, 'is_active' => true]);
        $this->assertDatabaseHas('product_reviews', ['id' => $videoReview->id, 'is_active' => false]);
    }

    public function test_admin_product_problem_solution_hub_renders_correctly(): void
    {
        Permission::create(['name' => 'view products', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Admin User', 'guard_name' => 'admin']);
        $role->givePermissionTo('view products');

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.ecommerce.products.problem-solution.index', ['product_id' => $product->id]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.ecommerce.products.problem-solution');
        $response->assertViewHas('product');
    }

    public function test_admin_can_create_a_variant_and_inventory_record(): void
    {
        Permission::create(['name' => 'manage products', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Variant Manager', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage products');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);
        $product = Product::factory()->create(['product_type' => 'simple', 'is_variant_enabled' => false]);

        $this->actingAs($admin, 'admin')->post(route('admin.ecommerce.variants.store'), [
            'product_id' => $product->id,
            'name' => 'Small pack',
            'sku' => 'small-pack',
            'price' => 19.99,
            'stock_qty' => 7,
            'track_stock' => true,
            'is_in_stock' => true,
        ])->assertRedirect();

        $variant = ProductVariant::where('product_id', $product->id)->firstOrFail();
        $this->assertDatabaseHas('inventories', [
            'product_variant_id' => $variant->id,
            'stock_qty' => 7,
            'track_stock' => 1,
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'product_type' => 'variable']);
    }

    public function test_admin_can_create_a_product_with_inventory(): void
    {
        Permission::create(['name' => 'manage products', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Product Creator', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage products');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        $this->actingAs($admin, 'admin')->post(route('admin.ecommerce.products.store'), [
            'name' => 'Daily Vitamin',
            'base_price' => 249,
            'product_type' => 'simple',
            'stock_qty' => 8,
        ])->assertRedirect(route('admin.ecommerce.products.index'));

        $product = Product::where('name', 'Daily Vitamin')->firstOrFail();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'slug' => 'daily-vitamin']);
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'stock_qty' => 8]);
    }

    public function test_admin_can_update_a_product_without_changing_its_route_contract(): void
    {
        Permission::create(['name' => 'manage products', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Product Editor', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage products');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);
        $product = Product::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

        $this->actingAs($admin, 'admin')->put(route('admin.ecommerce.products.update', $product), [
            'name' => 'Updated Name',
            'base_price' => 399,
            'product_type' => 'simple',
            'stock_qty' => 3,
        ])->assertRedirect(route('admin.ecommerce.products.index'));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Name', 'slug' => 'updated-name']);
    }
}
