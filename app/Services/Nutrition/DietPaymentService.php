<?php

namespace App\Services\Nutrition;

use App\Models\DietSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\PlanAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DietPaymentService
{
    public function __construct(private readonly DietLifecycleNotificationService $notifications) {}

    public function createOrder(User $user, array $data): array
    {
        $plan = SubscriptionPlan::where('slug', $data['plan_slug'])
            ->where('is_active', true)
            ->firstOrFail();

        // What does this child already have? (skipped when no child is attached yet)
        $existing = ! empty($data['child_id'])
            ? DietSubscription::query()
                ->where('user_id', $user->id)
                ->where('child_id', $data['child_id'])
                ->usable()
                ->with('subscriptionPlan')
                ->get()
            : collect();

        // FREE plan (Basic): no billing cycle, never expires, one per child.
        if ($plan->slug === 'basic') {
            if ($existing->isNotEmpty()) {
                return ['success' => true, 'gateway' => 'free', 'subscription_id' => $existing->first()->id];
            }

            $subscription = DietSubscription::create([
                'user_id' => $user->id,
                'child_id' => $data['child_id'] ?? null,
                'subscription_plan_id' => $plan->id,
                'model' => $plan->name,
                'status' => 'pending',
                'payment_status' => 'pending',
                'amount_paid' => 0,
                'starts_at' => now(),
                'expires_at' => null,
            ]);
            $this->activateFree($subscription);

            return ['success' => true, 'gateway' => 'free', 'subscription_id' => $subscription->id];
        }

        // Paid plans: no downgrades. Same plan = renewal, higher plan = upgrade.
        if (PlanAccess::rank($plan->slug) < PlanAccess::rank(PlanAccess::highestSlug($existing))) {
            return ['success' => false, 'message' => 'This child already has a higher plan.', '_status' => 422];
        }

        [$priceColumn, $days] = match ($data['cycle']) {
            'monthly' => ['monthly_price', 30],
            'quarterly' => ['quarterly_price', 90],
            'annually' => ['annual_price', 365],
        };
        $price = $plan->{$priceColumn};
        if ($price === null) {
            return ['success' => false, 'message' => 'This billing cycle is not available for the selected plan.', '_status' => 422];
        }
        $amountInPaise = (int) round((float) $price * 100);
        $amountInRupees = round((float) $price, 2);

        $subscription = DietSubscription::create([
            'user_id' => $user->id,
            'child_id' => $data['child_id'],
            'subscription_plan_id' => $plan->id,
            'model' => $plan->name.' ('.ucfirst($data['cycle']).')',
            'status' => 'pending',
            'payment_status' => 'pending',
            'amount_paid' => $amountInRupees,
            'starts_at' => now(),
            'expires_at' => now()->addDays($days),
        ]);

        if ($amountInRupees <= 0) {
            $this->activate($subscription);

            return ['success' => true, 'gateway' => 'free', 'subscription_id' => $subscription->id];
        }

        try {
            return $data['gateway'] === 'razorpay'
                ? $this->createRazorpayOrder($user, $plan, $subscription, $amountInPaise)
                : $this->createCashfreeOrder($user, $plan, $subscription, $amountInRupees);
        } catch (\Throwable $exception) {
            Log::error('Diet plan payment order failed.', [
                'subscription_id' => $subscription->id,
                'exception' => $exception,
            ]);

            return ['success' => false, 'message' => 'Unable to process payment at this time.', '_status' => 500];
        }
    }

    public function verifyRazorpayPayment(User $user, array $data): array
    {
        $subscription = DietSubscription::whereKey($data['subscription_id'])
            ->where('user_id', $user->id)->firstOrFail();

        if (! hash_equals((string) $subscription->razorpay_order_id, $data['razorpay_order_id'])) {
            return ['success' => false, 'message' => 'Invalid payment order.', '_status' => 400];
        }

        $expected = hash_hmac(
            'sha256',
            $data['razorpay_order_id'].'|'.$data['razorpay_payment_id'],
            config('services.razorpay.key_secret')
        );
        if (! hash_equals($expected, $data['razorpay_signature'])) {
            return ['success' => false, 'message' => 'Invalid payment signature', '_status' => 400];
        }

        $paymentResponse = Http::withBasicAuth(
            (string) config('services.razorpay.key_id'),
            (string) config('services.razorpay.key_secret'),
        )->get('https://api.razorpay.com/v1/payments/'.urlencode($data['razorpay_payment_id']));

        $payment = $paymentResponse->json() ?? [];
        if (! $paymentResponse->successful()
            || ($payment['order_id'] ?? null) !== $subscription->razorpay_order_id
            || (int) ($payment['amount'] ?? 0) !== (int) round((float) $subscription->amount_paid * 100)
            || strtoupper((string) ($payment['currency'] ?? '')) !== 'INR'
            || strtolower((string) ($payment['status'] ?? '')) !== 'captured') {
            return ['success' => false, 'message' => 'Payment is not captured or does not match this subscription.', '_status' => 400];
        }

        $subscription = $this->markPaid($subscription, $data['razorpay_payment_id']);
        $this->activateRelatedServices($subscription);

        return ['success' => true, 'message' => 'Subscription activated successfully!'];
    }

    public function confirmCashfreeReturn(?string $orderId, ?string $subscriptionId): array
    {
        if (! $orderId || ! $subscriptionId) {
            return ['success' => false, 'message' => 'Invalid payment return data.'];
        }

        try {
            $subscription = DietSubscription::whereKey($subscriptionId)
                ->where('razorpay_order_id', $orderId)->first();
            if (! $subscription) {
                return ['success' => false, 'message' => 'Invalid payment return data.'];
            }

            $baseUrl = \App\Services\Payments\CashfreePaymentService::resolveBaseUrl() . '/orders/';
            $response = Http::acceptJson()->withHeaders($this->cashfreeHeaders())
                ->get($baseUrl.urlencode($orderId));

            if (! $response->successful()) {
                return ['success' => false, 'message' => 'Payment was not successful or was cancelled.'];
            }
            $order = $response->json();
            if (($order['order_id'] ?? null) !== $orderId
                || round((float) ($order['order_amount'] ?? -1), 2) !== round((float) $subscription->amount_paid, 2)
                || strtoupper((string) ($order['order_currency'] ?? '')) !== 'INR'
                || strtoupper((string) ($order['order_status'] ?? '')) !== 'PAID') {
                return ['success' => false, 'message' => 'Payment was not successful or was cancelled.'];
            }

            $subscription = $this->markPaid($subscription, $order['cf_order_id'] ?? null);
            $this->activateRelatedServices($subscription);

            return ['success' => true, 'child_id' => $subscription->child_id];
        } catch (\Throwable $exception) {
            Log::error('Cashfree diet payment verification failed.', ['exception' => $exception]);

            return ['success' => false, 'message' => 'Error verifying payment.'];
        }
    }

    private function createRazorpayOrder(User $user, SubscriptionPlan $plan, DietSubscription $subscription, int $amount): array
    {
        $response = Http::withBasicAuth(config('services.razorpay.key_id'), config('services.razorpay.key_secret'))
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amount,
                'currency' => 'INR',
                'receipt' => 'diet_'.$subscription->id.'_'.uniqid(),
                'notes' => ['plan_id' => $plan->id, 'user_id' => $user->id, 'subscription_id' => $subscription->id],
            ]);
        if (! $response->successful()) {
            Log::warning('Razorpay rejected diet subscription order.', ['subscription_id' => $subscription->id]);

            return ['success' => false, 'message' => 'Unable to process payment at this time.', '_status' => 502];
        }
        $order = $response->json();
        $subscription->update(['razorpay_order_id' => $order['id']]);

        return [
            'success' => true, 'gateway' => 'razorpay', 'razorpay_order_id' => $order['id'],
            'amount' => $amount, 'currency' => 'INR', 'subscription_id' => $subscription->id,
        ];
    }

    private function createCashfreeOrder(User $user, SubscriptionPlan $plan, DietSubscription $subscription, float $amount): array
    {
        $orderId = 'diet_sub_'.$subscription->id.'_'.uniqid();
        $baseUrl = \App\Services\Payments\CashfreePaymentService::resolveBaseUrl() . '/orders';
        $response = Http::acceptJson()->withHeaders($this->cashfreeHeaders())->post($baseUrl, [
            'order_id' => $orderId,
            'order_amount' => $amount,
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id' => 'user_'.$user->id,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $this->customerPhone($user),
            ],
            'order_meta' => [
                'return_url' => route('diet.payment.cashfree.return')
                    .'?order_id={order_id}&subscription_id='.$subscription->id,
            ],
            'order_note' => 'Diet Subscription '.$plan->name,
        ]);
        if (! $response->successful()) {
            throw new \RuntimeException('Cashfree order creation failed.');
        }
        $order = $response->json();
        $subscription->update(['razorpay_order_id' => $order['order_id']]);

        return [
            'success' => true, 'gateway' => 'cashfree', 'payment_session_id' => $order['payment_session_id'],
            'order_id' => $order['order_id'], 'subscription_id' => $subscription->id,
            'mode' => \App\Services\Payments\CashfreePaymentService::resolveEnvironment(),
        ];
    }

    private function activateFree(DietSubscription $subscription): void
    {
        $subscription->update([
            'status' => 'active',
            'payment_status' => 'free',
            'starts_at' => now(),
            'expires_at' => null,
        ]);

        $subscription = $subscription->fresh();
        $this->notifications->subscriptionActivated($subscription);
        $this->activateRelatedServices($subscription);
    }

    /** After an upgrade, cancel the child's lower plans so only the new one stays active. */
    private function supersedeLowerPlans(DietSubscription $subscription): void
    {
        if (! $subscription->child_id) {
            return;
        }

        $newRank = PlanAccess::rank($subscription->subscriptionPlan?->slug);

        DietSubscription::query()
            ->where('user_id', $subscription->user_id)
            ->where('child_id', $subscription->child_id)
            ->where('id', '!=', $subscription->id)
            ->usable()
            ->with('subscriptionPlan')
            ->get()
            ->filter(fn (DietSubscription $old) => PlanAccess::rank($old->subscriptionPlan?->slug) < $newRank)
            ->each(fn (DietSubscription $old) => $old->update(['status' => 'cancelled', 'cancelled_at' => now()]));
    }

    private function activate(DietSubscription $subscription): void
    {
        $subscription = $this->markPaid($subscription);
        $this->activateRelatedServices($subscription);
    }

    private function markPaid(DietSubscription $subscription, ?string $paymentId = null): DietSubscription
    {
        return DB::transaction(function () use ($subscription, $paymentId): DietSubscription {
            $subscription = DietSubscription::query()->whereKey($subscription->id)->lockForUpdate()->firstOrFail();
            if ($subscription->payment_status === 'paid') {
                return $subscription;
            }

            $termSeconds = $subscription->starts_at && $subscription->expires_at
                ? $subscription->starts_at->diffInSeconds($subscription->expires_at)
                : 0;
            $startsAt = now();

            $subscription->update([
                'status' => 'active',
                'payment_status' => 'paid',
                'razorpay_payment_id' => $paymentId,
                'starts_at' => $startsAt,
                'expires_at' => $termSeconds > 0 ? $startsAt->copy()->addSeconds($termSeconds) : null,
            ]);

            $subscription = $subscription->fresh();
            $this->supersedeLowerPlans($subscription);
            $this->notifications->subscriptionActivated($subscription);

            return $subscription;
        });
    }

    private function activateRelatedServices(DietSubscription $subscription): void
    {
        app(NutritionLeadService::class)->createForSubscription($subscription);
        $assessment = $subscription->child?->assessments()->latest()->first();
        if (! $assessment || $assessment->dietPlans()->exists()) {
            return;
        }
        $answers = $assessment->answers()->pluck('answer_value', 'question_id')
            ->map(fn ($value) => is_string($value) ? json_decode($value, true) : $value)->all();
        try {
            $result = app(DietChartGeneratorService::class)->generateDietChart($assessment, $answers);
            if (! ($result['success'] ?? false)) {
                throw new \RuntimeException($result['message'] ?? 'Diet chart generation failed.');
            }
            $subscription->nutritionLead?->update(['status' => 'awaiting_review']);
        } catch (\Throwable $exception) {
            Log::error('Paid diet chart generation failed.', [
                'subscription_id' => $subscription->id,
                'exception' => $exception,
            ]);
        }
    }

    private function customerPhone(User $user): string
    {
        $digits = preg_replace('/\D+/', '', (string) ($user->phone ?? ''));

        return strlen($digits) >= 10 ? substr($digits, -10) : '9999999999';
    }

    private function cashfreeHeaders(): array
    {
        return [
            'x-client-id' => config('services.cashfree.app_id'),
            'x-client-secret' => config('services.cashfree.secret_key'),
            'x-api-version' => config('services.cashfree.api_version', '2025-01-01'),
        ];
    }
}
