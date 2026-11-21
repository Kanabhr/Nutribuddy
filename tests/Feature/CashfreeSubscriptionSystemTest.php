<?php

namespace Tests\Feature;

use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CashfreeSubscriptionSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected SubscriptionPlan $plusPlan;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.cashfree.app_id' => 'test_app_id',
            'services.cashfree.secret_key' => 'test_secret_key',
            'services.cashfree.env' => 'sandbox',
            'services.aisensy.enabled' => false,
        ]);

        $this->user = User::factory()->create([
            'email' => 'parent@example.com',
            'phone' => '9876543210',
        ]);

        $this->plusPlan = SubscriptionPlan::firstOrCreate(
            ['slug' => 'plus'],
            [
                'name' => 'Plus',
                'price' => 999.00,
                'monthly_price' => 999.00,
                'quarterly_price' => 2699.00,
                'annual_price' => 9999.00,
                'level' => 'plus',
                'cashfree_plan_id' => 'plus_monthly',
                'interval_type' => 'MONTH',
                'intervals' => 1,
                'max_cycles' => 60,
                'is_active' => true,
            ]
        );
    }

    public function test_checkout_creates_cashfree_subscription_and_returns_session(): void
    {
        Http::fake([
            'https://sandbox.cashfree.com/pg/subscriptions' => Http::response([
                'subscription_id' => 'sub_usr_' . $this->user->id . '_plus_12345',
                'cf_subscription_id' => 'cf_sub_998877',
                'subscription_status' => 'INITIALIZED',
                'subscription_session_id' => 'sub_session_abc123',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('subscription.checkout'), [
            'plan_slug' => 'plus',
            'cycle' => 'monthly',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'gateway' => 'cashfree',
                'subscription_session_id' => 'sub_session_abc123',
                'cf_subscription_id' => 'cf_sub_998877',
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $this->plusPlan->id,
            'status' => 'INITIALIZED',
            'cf_subscription_id' => 'cf_sub_998877',
        ]);
    }

    public function test_verify_activates_subscription_and_diet_chart_stays_pending_review(): void
    {
        $child = Child::create([
            'user_id' => $this->user->id,
            'name' => 'Aarav',
            'sex' => 'boy',
            'dob' => now()->subYears(5),
        ]);

        $assessment = ChildAssessment::create([
            'child_id' => $child->id,
            'model_level' => 'plus',
            'diet_type' => 'vegetarian',
            'activity_level' => 'moderate',
        ]);

        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plusPlan->id,
            'child_id' => $child->id,
            'cashfree_subscription_id' => 'sub_test_ Aarav_123',
            'status' => 'INITIALIZED',
        ]);

        $dietSub = \App\Models\DietSubscription::create([
            'user_id' => $this->user->id,
            'child_id' => $child->id,
            'subscription_plan_id' => $this->plusPlan->id,
            'model' => 'Plus (Monthly)',
            'status' => 'pending',
            'payment_status' => 'pending',
            'razorpay_order_id' => 'sub_test_ Aarav_123',
            'amount_paid' => 999.00,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        Http::fake([
            'https://sandbox.cashfree.com/pg/subscriptions/*' => Http::response([
                'subscription_id' => 'sub_test_ Aarav_123',
                'cf_subscription_id' => 'cf_sub_active_456',
                'subscription_status' => 'ACTIVE',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('subscription.verify', [
            'subscription_id' => 'sub_test_ Aarav_123',
        ]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'ACTIVE',
            ]);

        $dietSub->refresh();
        $this->assertSame('active', $dietSub->status);
        $this->assertSame('paid', $dietSub->payment_status);

        // Verify Diet Plan was generated and review_status is pending_review (NOT auto-approved!)
        $dietPlan = DietPlan::where('child_assessment_id', $assessment->id)->first();
        if ($dietPlan) {
            $this->assertSame('pending_review', $dietPlan->review_status, 'Diet plan MUST be pending_review, never auto-approved.');
        }
    }

    public function test_webhook_handles_status_change_and_payment_success_with_idempotency(): void
    {
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plusPlan->id,
            'cashfree_subscription_id' => 'sub_webhook_test_1',
            'status' => 'INITIALIZED',
        ]);

        $dietSub = \App\Models\DietSubscription::create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->plusPlan->id,
            'model' => 'Plus (Monthly)',
            'status' => 'pending',
            'payment_status' => 'pending',
            'razorpay_order_id' => 'sub_webhook_test_1',
            'amount_paid' => 999.00,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $payload = [
            'type' => 'SUBSCRIPTION_PAYMENT_SUCCESS',
            'data' => [
                'subscription_details' => [
                    'subscription_id' => 'sub_webhook_test_1',
                    'subscription_status' => 'ACTIVE',
                ],
                'payment_details' => [
                    'cf_payment_id' => 'cf_pay_998877',
                    'payment_amount' => 999.00,
                    'payment_status' => 'SUCCESS',
                ],
            ],
        ];

        $rawBody = json_encode($payload);
        $timestamp = (string) (time() * 1000);
        $signature = base64_encode(hash_hmac('sha256', $timestamp . $rawBody, 'test_secret_key', true));

        // First webhook delivery
        $response = $this->call(
            'POST',
            route('cashfree.subscription.webhook'),
            [],
            [],
            [],
            [
                'HTTP_x-webhook-signature' => $signature,
                'HTTP_x-webhook-timestamp' => $timestamp,
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawBody
        );

        $response->assertOk()->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('payments', [
            'subscription_id' => $subscription->id,
            'provider' => 'cashfree',
            'payment_type' => 'CHARGE',
            'status' => 'SUCCESS',
            'amount' => 999.00,
        ]);

        // Duplicate delivery (idempotency check)
        $duplicateResponse = $this->call(
            'POST',
            route('cashfree.subscription.webhook'),
            [],
            [],
            [],
            [
                'HTTP_x-webhook-signature' => $signature,
                'HTTP_x-webhook-timestamp' => $timestamp,
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawBody
        );

        $duplicateResponse->assertOk()->assertJson(['status' => 'already_processed']);
    }

    public function test_cancel_subscription_calls_cashfree_manage_api(): void
    {
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plusPlan->id,
            'cashfree_subscription_id' => 'sub_cancel_test_1',
            'status' => 'ACTIVE',
        ]);

        Http::fake([
            'https://sandbox.cashfree.com/pg/subscriptions/*/manage' => Http::response([
                'subscription_id' => 'sub_cancel_test_1',
                'action' => 'CANCEL',
                'status' => 'SUCCESS',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('subscription.cancel'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Subscription cancelled successfully.',
            ]);

        $subscription->refresh();
        $this->assertSame('CANCELLED', $subscription->status);
        $this->assertTrue($subscription->cancel_at_period_end);
        $this->assertNotNull($subscription->cancelled_at);
    }
}
