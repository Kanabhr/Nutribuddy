<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DietSubscription;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Nutrition\PostPaymentChartService;
use App\Services\Payments\CashfreeSubscriptionService;
use App\Support\PlanAccess;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubscriptionApiController extends Controller
{
    public function __construct(
        protected CashfreeSubscriptionService $cashfree,
        protected PostPaymentChartService $chartService,
    ) {}

    /**
     * 1. Checkout: Create Subscription Mandate
     * POST /api/subscriptions/checkout
     */
    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'plan_slug' => ['required', 'string', 'exists:subscription_plans,slug'],
            'cycle' => ['required', 'string', 'in:monthly,quarterly,annually'],
            'child_id' => ['nullable', 'integer', 'exists:children,id'],
        ]);

        $plan = SubscriptionPlan::where('slug', $validated['plan_slug'])
            ->where('is_active', true)
            ->firstOrFail();

        $childId = $validated['child_id'] ?? null;

        // Free Basic Plan
        if ($plan->slug === 'basic') {
            $dietSub = DietSubscription::create([
                'user_id' => $user->id,
                'child_id' => $childId,
                'subscription_plan_id' => $plan->id,
                'model' => $plan->name,
                'status' => 'active',
                'payment_status' => 'free',
                'amount_paid' => 0,
                'starts_at' => now(),
                'expires_at' => null,
            ]);

            $this->chartService->generateIfReady($dietSub);

            return response()->json([
                'success' => true,
                'gateway' => 'free',
                'subscription_id' => $dietSub->id,
                'message' => 'Free plan activated successfully.',
            ]);
        }

        [$priceField, $days, $cycleSuffix] = match ($validated['cycle']) {
            'monthly' => ['monthly_price', 30, 'monthly'],
            'quarterly' => ['quarterly_price', 90, 'quarterly'],
            'annually' => ['annual_price', 365, 'annually'],
        };

        $price = (float) $plan->{$priceField};
        if ($price <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Selected billing cycle is not available for this plan.',
            ], 422);
        }

        $cashfreePlanId = strtolower($plan->slug . '_' . $cycleSuffix . '_' . (int) $price);
        $cashfreeSubId = 'sub_usr_' . $user->id . '_' . $plan->slug . '_' . time();

        // Ensure the plan exists on Cashfree with this exact price
        try {
            $this->cashfree->getPlan($cashfreePlanId);
        } catch (\Throwable) {
            $intervalType = match ($validated['cycle']) {
                'annually' => 'YEAR',
                default => 'MONTH',
            };
            $intervals = match ($validated['cycle']) {
                'quarterly' => 3,
                default => 1,
            };
            $maxCycles = match ($validated['cycle']) {
                'annually' => 10,
                'quarterly' => 20,
                default => 60,
            };
            $planName = ucwords(str_replace('_', ' ', $plan->slug . ' ' . $cycleSuffix . ' ' . (int) $price));

            try {
                $this->cashfree->createPlan([
                    'plan_id' => $cashfreePlanId,
                    'plan_name' => $planName,
                    'plan_type' => 'PERIODIC',
                    'plan_currency' => 'INR',
                    'plan_recurring_amount' => round($price, 2),
                    'plan_max_amount' => round($price, 2),
                    'plan_max_cycles' => $maxCycles,
                    'plan_intervals' => $intervals,
                    'plan_interval_type' => $intervalType,
                    'plan_note' => "NutriBuddy {$planName}",
                ]);
            } catch (\Throwable $planErr) {
                Log::warning('Cashfree plan creation error: ' . $planErr->getMessage());
            }
        }

        $customerPhone = substr(preg_replace('/\D+/', '', (string) ($user->phone ?: '9999999999')), -10);
        if (strlen($customerPhone) < 10) {
            $customerPhone = '9999999999';
        }

        $payload = [
            'subscription_id' => $cashfreeSubId,
            'plan_details' => [
                'plan_id' => $cashfreePlanId,
            ],
            'customer_details' => [
                'customer_id' => 'user_' . $user->id,
                'customer_email' => $user->email ?: 'user_' . $user->id . '@nutribuddy.in',
                'customer_phone' => $customerPhone,
                'customer_name' => $user->name ?: 'NutriBuddy Parent',
            ],
            'subscription_meta' => [
                'return_url' => route('subscription.verify') . '?subscription_id={subscription_id}',
                'notification_details' => [
                    'notification_modes' => ['EMAIL', 'SMS'],
                ],
            ],
            'subscription_note' => "NutriBuddy {$plan->name} ({$cycleSuffix})",
            'subscription_tags' => [
                'user_id' => (string) $user->id,
                'child_id' => (string) ($childId ?? ''),
                'plan_slug' => (string) $plan->slug,
            ],
        ];

        try {
            $cfResponse = $this->cashfree->createSubscription($payload);
        } catch (Throwable $e) {
            Log::error('Cashfree subscription creation error', [
                'user_id' => $user->id,
                'plan_slug' => $plan->slug,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to initialize subscription: ' . $e->getMessage(),
            ], 500);
        }

        $subscriptionSessionId = $cfResponse['subscription_session_id'] ?? null;
        $cfSubscriptionId = $cfResponse['cf_subscription_id'] ?? null;

        // Create subscription in local database
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'child_id' => $childId,
            'cashfree_subscription_id' => $cashfreeSubId,
            'cf_subscription_id' => $cfSubscriptionId,
            'status' => 'INITIALIZED',
            'current_period_start' => now(),
            'current_period_end' => now()->addDays($days),
        ]);

        $dietSubscription = DietSubscription::create([
            'user_id' => $user->id,
            'child_id' => $childId,
            'subscription_plan_id' => $plan->id,
            'model' => $plan->name . ' (' . ucfirst($validated['cycle']) . ')',
            'status' => 'pending',
            'payment_status' => 'pending',
            'razorpay_order_id' => $cashfreeSubId, // Gateway reference
            'amount_paid' => round($price, 2),
            'starts_at' => now(),
            'expires_at' => now()->addDays($days),
        ]);

        return response()->json([
            'success' => true,
            'gateway' => 'cashfree',
            'subscription_id' => $dietSubscription->id,
            'local_subscription_id' => $subscription->id,
            'cashfree_subscription_id' => $cashfreeSubId,
            'subscription_session_id' => $subscriptionSessionId,
            'cf_subscription_id' => $cfSubscriptionId,
            'mode' => config('services.cashfree.env', 'sandbox'),
        ]);
    }

    /**
     * 2. Verify Subscription Status
     * GET/POST /api/subscriptions/verify
     */
    public function verify(Request $request)
    {
        $cashfreeSubId = $request->input('subscription_id') ?: $request->query('subscription_id');

        if (! $cashfreeSubId) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Missing subscription_id.'], 400);
            }
            return redirect()->route('diet_chart')->with('error', 'Missing subscription identifier.');
        }

        try {
            $cfData = $this->cashfree->getSubscription($cashfreeSubId);
            $cfStatus = strtoupper($cfData['subscription_status'] ?? '');

            $isSuccessful = in_array($cfStatus, ['ACTIVE', 'BANK_APPROVAL_PENDING'], true);

            $subscription = Subscription::where('cashfree_subscription_id', $cashfreeSubId)->first();
            $dietSub = DietSubscription::where('razorpay_order_id', $cashfreeSubId)->latest()->first();

            if ($subscription) {
                $subscription->update([
                    'status' => $cfStatus,
                    'cf_subscription_id' => $cfData['cf_subscription_id'] ?? $subscription->cf_subscription_id,
                ]);
            }

            $needsQuestionnaire = false;
            $childId = $dietSub?->child_id;
            $planSlug = $dietSub?->subscriptionPlan?->slug;

            if ($isSuccessful && $dietSub) {
                if ($dietSub->payment_status !== 'paid') {
                    $dietSub->update([
                        'status' => 'active',
                        'payment_status' => 'paid',
                        'razorpay_payment_id' => $cfData['cf_subscription_id'] ?? 'cf_sub_' . time(),
                    ]);

                    // Generate chart (review_status stays pending_review, never auto-approved)
                    $ready = $this->chartService->generateIfReady($dietSub);
                    $needsQuestionnaire = ! $ready;
                }
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $isSuccessful,
                    'status' => $cfStatus,
                    'child_id' => $childId,
                    'plan_slug' => $planSlug,
                    'needs_questionnaire' => $needsQuestionnaire,
                    'message' => $isSuccessful
                        ? 'Subscription activated successfully! Diet chart is pending nutritionist review.'
                        : 'Subscription status: ' . $cfStatus,
                ]);
            }

            // Redirect back to child profile or questionnaire
            if ($needsQuestionnaire && $childId && $planSlug) {
                return redirect()->route('frontend.nutrition.questionnaire.child', $childId)
                    ->with('success', 'Subscription activated! Please complete the additional questions.')
                    ->with('plan', $planSlug);
            }

            return redirect()->route('child.profile')
                ->with('success', 'Subscription activated successfully! Your diet chart has been submitted to our nutritionist for review.');

        } catch (Throwable $e) {
            Log::error('Subscription verification failed', [
                'subscription_id' => $cashfreeSubId,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()], 500);
            }

            return redirect()->route('diet_chart')->with('error', 'Error verifying subscription.');
        }
    }

    /**
     * 3. Webhook: Idempotent Cashfree Subscription Webhook Receiver
     * POST /api/webhooks/cashfree
     */
    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('x-webhook-signature');
        $timestamp = $request->header('x-webhook-timestamp');
        $rawPayload = (string) $request->getContent();

        if (! $this->cashfree->verifyWebhookSignature($rawPayload, $signature, $timestamp)) {
            Log::warning('Cashfree subscription webhook signature verification failed.');
            return response()->json(['message' => 'Invalid webhook signature.'], 400);
        }

        // Replay attack prevention: timestamp within 300 seconds
        if ($timestamp && abs(time() - (int) ($timestamp / 1000 > 1000000000 ? $timestamp / 1000 : $timestamp)) > 300) {
            Log::warning('Cashfree webhook timestamp expired', ['timestamp' => $timestamp]);
            return response()->json(['message' => 'Webhook timestamp expired.'], 400);
        }

        $payload = $request->all();
        $eventType = $payload['type'] ?? $payload['event'] ?? 'UNKNOWN';
        $eventKey = hash('sha256', ($payload['data']['subscription_details']['subscription_id'] ?? '') . ':' . $eventType . ':' . $timestamp);

        // Idempotency check
        $existingEvent = WebhookEvent::where('event_key', $eventKey)->first();
        if ($existingEvent && $existingEvent->processed) {
            return response()->json(['status' => 'already_processed']);
        }

        $webhookEvent = WebhookEvent::updateOrCreate(
            ['event_key' => $eventKey],
            [
                'event_type' => $eventType,
                'payload' => $payload,
                'processed' => false,
                'received_at' => now(),
            ]
        );

        DB::transaction(function () use ($payload, $eventType, $webhookEvent) {
            $data = $payload['data'] ?? [];
            $subDetails = $data['subscription_details'] ?? [];
            $cashfreeSubId = $subDetails['subscription_id'] ?? null;

            if (! $cashfreeSubId) {
                $webhookEvent->update(['processed' => true]);
                return;
            }

            $subscription = Subscription::where('cashfree_subscription_id', $cashfreeSubId)->first();
            $dietSub = DietSubscription::where('razorpay_order_id', $cashfreeSubId)->latest()->first();

            switch ($eventType) {
                case 'SUBSCRIPTION_STATUS_CHANGE':
                    $newStatus = strtoupper($subDetails['subscription_status'] ?? '');
                    if ($subscription) {
                        $subscription->update(['status' => $newStatus]);
                    }

                    if ($newStatus === 'ACTIVE' && $dietSub && $dietSub->payment_status !== 'paid') {
                        $dietSub->update([
                            'status' => 'active',
                            'payment_status' => 'paid',
                            'razorpay_payment_id' => $subDetails['cf_subscription_id'] ?? null,
                        ]);
                        // Generate diet chart with review_status = 'pending_review'
                        $this->chartService->generateIfReady($dietSub);
                    } elseif (in_array($newStatus, ['CANCELLED', 'EXPIRED'], true)) {
                        if ($dietSub) {
                            $dietSub->update(['status' => 'cancelled', 'cancelled_at' => now()]);
                        }
                    }
                    break;

                case 'SUBSCRIPTION_PAYMENT_SUCCESS':
                    $paymentData = $data['payment_details'] ?? [];
                    $cfPaymentId = (string) ($paymentData['cf_payment_id'] ?? '');
                    $amount = (float) ($paymentData['payment_amount'] ?? 0);

                    if ($subscription) {
                        $subscription->update([
                            'status' => 'ACTIVE',
                            'current_period_start' => now(),
                            'current_period_end' => now()->addDays(30),
                        ]);

                        Payment::create([
                            'subscription_id' => $subscription->id,
                            'order_id' => null,
                            'provider' => 'cashfree',
                            'transaction_type' => 'capture',
                            'payment_type' => 'CHARGE',
                            'status' => 'SUCCESS',
                            'currency' => 'INR',
                            'amount' => $amount,
                            'gateway_order_id' => $cashfreeSubId,
                            'gateway_payment_id' => $cfPaymentId,
                            'invoice_number' => 'INV-' . strtoupper(uniqid()),
                            'gateway_payload' => $payload,
                            'paid_at' => now(),
                        ]);
                    }

                    if ($dietSub && $dietSub->payment_status !== 'paid') {
                        $dietSub->update([
                            'status' => 'active',
                            'payment_status' => 'paid',
                            'razorpay_payment_id' => $cfPaymentId,
                        ]);
                        $this->chartService->generateIfReady($dietSub);
                    }
                    break;

                case 'SUBSCRIPTION_PAYMENT_FAILED':
                case 'SUBSCRIPTION_PAYMENT_DECLINED':
                    $paymentData = $data['payment_details'] ?? [];
                    if ($subscription) {
                        $subscription->update(['status' => 'PAST_DUE']);

                        Payment::create([
                            'subscription_id' => $subscription->id,
                            'order_id' => null,
                            'provider' => 'cashfree',
                            'transaction_type' => 'charge',
                            'payment_type' => 'CHARGE',
                            'status' => 'FAILED',
                            'currency' => 'INR',
                            'amount' => (float) ($paymentData['payment_amount'] ?? 0),
                            'gateway_order_id' => $cashfreeSubId,
                            'gateway_payment_id' => (string) ($paymentData['cf_payment_id'] ?? ''),
                            'failure_reason' => $paymentData['failure_reason'] ?? 'Payment failed',
                            'gateway_payload' => $payload,
                        ]);
                    }
                    break;
            }

            $webhookEvent->update(['processed' => true]);
        });

        return response()->json(['status' => 'ok']);
    }

    /**
     * 4. Cancel Subscription
     * POST /api/subscriptions/cancel
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $subscription = Subscription::where('user_id', $user->id)
            ->whereIn('status', ['ACTIVE', 'INITIALIZED'])
            ->latest()
            ->first();

        if (! $subscription) {
            return response()->json(['success' => false, 'message' => 'No active subscription found.'], 404);
        }

        try {
            $this->cashfree->manageSubscription($subscription->cashfree_subscription_id, 'CANCEL');

            $subscription->update([
                'cancel_at_period_end' => true,
                'cancelled_at' => now(),
                'status' => 'CANCELLED',
            ]);

            DietSubscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully.',
            ]);
        } catch (Throwable $e) {
            Log::error('Cancel subscription failed', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->cashfree_subscription_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 5. Current Subscription Status & Usage
     * GET /api/subscriptions/current
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $subscription = Subscription::with('plan')
            ->where('user_id', $user->id)
            ->whereIn('status', ['ACTIVE', 'INITIALIZED', 'PAST_DUE'])
            ->latest()
            ->first();

        $dietSub = DietSubscription::with('subscriptionPlan')
            ->where('user_id', $user->id)
            ->usable()
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'subscription' => $subscription,
            'diet_subscription' => $dietSub,
            'is_active' => $subscription?->isActive() ?? ($dietSub !== null),
        ]);
    }
}
