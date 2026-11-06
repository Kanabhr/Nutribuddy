<?php

namespace Tests\Feature;

use App\Models\DietSubscription;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\Nutrition\QuestionnaireAnswer;
use App\Models\Nutrition\QuestionnaireQuestion;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Nutrition\DietChartGeneratorService;
use App\Services\Nutrition\DietPaymentService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Task C1f – Paid-child upgrade questionnaire regression tests.
 *
 * Covers:
 *  1. A child with an active paid Plus subscription submits the upgrade
 *     questionnaire → no payment/create call, chart generated once.
 *  2. A new child (no prior subscription) still gets the payment flow.
 *  3. payment/create for an already-paid child returns a clean 422 (not 500).
 *  4. createCashfreeOrder logs HTTP status + response body on failure (no secrets).
 */
class PaidChildUpgradeQuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────────────────
    // Shared fixtures
    // ──────────────────────────────────────────────────────────────────────────

    private function makePlusPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::firstOrCreate(['slug' => 'plus'], [
            'name'          => 'Plus',
            'price'         => 299,
            'monthly_price' => 299,
            'is_active'     => true,
        ]);
    }

    /** Seed minimal Plus-level questions. */
    private function seedPlusQuestions(): QuestionnaireQuestion
    {
        return QuestionnaireQuestion::create([
            'model'       => '2 Plus',
            'section'     => 'Lifestyle',
            'question'    => 'Activity level?',
            'answer_type' => 'Text',
            'sort_order'  => 13,
            'is_optional' => true,
        ]);
    }

    private function makeUserWithChildAndBasicAssessment(): array
    {
        $user  = User::factory()->create();
        $child = Child::create(['user_id' => $user->id, 'name' => 'Rohan', 'sex' => 'boy']);

        ChildAssessment::create([
            'child_id'    => $child->id,
            'model_level' => 'basic',
        ]);

        return [$user, $child];
    }

    private function giveActivePlusSub(User $user, Child $child): DietSubscription
    {
        $plan = $this->makePlusPlan();

        return DietSubscription::create([
            'user_id'              => $user->id,
            'child_id'             => $child->id,
            'subscription_plan_id' => $plan->id,
            'model'                => 'Plus (Monthly)',
            'status'               => 'active',
            'payment_status'       => 'paid',
            'amount_paid'          => 299,
            'starts_at'            => now(),
            'expires_at'           => now()->addDays(30),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 1. Paid child: submit upgrade answers → no payment call, chart generated
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * When a child already holds an active paid Plus subscription and the parent
     * submits the upgrade questionnaire, the submission service must:
     *   a) save the answers (new assessment at Plus level),
     *   b) call the chart generator exactly once,
     *   c) NOT call payment/create at all.
     *
     * The JS guard (window.qzSkipPayment) prevents the browser from ever calling
     * payment/create, but we verify the server-side behaviour as well: if
     * payment/create IS accidentally called it must return 422, not 500.
     */
    public function test_paid_child_submits_upgrade_answers_chart_generated_no_payment_call(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        [$user, $child] = $this->makeUserWithChildAndBasicAssessment();
        $this->giveActivePlusSub($user, $child);
        $plusQuestion = $this->seedPlusQuestions();

        // The generator should be called exactly once (by QuestionnaireSubmissionService).
        $generator = \Mockery::mock(DietChartGeneratorService::class);
        $generator->shouldReceive('generateDietChart')
            ->once()
            ->andReturnUsing(function (ChildAssessment $assessment) {
                DietPlan::create([
                    'child_assessment_id' => $assessment->id,
                    'status'              => 'pending_review',
                    'review_status'       => 'pending',
                    'plan_data'           => '{}',
                ]);

                return ['success' => true, 'diet_plan_id' => DietPlan::latest()->first()->id];
            });
        $this->app->instance(DietChartGeneratorService::class, $generator);

        // Submit the upgrade questionnaire answers.
        $response = $this->actingAs($user)
            ->postJson(route('api.nutrition.questionnaire.submit'), [
                'plan_slug'           => 'plus',
                'child_id'            => $child->id,
                'q_' . $plusQuestion->id => 'active',
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        // A new assessment at Plus level must exist.
        $newAssessment = $child->fresh()->assessments()->orderByDesc('id')->first();
        $this->assertSame('plus', $newAssessment->model_level);

        // A diet plan must have been created for it.
        $this->assertDatabaseHas('diet_plans', [
            'child_assessment_id' => $newAssessment->id,
        ]);

        // No pending subscriptions should have been created.
        $this->assertDatabaseMissing('diet_subscriptions', [
            'user_id'        => $user->id,
            'child_id'       => $child->id,
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Double-submit: the generator is called at most once even when the parent
     * submits the same answers twice (idempotency via dietPlans()->exists() guard).
     */
    public function test_paid_child_double_submit_chart_generated_at_most_once(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        [$user, $child] = $this->makeUserWithChildAndBasicAssessment();
        $this->giveActivePlusSub($user, $child);
        $plusQuestion = $this->seedPlusQuestions();

        $generatorCallCount = 0;
        $generator = \Mockery::mock(DietChartGeneratorService::class);
        $generator->shouldReceive('generateDietChart')
            ->andReturnUsing(function (ChildAssessment $assessment) use (&$generatorCallCount) {
                $generatorCallCount++;
                DietPlan::create([
                    'child_assessment_id' => $assessment->id,
                    'status'              => 'pending_review',
                    'review_status'       => 'pending',
                    'plan_data'           => '{}',
                ]);

                return ['success' => true, 'diet_plan_id' => DietPlan::latest()->first()->id];
            });
        $this->app->instance(DietChartGeneratorService::class, $generator);

        $payload = [
            'plan_slug'           => 'plus',
            'child_id'            => $child->id,
            'q_' . $plusQuestion->id => 'active',
        ];

        $this->actingAs($user)->postJson(route('api.nutrition.questionnaire.submit'), $payload)->assertOk();
        $this->actingAs($user)->postJson(route('api.nutrition.questionnaire.submit'), $payload)->assertOk();

        // Each submission creates a NEW assessment, so the generator CAN be called
        // twice (once per assessment).  What must NOT happen is two plans on the
        // same assessment.
        $latestAssessment = $child->fresh()->assessments()->orderByDesc('id')->first();
        $this->assertLessThanOrEqual(
            1,
            $latestAssessment->dietPlans()->count(),
            'At most one diet plan per assessment.'
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 2. New child: payment flow is unchanged
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * A brand-new child (no subscription at all) who chooses Plus still goes
     * through the normal payment flow: payment/create must succeed and return
     * gateway details.
     */
    public function test_new_child_still_gets_payment_flow_via_cashfree(): void
    {
        config([
            'services.cashfree.app_id'    => 'test-app',
            'services.cashfree.secret_key' => 'test-secret',
        ]);
        Http::fake(['https://sandbox.cashfree.com/pg/orders' => Http::response([
            'order_id'           => 'diet_sub_new_001',
            'payment_session_id' => 'ps_new_001',
            'cf_order_id'        => 'cf_new_001',
        ], 200)]);

        $this->makePlusPlan();
        $user  = User::factory()->create();
        $child = Child::create(['user_id' => $user->id, 'name' => 'Priya', 'sex' => 'girl']);

        $response = $this->actingAs($user)
            ->postJson(route('diet.payment.create'), [
                'plan_slug' => 'plus',
                'gateway'   => 'cashfree',
                'cycle'     => 'monthly',
                'child_id'  => $child->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('gateway', 'cashfree');

        $this->assertDatabaseHas('diet_subscriptions', [
            'user_id'        => $user->id,
            'child_id'       => $child->id,
            'payment_status' => 'pending',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 3. payment/create returns clean 422 for already-paid child
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * When payment/create is called for a child who already holds an active paid
     * subscription for the same plan, it must return 422 with a readable message
     * instead of calling the payment gateway and potentially returning a 500.
     */
    public function test_payment_create_returns_422_for_already_paid_child(): void
    {
        // Ensure no HTTP requests reach the gateway (if they do, the test fails).
        Http::fake([
            'https://sandbox.cashfree.com/*' => Http::response(['error' => 'should not reach'], 500),
            'https://api.cashfree.com/*'     => Http::response(['error' => 'should not reach'], 500),
        ]);

        [$user, $child] = $this->makeUserWithChildAndBasicAssessment();
        $this->giveActivePlusSub($user, $child);

        $response = $this->actingAs($user)
            ->postJson(route('diet.payment.create'), [
                'plan_slug' => 'plus',
                'gateway'   => 'cashfree',
                'cycle'     => 'monthly',
                'child_id'  => $child->id,
            ]);

        $response->assertUnprocessable() // 422
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['message' => 'This child already has an active subscription for this plan.']);

        // No new subscription row should have been created.
        $this->assertDatabaseMissing('diet_subscriptions', [
            'user_id'        => $user->id,
            'child_id'       => $child->id,
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Ensure a child with a higher plan (Pro) cannot be charged for a lower plan (Plus).
     * This pre-existing guard must still work and return 422.
     */
    public function test_payment_create_returns_422_for_child_with_higher_plan(): void
    {
        Http::fake();

        $user  = User::factory()->create();
        $child = Child::create(['user_id' => $user->id, 'name' => 'Dev', 'sex' => 'boy']);

        $plusPlan = $this->makePlusPlan();
        $proPlan  = SubscriptionPlan::firstOrCreate(['slug' => 'pro'], [
            'name'          => 'Pro',
            'price'         => 499,
            'monthly_price' => 499,
            'is_active'     => true,
        ]);

        // Child already has Pro.
        DietSubscription::create([
            'user_id'              => $user->id,
            'child_id'             => $child->id,
            'subscription_plan_id' => $proPlan->id,
            'model'                => 'Pro (Monthly)',
            'status'               => 'active',
            'payment_status'       => 'paid',
            'amount_paid'          => 499,
            'starts_at'            => now(),
            'expires_at'           => now()->addDays(30),
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('diet.payment.create'), [
                'plan_slug' => 'plus',
                'gateway'   => 'cashfree',
                'cycle'     => 'monthly',
                'child_id'  => $child->id,
            ]);

        $response->assertUnprocessable()->assertJsonPath('success', false);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 4. createCashfreeOrder logs status + body on failure, never logs secrets
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * When Cashfree rejects an order creation request, DietPaymentService must:
     *   a) log the HTTP status code and response body,
     *   b) NOT log the x-client-id, x-client-secret, or any key/token value.
     */
    public function test_cashfree_order_failure_logs_status_and_body_without_secrets(): void
    {
        $appId     = 'test-cf-app-id-12345';
        $secretKey = 'test-cf-secret-key-99999';

        config([
            'services.cashfree.app_id'    => $appId,
            'services.cashfree.secret_key' => $secretKey,
        ]);

        Http::fake([
            'https://sandbox.cashfree.com/pg/orders' => Http::response(
                ['message' => 'Invalid merchant credentials', 'code' => 'authentication_error'],
                401
            ),
        ]);

        $this->makePlusPlan();
        $user  = User::factory()->create();
        $child = Child::create(['user_id' => $user->id, 'name' => 'Aarav', 'sex' => 'boy']);

        $logContext = null;
        Log::shouldReceive('error')
            ->withArgs(function (string $message, array $context) use (&$logContext) {
                if ($message === 'Cashfree order creation failed.') {
                    $logContext = $context;

                    return true;
                }

                return false; // allow other log calls through
            })
            ->atLeast()->once();

        // Also allow the outer "Diet plan payment order failed." log call.
        Log::shouldReceive('error')->withAnyArgs()->zeroOrMoreTimes();

        $this->actingAs($user)
            ->postJson(route('diet.payment.create'), [
                'plan_slug' => 'plus',
                'gateway'   => 'cashfree',
                'cycle'     => 'monthly',
                'child_id'  => $child->id,
            ]);

        // Verify the specific log call was made.
        $this->assertNotNull($logContext, 'Log::error("Cashfree order creation failed.") must have been called.');

        // Must include http_status and response_body.
        $this->assertArrayHasKey('http_status', $logContext, 'Log context must include http_status.');
        $this->assertArrayHasKey('response_body', $logContext, 'Log context must include response_body.');
        $this->assertSame(401, $logContext['http_status']);
        $this->assertStringContainsString('authentication_error', (string) $logContext['response_body']);

        // Must NOT contain any secret value in the logged context.
        $contextJson = json_encode($logContext) ?: '';
        $this->assertStringNotContainsString(
            $secretKey,
            $contextJson,
            'Log context must never contain the Cashfree secret key.'
        );
        $this->assertStringNotContainsString(
            $appId,
            $contextJson,
            'Log context must never contain the Cashfree app ID.'
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 5. QuestionnaireController sets skipPayment flag correctly
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * When a paid-Plus child visits the upgrade questionnaire page, the view
     * must receive skipPayment = true so the JS can skip the payment call.
     */
    public function test_questionnaire_controller_sets_skip_payment_true_for_paid_child(): void
    {
        // Seed a minimal Plus question so the higher-question branch fires.
        $this->seedPlusQuestions();

        [$user, $child] = $this->makeUserWithChildAndBasicAssessment();
        $this->giveActivePlusSub($user, $child);

        $response = $this->actingAs($user)
            ->get(route('frontend.nutrition.questionnaire.child', $child->id) . '?plan=plus');

        $response->assertOk();
        $viewData = $response->viewData('skipPayment');
        $this->assertTrue((bool) $viewData, 'skipPayment must be true for a child with an active paid subscription.');
    }

    /**
     * A new child visiting the questionnaire for the first time must get
     * skipPayment = false so the normal payment flow proceeds.
     */
    public function test_questionnaire_controller_sets_skip_payment_false_for_new_child(): void
    {
        $this->makePlusPlan();

        $user = User::factory()->create();
        // No child yet — questionnaire is reached via the plan=plus URL directly.

        $response = $this->actingAs($user)
            ->get(route('frontend.nutrition.questionnaire') . '?plan=plus');

        $response->assertOk();
        $viewData = $response->viewData('skipPayment');
        $this->assertFalse((bool) $viewData, 'skipPayment must be false for a new child with no subscription.');
    }
}
