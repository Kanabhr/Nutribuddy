<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CashfreeSubscriptionService
{
    protected string $appId;
    protected string $secretKey;
    protected string $apiVersion;
    protected string $env;

    public function __construct()
    {
        $this->appId = (string) (config('services.cashfree.subscription_app_id') ?: config('services.cashfree.app_id'));
        $this->secretKey = (string) (config('services.cashfree.subscription_secret_key') ?: config('services.cashfree.secret_key'));
        $this->apiVersion = (string) config('services.cashfree.api_version', '2025-01-01');
        $this->env = (string) config('services.cashfree.env', 'sandbox');
    }

    public function baseUrl(): string
    {
        return $this->env === 'production'
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';
    }

    public function client(): PendingRequest
    {
        return Http::acceptJson()
            ->withOptions(['verify' => config('app.env') === 'production'])
            ->withHeaders([
                'x-client-id' => $this->appId,
                'x-client-secret' => $this->secretKey,
                'x-api-version' => $this->apiVersion,
                'Content-Type' => 'application/json',
            ]);
    }

    /**
     * C1: Create Subscription Plan on Cashfree
     * POST /pg/plans
     */
    public function createPlan(array $data): array
    {
        $response = $this->client()->post($this->baseUrl() . '/plans', $data);

        if (! $response->successful()) {
            Log::error('Cashfree createPlan failed', [
                'status' => $response->status(),
                'response' => $response->json() ?: $response->body(),
                'payload' => $data,
            ]);
            throw new RuntimeException($response->json('message') ?? 'Failed to create plan on Cashfree.');
        }

        return $response->json();
    }

    /**
     * C2: Fetch Subscription Plan by ID
     * GET /pg/plans/{plan_id}
     */
    public function getPlan(string $planId): array
    {
        $response = $this->client()->get($this->baseUrl() . '/plans/' . urlencode($planId));

        if (! $response->successful()) {
            Log::error('Cashfree getPlan failed', [
                'plan_id' => $planId,
                'status' => $response->status(),
                'response' => $response->json() ?: $response->body(),
            ]);
            throw new RuntimeException($response->json('message') ?? 'Failed to fetch plan from Cashfree.');
        }

        return $response->json();
    }

    /**
     * C3: Create Subscription (Mandate / Recurring Session)
     * POST /pg/subscriptions
     */
    public function createSubscription(array $data): array
    {
        $response = $this->client()->post($this->baseUrl() . '/subscriptions', $data);

        if (! $response->successful()) {
            Log::error('Cashfree createSubscription failed', [
                'status' => $response->status(),
                'response' => $response->json() ?: $response->body(),
                'payload' => $data,
            ]);
            throw new RuntimeException($response->json('message') ?? 'Failed to create subscription on Cashfree.');
        }

        return $response->json();
    }

    /**
     * C4: Fetch Subscription Status and Details
     * GET /pg/subscriptions/{subscription_id}
     */
    public function getSubscription(string $subscriptionId): array
    {
        $response = $this->client()->get($this->baseUrl() . '/subscriptions/' . urlencode($subscriptionId));

        if (! $response->successful()) {
            Log::error('Cashfree getSubscription failed', [
                'subscription_id' => $subscriptionId,
                'status' => $response->status(),
                'response' => $response->json() ?: $response->body(),
            ]);
            throw new RuntimeException($response->json('message') ?? 'Failed to fetch subscription from Cashfree.');
        }

        return $response->json();
    }

    /**
     * C5: Manage Subscription (CANCEL, PAUSE, RESUME, CHANGE_PLAN)
     * POST /pg/subscriptions/{subscription_id}/manage
     */
    public function manageSubscription(string $subscriptionId, string $action, ?string $newPlanId = null): array
    {
        $payload = ['action' => strtoupper($action)];
        if ($newPlanId !== null && strtoupper($action) === 'CHANGE_PLAN') {
            $payload['plan_id'] = $newPlanId;
        }

        $response = $this->client()->post(
            $this->baseUrl() . '/subscriptions/' . urlencode($subscriptionId) . '/manage',
            $payload
        );

        if (! $response->successful()) {
            Log::error('Cashfree manageSubscription failed', [
                'subscription_id' => $subscriptionId,
                'action' => $action,
                'status' => $response->status(),
                'response' => $response->json() ?: $response->body(),
            ]);
            throw new RuntimeException($response->json('message') ?? "Failed to {$action} subscription on Cashfree.");
        }

        return $response->json();
    }

    /**
     * C6: Fetch All Payments for a Subscription
     * GET /pg/subscriptions/{subscription_id}/payments
     */
    public function getSubscriptionPayments(string $subscriptionId): array
    {
        $response = $this->client()->get($this->baseUrl() . '/subscriptions/' . urlencode($subscriptionId) . '/payments');

        if (! $response->successful()) {
            Log::error('Cashfree getSubscriptionPayments failed', [
                'subscription_id' => $subscriptionId,
                'status' => $response->status(),
                'response' => $response->json() ?: $response->body(),
            ]);
            throw new RuntimeException($response->json('message') ?? 'Failed to fetch subscription payments.');
        }

        return $response->json();
    }

    /**
     * C7: Fetch Specific Payment Details
     * GET /pg/subscriptions/{subscription_id}/payments/{cf_payment_id}
     */
    public function getPayment(string $subscriptionId, string $cfPaymentId): array
    {
        $url = $this->baseUrl() . '/subscriptions/' . urlencode($subscriptionId) . '/payments/' . urlencode($cfPaymentId);
        $response = $this->client()->get($url);

        if (! $response->successful()) {
            Log::error('Cashfree getPayment failed', [
                'subscription_id' => $subscriptionId,
                'cf_payment_id' => $cfPaymentId,
                'status' => $response->status(),
                'response' => $response->json() ?: $response->body(),
            ]);
            throw new RuntimeException($response->json('message') ?? 'Failed to fetch payment details.');
        }

        return $response->json();
    }

    /**
     * Verify Cashfree Webhook Signature (HMAC-SHA256)
     */
    public function verifyWebhookSignature(string $rawPayload, ?string $signature, ?string $timestamp): bool
    {
        if (empty($signature) || empty($timestamp) || empty($this->secretKey)) {
            return false;
        }

        $expectedSignature = base64_encode(hash_hmac('sha256', $timestamp . $rawPayload, $this->secretKey, true));

        return hash_equals($expectedSignature, $signature);
    }
}
