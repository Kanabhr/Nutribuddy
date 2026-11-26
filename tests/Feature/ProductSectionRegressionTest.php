<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductImage;
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
            'name' => 'Brain Booster Gummies',
            'slug' => 'brain-booster-gummies',
            'is_active' => true,
            'product_type' => 'variable',
            'is_variant_enabled' => true,
            'problem_subtitle' => 'Algal DHA, Brahmi & Shankhpushpi, with essential nutrients.',
            'coins_reward' => 91,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'img/newwkadha.png',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '60 Gummies Pack',
            'sku' => 'GUMMY-60',
            'price' => 649.00,
            'compare_at_price' => 699.00,
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

        $ingredient = Ingredient::create([
            'main_heading' => 'Algal DHA',
            'short_heading' => 'Algal DHA',
            'description' => 'Plant-derived omega-3.',
            'dosage_heading_one' => 'Plant-derived omega-3',
            'dosage_heading_two' => '20 mg',
            'is_active' => true,
        ]);
        $product->ingredients()->attach($ingredient->id);

        $response = $this->get(route('product.show', 'brain-booster-gummies'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.product');
        $response->assertViewHas('product');
        $response->assertSee('/img/newwkadha.png', false);
        $response->assertSee('pdp-reference-features');
        $response->assertSee('assets/research/world-nutrition-map.html');
        $response->assertSee('assets/brain/routine-before.png');
        $response->assertSee('assets/brain/routine-after.webp');
        $response->assertSee('Reveal before and after routine images');
        $response->assertSee('pdp-formula-reveal');
        $response->assertSee('Meet your nutrition buddy');
        $response->assertSee('Little gummy.');
        $response->assertSee('Thoughtful formula.');
        $response->assertSee('Brain Booster Gummies');
        $response->assertSee('assets/brain/hero-reveal.webp');
        $response->assertSee('Ayurveda + nutraceutical');
        $response->assertSee('Brahmi & Shankhpushpi');
        $response->assertSee('20 mg');
        $response->assertSee('No preservatives');
        $response->assertSee('100% vegetarian');
        $response->assertSee('Choose your plan');
        $response->assertSee('Explore ingredients');
        $response->assertSee('Ingredient Transparency');
        $response->assertDontSee('nb-summary-bar', false);
        $response->assertSee('What’s inside');
        $response->assertSee('nb-ingredient-nav', false);
        $response->assertSee('role="tablist"', false);
        $mapDocument = file_get_contents(public_path('assets/research/world-nutrition-map.html'));
        $this->assertStringContainsString('Math.min(500,w*.4)', $mapDocument);
        $this->assertStringContainsString('max-width:1400px', $mapDocument);
        $this->assertStringContainsString('Published study location', $mapDocument);
        $this->assertStringContainsString('nb-map-legend', $mapDocument);
        $response->assertSee('Children’s supplement use around the world');
        $response->assertSee('data-gallery-count', false);
        $response->assertSee('data-gallery-prev', false);
        $response->assertSee('data-gallery-next', false);
        $response->assertSee('data-gallery-zoom', false);
        $response->assertSee('data-pdp-sticky-purchase', false);
        $response->assertSee('pdpStickyPrice', false);
        $response->assertSee('Red grape · 30 gummies · Ages 4+');
        $response->assertSee('data-pdp-sticky-action="cart"', false);
        $response->assertSee('data-pdp-sticky-action="buy"', false);
        $response->assertSee('EXPLORE THE NUTRIBUDDY FAMILY');
        $response->assertSee('One favourite.');
        $response->assertSee('Or a little more.');
        $response->assertSee('Individual products and thoughtful combos, all in one place.');
        $response->assertSee('data-pdp-family', false);
        $response->assertSee('Complete Care');
        $response->assertSee('Total Nutrition Kit');
        $response->assertSee('pdp-subscribe-story', false);
        $response->assertSee('Never run out.');
        $response->assertSee('Save from month two.');
        $response->assertSee('Pick your plan');
        $response->assertSee('See every payment');
        $response->assertSee('Stay in control');
        $response->assertSee('data-pdp-subscribe-cta', false);
        $response->assertSee('id="pdp-purchase-options"', false);
        $response->assertSee('Product description');
        $response->assertSee('What’s inside?');
        $response->assertSee('How do I use it?');
        $response->assertSee('How does Subscribe &amp; Save work?', false);
        $response->assertSee('What are the shipping and return policies?');
        $response->assertSeeInOrder([
            'Estimated delivery between',
            'Product description',
            'What’s inside?',
            'How do I use it?',
        ]);
        $response->assertSee('No Added');
        $response->assertSee('Colours');
        $response->assertSee('No Refined');
        $response->assertSee('Sugar');
        $response->assertSee('Preservatives');
        $response->assertSee('No<br>Gelatin', false);
        $response->assertSee('Vegetarian');
        $response->assertSee('pdp-subtitle');
        $response->assertSee('Algal DHA, Brahmi & Shankhpushpi, with essential nutrients.');
        $response->assertSee('1 review · design sample');
        $response->assertSee('5.0');
        $response->assertSee('₹649');
        $response->assertSee('₹699');
        $response->assertSee('pdp-price-line');
        $response->assertSee('pdp-coins-strip');
        $response->assertSee('pdpCoinsModal');
        $response->assertSee('A little thank-you from NutriBuddy');
        $response->assertSee('How do you want to buy?');
        $response->assertSee('One-time purchase');
        $response->assertSee('Subscribe & Save');
        $response->assertSee('From ₹569');
        $response->assertSee('Lower price from month 2');
        $response->assertDontSee('Plans coming soon');
        $response->assertSee('pdpSubscriptionPanel');
        $response->assertSee('Save from month 2 · Cancel anytime');
        $response->assertSee('2 months');
        $response->assertSee('3 months');
        $response->assertSee('4 months');
        $response->assertSee('Recommended');
        $response->assertSee('Best value');
        $response->assertSee('Free Tasty Kadha');
        $response->assertSee('Buy 2: save 5% · Buy 3+: save 10%');
        $response->assertSee('Available Coupons');
        $response->assertSee('Check eligible offers at checkout');
        $response->assertSee('pdpCouponModal');
        $response->assertSee('A little extra, on us');
        $response->assertSee('Extra 5% off prepaid orders');
        $response->assertSee('PREPAID5');
        $response->assertSee('Free shipping on ₹1,000+');
        $response->assertSee('Free shipping ₹1000+');
        $response->assertSee('COD Available');
        $response->assertSee('Extra 5% OFF on Prepaid orders');
        $response->assertSee('Estimated delivery between');
        $response->assertSee('17 November – 19 November.');
        $response->assertDontSee('Why Parents Love Brain Booster Gummies');
        $response->assertDontSee('7-Day Return');
        $response->assertSee('Earn');
        $response->assertSee('32 NB');
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
