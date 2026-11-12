<?php

namespace Tests\Feature;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductReviewUploadRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_review_uploads_are_saved_with_the_pending_review_and_existing_redirect_contract(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('reviews.store', $product), [
                'rating' => 5,
                'comment' => 'Helpful product',
                'review_images' => [UploadedFile::fake()->image('review.jpg')],
                'review_video' => UploadedFile::fake()->create('review.mp4', 100, 'video/mp4'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Your review has been submitted and is awaiting approval.');

        $review = ProductReview::firstOrFail();
        $this->assertSame($user->id, $review->user_id);
        $this->assertFalse($review->is_active);
        $this->assertCount(1, $review->images);
        Storage::disk('public')->assertExists($review->image_path);
        Storage::disk('public')->assertExists($review->video_path);
    }
    
    private function buyerOf(Product $product, string $status = 'delivered'): User
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => $status]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

        return $user;
    }

    public function test_product_page_review_box_depends_on_purchase_state(): void
    {
        $product = Product::factory()->create();
        $url = route('product.show', $product->slug);

        // Guest
        $this->get($url)->assertOk()->assertSee('Please log in to review this product.');

        // Logged in, no purchase
        $this->actingAs(User::factory()->create())->get($url)->assertOk()
            ->assertSee('Only customers who have received this product can review it.');

        // Order not yet delivered
        $this->actingAs($this->buyerOf($product, 'processing'))->get($url)->assertOk()
            ->assertSee('Only customers who have received this product can review it.');

        // Delivered order: form is shown
        $this->actingAs($this->buyerOf($product))->get($url)->assertOk()
            ->assertSee('Post review')
            ->assertSee(route('reviews.store', $product->id), false);
    }

    public function test_product_page_shows_already_reviewed_note(): void
    {
        $product = Product::factory()->create();
        $buyer = $this->buyerOf($product);
        ProductReview::create([
            'product_id' => $product->id, 'user_id' => $buyer->id, 'rating' => 5,
            'comment' => 'Nice', 'is_active' => false,
        ]);

        $this->actingAs($buyer)->get(route('product.show', $product->slug))->assertOk()
            ->assertSee('you have already reviewed this product')
            ->assertDontSee('Post review');
    }
}
