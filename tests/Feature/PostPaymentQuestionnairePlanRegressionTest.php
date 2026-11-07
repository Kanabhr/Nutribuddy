<?php

namespace Tests\Feature;

use App\Models\DietSubscription;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\QuestionnaireQuestion;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression: after a Cashfree payment return the questionnaire page must show
 * the PLUS plan label and only the upgrade (Plus-only) questions, not all 12
 * Basic questions.
 *
 * Root cause: DietPaymentController::handleCashfreeReturn() redirected to
 * frontend.nutrition.questionnaire.child without appending ?plan=plus, so
 * showForChild() fell back to reading model_level='basic' from the latest
 * assessment and showed all Basic questions instead of the ~9 Plus ones.
 *
 * Fix: append '?plan='.urlencode($planSlug) to the redirect URL (same pattern
 * already used by verifyPayment() and NutritionPageService).
 */
class PostPaymentQuestionnairePlanRegressionTest extends TestCase
{
    use RefreshDatabase;

    /** @var User */
    private User $parent;

    /** @var Child */
    private Child $child;

    /** @var SubscriptionPlan */
    private SubscriptionPlan $basicPlan;

    /** @var SubscriptionPlan */
    private SubscriptionPlan $plusPlan;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.cashfree.app_id'    => 'test-app',
            'services.cashfree.secret_key' => 'test-secret',
            'services.cashfree.env'        => 'sandbox',
            'services.aisensy.enabled'     => false,
        ]);

        // Seed plans matching the slugs PlanAccess understands.
        $this->basicPlan = SubscriptionPlan::create([
            'name'          => 'Basic',
            'slug'          => 'basic',
            'price'         => 0,
            'monthly_price' => 0,
            'is_active'     => true,
        ]);

        $this->plusPlan = SubscriptionPlan::create([
            'name'           => 'Plus',
            'slug'           => 'plus',
            'price'          => 299,
            'monthly_price'  => 299,
            'is_active'      => true,
        ]);

        $this->parent = User::factory()->create();
        $this->child  = Child::create([
            'user_id' => $this->parent->id,
            'name'    => 'Arjun',
            'sex'     => 'boy',
        ]);
    }

    // ─── helpers ────────────────────────────────────────────────────────────

    /**
     * Seed N questions at the given model tier (e.g. '1 Basic' or '2 Plus').
     *
     * @return QuestionnaireQuestion[]
     */
    private function seedQuestions(string $modelTier, int $count): array
    {
        $questions = [];
        for ($i = 1; $i <= $count; $i++) {
            $questions[] = QuestionnaireQuestion::create([
                'model'       => $modelTier,
                'section'     => 'A',
                'question'    => "Question {$modelTier} #{$i}",
                'answer_type' => 'text',
                'sort_order'  => $i,
            ]);
        }

        return $questions;
    }

    /**
     * Create a completed Basic assessment for $child with one answer per question.
     */
    private function createBasicAssessment(array $questions): ChildAssessment
    {
        $assessment = ChildAssessment::create([
            'child_id'    => $this->child->id,
            'model_level' => 'basic',
        ]);

        foreach ($questions as $question) {
            \App\Models\Nutrition\QuestionnaireAnswer::create([
                'child_assessment_id' => $assessment->id,
                'question_id'         => $question->id,
                'answer_value'        => 'answer',
            ]);
        }

        return $assessment;
    }

    /**
     * Create an active Plus subscription with a pending Cashfree order (order_id stored
     * in razorpay_order_id per DietPaymentService convention) and fake the Cashfree
     * order API to return PAID.
     */
    private function createPlusPendingSubscription(string $cfOrderId): DietSubscription
    {
        $subscription = DietSubscription::create([
            'user_id'              => $this->parent->id,
            'child_id'             => $this->child->id,
            'subscription_plan_id' => $this->plusPlan->id,
            'model'                => 'Plus (Monthly)',
            'status'               => 'pending',
            'payment_status'       => 'pending',
            'amount_paid'          => 299,
            'razorpay_order_id'    => $cfOrderId,
            'starts_at'            => now(),
            'expires_at'           => now()->addDays(30),
        ]);

        Http::fake([
            'https://sandbox.cashfree.com/pg/orders/' . $cfOrderId => Http::response([
                'order_id'       => $cfOrderId,
                'cf_order_id'    => 'cf_' . $cfOrderId,
                'order_amount'   => 299,
                'order_currency' => 'INR',
                'order_status'   => 'PAID',
            ]),
        ]);

        return $subscription;
    }

    // ─── Test 1: Cashfree return redirect ───────────────────────────────────

    /**
     * After a Cashfree payment return, the controller must redirect to the
     * child-specific questionnaire URL with ?plan=plus so that only the Plus
     * upgrade questions are shown (not all 12 Basic questions).
     *
     * @test
     */
    public function cashfree_return_redirects_to_upgrade_questionnaire_with_plan_param(): void
    {
        // Arrange: 12 Basic questions, child has answered them.
        $basicQuestions = $this->seedQuestions('1 Basic', 12);
        $this->createBasicAssessment($basicQuestions);

        // Also seed Plus questions so there's something to show.
        $this->seedQuestions('2 Plus', 9);

        $cfOrderId = 'cf_plus_return_001';
        $subscription = $this->createPlusPendingSubscription($cfOrderId);

        // Act: simulate Cashfree redirecting the browser back.
        $response = $this->actingAs($this->parent)
            ->get(route('diet.payment.cashfree.return', [
                'order_id'        => $cfOrderId,
                'subscription_id' => $subscription->id,
            ]));

        // Assert: must redirect to the child-specific route WITH ?plan=plus.
        $expectedUrl = route('frontend.nutrition.questionnaire.child', $this->child->id)
            . '?plan=' . urlencode('plus');

        $response->assertRedirect($expectedUrl);
        $response->assertSessionHas('info');
    }

    // ─── Test 2: Questionnaire page after following the redirect ────────────

    /**
     * Following the Cashfree return redirect all the way to the questionnaire page:
     * - The page must show the plan label "Plus Plan" (not "Basic Plan").
     * - The page must render only the Plus-tier questions (~9), not the Basic ones.
     *
     * @test
     */
    public function questionnaire_page_shows_plus_label_and_only_plus_questions_after_cashfree_return(): void
    {
        // Arrange
        $basicQuestions = $this->seedQuestions('1 Basic', 12);
        $this->createBasicAssessment($basicQuestions);
        $this->seedQuestions('2 Plus', 9);

        $cfOrderId    = 'cf_plus_return_002';
        $subscription = $this->createPlusPendingSubscription($cfOrderId);

        // Step 1: follow the Cashfree return through all redirects.
        $returnUrl = route('diet.payment.cashfree.return', [
            'order_id'        => $cfOrderId,
            'subscription_id' => $subscription->id,
        ]);

        // followingRedirects() follows all chained redirects and returns the final page.
        $response = $this->actingAs($this->parent)
            ->followingRedirects()
            ->get($returnUrl);

        $response->assertOk();

        // Assert: the plan label visible on the page shows "Plus" not "Basic".
        $response->assertSee('Plus');
        $response->assertSee('Plus Plan');
        $response->assertDontSee('Basic Plan');

        // Assert: only Plus questions are visible (none of the Basic question texts).
        foreach (range(1, 9) as $i) {
            $response->assertSee("Question 2 Plus #{$i}");
        }
        foreach (range(1, 12) as $i) {
            $response->assertDontSee("Question 1 Basic #{$i}");
        }
    }

    // ─── Test 3: Banner link in NutritionPageService / meal-plan ────────────

    /**
     * The "Complete your questionnaire" banner shown on the meal-plan page
     * (generated by NutritionPageService) must link to the child-specific
     * questionnaire route with ?plan=plus.
     *
     * This path was already correct, but the test documents the contract so any
     * future regression is caught immediately.
     *
     * @test
     */
    public function meal_plan_banner_link_contains_child_specific_route_with_plan_param(): void
    {
        // Arrange: Basic questions + Basic assessment (no Plus assessment yet).
        $basicQuestions = $this->seedQuestions('1 Basic', 12);
        $this->createBasicAssessment($basicQuestions);
        $this->seedQuestions('2 Plus', 9);

        // Create an active Plus subscription so the banner is triggered.
        DietSubscription::create([
            'user_id'              => $this->parent->id,
            'child_id'             => $this->child->id,
            'subscription_plan_id' => $this->plusPlan->id,
            'model'                => 'Plus (Monthly)',
            'status'               => 'active',
            'payment_status'       => 'paid',
            'amount_paid'          => 299,
            'razorpay_order_id'    => 'cf_banner_test',
            'starts_at'            => now(),
            'expires_at'           => now()->addDays(30),
        ]);

        // Act: call NutritionPageService directly and inspect the URL it builds.
        $service  = app(\App\Services\Nutrition\NutritionPageService::class);
        $viewData = $service->buildMealPlan($this->parent->id, (string) $this->child->id);

        // Assert: banner must be pending and the URL must carry ?plan=plus.
        $this->assertNotNull($viewData, 'buildMealPlan() returned null — subscription or child not found.');
        $this->assertTrue($viewData['questionnairePending'] ?? false, 'questionnairePending should be true.');
        $this->assertNotEmpty($viewData['questionnaireUrl'] ?? '', 'questionnaireUrl must not be empty.');

        $expectedUrl = route('frontend.nutrition.questionnaire.child', $this->child->id)
            . '?plan=' . urlencode('plus');

        $this->assertSame($expectedUrl, $viewData['questionnaireUrl']);
    }

    // ─── Test 4: QuestionnaireController upgrade filter ─────────────────────

    /**
     * GET /nutrition/questionnaire/child/{id}?plan=plus must show exactly the 9
     * Plus questions and none of the 12 Basic questions when the child already
     * has a Basic assessment.
     *
     * @test
     */
    public function questionnaire_child_route_shows_only_upgrade_questions_when_plan_is_plus(): void
    {
        // Arrange
        $basicQuestions = $this->seedQuestions('1 Basic', 12);
        $this->createBasicAssessment($basicQuestions);
        $this->seedQuestions('2 Plus', 9);

        // Act
        $response = $this->actingAs($this->parent)
            ->get(route('frontend.nutrition.questionnaire.child', $this->child->id) . '?plan=plus');

        $response->assertOk();

        // Only Plus questions visible.
        foreach (range(1, 9) as $i) {
            $response->assertSee("Question 2 Plus #{$i}");
        }
        foreach (range(1, 12) as $i) {
            $response->assertDontSee("Question 1 Basic #{$i}");
        }

        // Plan label is "Plus", not "Basic".
        $response->assertSee('Plus Plan');
        $response->assertDontSee('Basic Plan');
    }

    // ─── Test 5: Baseline — without fix the wrong URL would be generated ────

    /**
     * Control test: calling the route WITHOUT ?plan= falls back to showing
     * the Basic questions (the old buggy behaviour, now confirmed by the guard
     * in showForChild()).  This test documents the regression boundary:
     * with no plan param the child's existing model_level ('basic') is used,
     * so plan=basic is injected and the full 12-question Basic set is shown.
     *
     * @test
     */
    public function questionnaire_without_plan_param_falls_back_to_current_assessment_level(): void
    {
        // Arrange: Basic assessment only.
        $basicQuestions = $this->seedQuestions('1 Basic', 12);
        $this->createBasicAssessment($basicQuestions);
        $this->seedQuestions('2 Plus', 9);

        // Act: no ?plan= param.
        $response = $this->actingAs($this->parent)
            ->get(route('frontend.nutrition.questionnaire.child', $this->child->id));

        $response->assertOk();

        // Without a plan param, showForChild() injects plan=basic from model_level.
        // The full Basic question set is shown (no upgrade filter, rank delta = 0).
        foreach (range(1, 12) as $i) {
            $response->assertSee("Question 1 Basic #{$i}");
        }
        // Plus questions must NOT appear on the Basic page.
        foreach (range(1, 9) as $i) {
            $response->assertDontSee("Question 2 Plus #{$i}");
        }

        $response->assertSee('Basic Plan');
    }
}
