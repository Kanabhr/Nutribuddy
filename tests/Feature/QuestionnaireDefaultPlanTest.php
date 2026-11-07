<?php

namespace Tests\Feature;

use App\Models\DietSubscription;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\QuestionnaireQuestion;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C1e: GET questionnaire/child/{child} without ?plan= defaults to the child's
 * highest usable paid plan (or the latest assessment level, whichever is higher).
 */
class QuestionnaireDefaultPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;
    private Child $child;
    /** @var array<string, SubscriptionPlan> */
    private array $plans = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['basic', 'plus', 'pro', 'premium'] as $slug) {
            $this->plans[$slug] = SubscriptionPlan::create([
                'name'      => ucfirst($slug),
                'slug'      => $slug,
                'price'     => 0,
                'is_active' => true,
            ]);
        }

        $order = 1;
        foreach (['1 Basic', '2 Plus', '3 Pro', '4 Premium'] as $model) {
            for ($i = 1; $i <= 2; $i++) {
                QuestionnaireQuestion::create([
                    'model'       => $model,
                    'section'     => $model . ' section',
                    'question'    => "Question {$model} #{$i}",
                    'answer_type' => 'Text',
                    'sort_order'  => $order++,
                    'is_optional' => true,
                ]);
            }
        }

        $this->parent = User::factory()->create();
        $this->child  = Child::create(['user_id' => $this->parent->id, 'name' => 'Default Kid', 'sex' => 'boy']);
        ChildAssessment::create([
            'child_id'          => $this->child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 20,
            'current_height_cm' => 115,
            'diet_type'         => 'vegetarian',
        ]);
    }

    private function subscribe(string $slug, ?\DateTimeInterface $expires = null): void
    {
        DietSubscription::create([
            'user_id'              => $this->parent->id,
            'child_id'             => $this->child->id,
            'subscription_plan_id' => $this->plans[$slug]->id,
            'model'                => ucfirst($slug) . ' (Monthly)',
            'status'               => 'active',
            'payment_status'       => 'paid',
            'amount_paid'          => 299,
            'razorpay_order_id'    => 'cf_default_' . $slug . '_' . uniqid(),
            'starts_at'            => now()->subDay(),
            'expires_at'           => $expires ?? now()->addDays(30),
        ]);
    }

    private function questionTexts($response): array
    {
        return $response->viewData('questions')->flatten()->pluck('question')->all();
    }

    public function test_without_plan_param_the_paid_plus_plan_is_used(): void
    {
        $this->subscribe('plus');

        $response = $this->actingAs($this->parent)
            ->get(route('frontend.nutrition.questionnaire.child', $this->child->id))
            ->assertOk();

        $this->assertSame('plus', $response->viewData('plan')->slug);
        $this->assertSame(['Question 2 Plus #1', 'Question 2 Plus #2'], $this->questionTexts($response));
    }

    public function test_without_plan_param_the_highest_paid_plan_wins(): void
    {
        $this->subscribe('plus');
        $this->subscribe('premium');

        $response = $this->actingAs($this->parent)
            ->get(route('frontend.nutrition.questionnaire.child', $this->child->id))
            ->assertOk();

        $this->assertSame('premium', $response->viewData('plan')->slug);
        // Basic is already answered, so Plus + Pro + Premium questions are shown (6).
        $this->assertCount(6, $this->questionTexts($response));
    }

    public function test_expired_paid_plan_is_ignored_and_assessment_level_is_used(): void
    {
        $this->subscribe('plus', now()->subDay());

        $response = $this->actingAs($this->parent)
            ->get(route('frontend.nutrition.questionnaire.child', $this->child->id))
            ->assertOk();

        $this->assertSame('basic', $response->viewData('plan')->slug);
    }

    public function test_explicit_plan_param_is_never_overridden(): void
    {
        $this->subscribe('plus');

        $response = $this->actingAs($this->parent)
            ->get(route('frontend.nutrition.questionnaire.child', $this->child->id) . '?plan=basic')
            ->assertOk();

        $this->assertSame('basic', $response->viewData('plan')->slug);
    }

    public function test_no_subscription_keeps_the_existing_assessment_level_default(): void
    {
        $response = $this->actingAs($this->parent)
            ->get(route('frontend.nutrition.questionnaire.child', $this->child->id))
            ->assertOk();

        $this->assertSame('basic', $response->viewData('plan')->slug);
        $this->assertSame(['Question 1 Basic #1', 'Question 1 Basic #2'], $this->questionTexts($response));
    }
}