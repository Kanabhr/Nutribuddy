<?php

namespace App\Services\Nutrition;

use App\Models\DietSubscription;
use App\Models\SubscriptionPlan;

class SubscriptionPlanService
{
    /** @param array<string, mixed> $validated */
    public function create(array $validated, bool $isActive): SubscriptionPlan
    {
        $features = collect(preg_split('/\R/', (string) ($validated['features_text'] ?? '')))
            ->map(fn (string $feature) => trim($feature))
            ->filter()
            ->map(fn (string $feature) => mb_substr($feature, 0, 255))
            ->values()
            ->all();
        unset($validated['features_text']);

        return SubscriptionPlan::create([
            ...$validated,
            'features' => $features,
            'is_active' => $isActive,
        ]);
    }

    public function find(int $id): SubscriptionPlan
    {
        return SubscriptionPlan::findOrFail($id);
    }

    /** @return array<string, mixed> */
    public function listingData(): array
    {
        return [
            'plans' => SubscriptionPlan::all(),
            'totalPlans' => SubscriptionPlan::count(),
            'totalPurchases' => DietSubscription::count(),
            'totalRevenue' => DietSubscription::where('payment_status', 'paid')->sum('amount_paid'),
            'activeSubscriptions' => DietSubscription::where('status', 'active')->count(),
            'planPurchasesData' => SubscriptionPlan::withCount('dietSubscriptions')
                ->get()->pluck('diet_subscriptions_count', 'name')->toArray(),
        ];
    }

    /** @param array<string, mixed> $validated */
    public function update(SubscriptionPlan $plan, array $validated, bool $isActive): void
    {
        $plan->update([
            'name' => $validated['name'],
            'monthly_price' => $validated['monthly_price'] ?? null,
            'quarterly_price' => $validated['quarterly_price'] ?? null,
            'annual_price' => $validated['annual_price'] ?? null,
            'is_active' => $isActive,
        ]);
    }

    public function delete(SubscriptionPlan $plan): bool
    {
        if ($plan->dietSubscriptions()->exists()) {
            return false;
        }

        return (bool) $plan->delete();
    }
}
