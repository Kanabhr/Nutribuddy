<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminReviewReplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_moderator_can_reply_to_a_review(): void
    {
        $admin = $this->adminWith(['view moderation', 'manage moderation']);
        $review = $this->createReview();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.reviews.reply', $review), ['admin_reply' => 'Thank you for your feedback!'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Reply saved successfully.');

        $review->refresh();
        $this->assertSame('Thank you for your feedback!', $review->admin_reply);
        $this->assertNotNull($review->admin_replied_at);
        $this->assertSame($admin->id, $review->admin_replied_by);
    }

    public function test_empty_reply_removes_the_existing_reply(): void
    {
        $admin = $this->adminWith(['view moderation', 'manage moderation']);
        $review = $this->createReview([
            'admin_reply' => 'Old reply',
            'admin_replied_at' => now(),
            'admin_replied_by' => $admin->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.reviews.reply', $review), ['admin_reply' => ''])
            ->assertRedirect();

        $review->refresh();
        $this->assertNull($review->admin_reply);
        $this->assertNull($review->admin_replied_at);
        $this->assertNull($review->admin_replied_by);
    }

    public function test_reply_longer_than_the_limit_is_rejected(): void
    {
        $admin = $this->adminWith(['view moderation', 'manage moderation']);
        $review = $this->createReview();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.reviews.reply', $review), ['admin_reply' => str_repeat('a', 2001)])
            ->assertSessionHasErrors('admin_reply');

        $this->assertNull($review->fresh()->admin_reply);
    }

    public function test_view_only_admin_cannot_reply(): void
    {
        $admin = $this->adminWith(['view moderation']);
        $review = $this->createReview();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.reviews.reply', $review), ['admin_reply' => 'Nope'])
            ->assertForbidden();

        $this->assertNull($review->fresh()->admin_reply);
    }

    public function test_reviews_page_shows_the_reply_and_customer_popup_data(): void
    {
        $this->withoutVite();
        $admin = $this->adminWith(['view moderation', 'manage moderation']);
        $review = $this->createReview(['admin_reply' => 'We appreciate this review']);
        Order::factory()->create(['user_id' => $review->user_id, 'status' => 'delivered']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.reviews.index'))
            ->assertOk()
            ->assertSee('We appreciate this review')
            ->assertSee('Priya Sharma')
            ->assertSee('data-orders="1"', false)
            ->assertSee('Edit reply');
    }

    /** @param list<string> $permissions */
    private function adminWith(array $permissions): User
    {
        foreach (['view moderation', 'manage moderation'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $role = Role::create(['name' => 'Role '.uniqid(), 'guard_name' => 'admin']);
        $role->givePermissionTo($permissions);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        return $admin;
    }

    /** @param array<string, mixed> $attributes */
    private function createReview(array $attributes = []): ProductReview
    {
        $customer = User::factory()->create(['name' => 'Priya Sharma']);

        return ProductReview::create(array_merge([
            'product_id' => Product::factory()->create()->id,
            'user_id' => $customer->id,
            'rating' => 5,
            'comment' => 'Great product',
            'is_active' => true,
        ], $attributes));
    }
}
