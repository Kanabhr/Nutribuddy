<?php

namespace Tests\Feature;

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
}
