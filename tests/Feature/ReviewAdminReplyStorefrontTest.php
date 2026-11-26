<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Needs the PR #19 migration (admin_reply) and the include lines from PASTE-GUIDE.md. */
class ReviewAdminReplyStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_testimonials_page_shows_the_brand_reply_for_a_published_review(): void
    {
        $this->withoutVite();
        $review = $this->review(['admin_reply' => 'Thank you for trusting NutriBuddy!']);

        $this->get(route('testimonials'))
            ->assertOk()
            ->assertSee('Reply from NutriBuddy')
            ->assertSee('Thank you for trusting NutriBuddy!');
    }

    public function test_reply_text_is_escaped(): void
    {
        $this->withoutVite();
        $this->review(['admin_reply' => '<script>alert(1)</script>']);

        $this->get(route('testimonials'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_hidden_reviews_and_reviews_without_a_reply_show_no_reply_block(): void
    {
        $this->withoutVite();
        $this->review(['admin_reply' => 'Secret reply on a hidden review', 'is_active' => false]);
        $this->review(['admin_reply' => null, 'comment' => 'No reply here']);

        $this->get(route('testimonials'))
            ->assertOk()
            ->assertDontSee('Secret reply on a hidden review')
            ->assertDontSee('Reply from NutriBuddy');
    }

    /** @param array<string, mixed> $attributes */
    private function review(array $attributes = []): ProductReview
    {
        return ProductReview::create(array_merge([
            'product_id' => Product::factory()->create()->id,
            'user_id' => User::factory()->create(['name' => 'Asha Parent'])->id,
            'rating' => 5,
            'comment' => 'My child loves it',
            'is_active' => true,
        ], $attributes));
    }
}