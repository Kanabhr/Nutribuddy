<?php

namespace App\Services\Nutrition;

use App\Models\DietSubscription;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\NutritionLead;
use App\Models\User;
use App\Notifications\DietLifecycleNotification;
use Illuminate\Support\Facades\Log;

/** Sends admin feed alerts for diet subscription and chart lifecycle events. */
class DietLifecycleNotificationService
{
    public function subscriptionActivated(DietSubscription $subscription): void
    {
        $this->safely('subscription_activated', function () use ($subscription): void {
            $subscription->loadMissing(['user', 'child', 'subscriptionPlan']);
            $userName = $subscription->user?->name ?? 'A customer';
            $planName = $subscription->subscriptionPlan?->name ?? $subscription->model ?? 'Diet plan';
            $childName = $subscription->child?->name;

            $this->notifyAdmins([
                'event' => 'subscription_activated',
                'title' => 'Diet plan purchase confirmed',
                'message' => "{$userName} purchased {$planName}".($childName ? " for {$childName}." : '.'),
                'subscription_id' => $subscription->id,
                'action_url' => route('admin.nutrition.diet-subscriptions.show', $subscription),
            ]);
        });
    }

    public function chartGenerated(ChildAssessment $assessment, DietPlan $dietPlan): void
    {
        $this->safely('diet_chart_generated', function () use ($assessment, $dietPlan): void {
            $assessment->loadMissing('child');
            $childName = $assessment->child?->name ?? 'A child';

            $this->notifyAdmins([
                'event' => 'diet_chart_generated',
                'title' => 'Diet chart generated',
                'message' => "A diet chart for {$childName} was generated and is awaiting professional review.",
                'child_id' => $assessment->child_id,
                'diet_plan_id' => $dietPlan->id,
                'action_url' => route('admin.nutrition.diet-subscriptions.index'),
            ]);
        });
    }

    public function chartApproved(NutritionLead $lead, DietPlan $dietPlan): void
    {
        $this->safely('diet_chart_approved', function () use ($lead, $dietPlan): void {
            $lead->loadMissing('child');
            $childName = $lead->child?->name ?? 'A child';

            $this->notifyAdmins([
                'event' => 'diet_chart_approved',
                'title' => 'Diet chart approved',
                'message' => "The diet chart for {$childName} was approved and released to the customer.",
                'child_id' => $lead->child_id,
                'diet_plan_id' => $dietPlan->id,
                'action_url' => route('admin.nutrition.diet-subscriptions.index'),
            ]);
        });
    }

    public function subscriptionExpired(DietSubscription $subscription): void
    {
        $this->safely('subscription_expired', function () use ($subscription): void {
            $subscription->loadMissing(['user', 'child', 'subscriptionPlan']);
            $userName = $subscription->user?->name ?? 'A customer';
            $planName = $subscription->subscriptionPlan?->name ?? $subscription->model ?? 'Diet plan';
            $childName = $subscription->child?->name;

            $this->notifyAdmins([
                'event' => 'subscription_expired',
                'title' => 'Diet subscription expired',
                'message' => "{$planName} for {$userName}".($childName ? " ({$childName})" : '').' has expired.',
                'subscription_id' => $subscription->id,
                'action_url' => route('admin.nutrition.diet-subscriptions.show', $subscription),
            ]);
        });
    }

    private function safely(string $event, callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $exception) {
            Log::warning('Diet lifecycle notification could not be sent.', [
                'event' => $event,
                'exception' => $exception,
            ]);
        }
    }

    /** @param array<string, int|string|null> $data */
    private function notifyAdmins(array $data): void
    {
        User::query()->where('role', 'admin')->chunkById(100, function ($admins) use ($data): void {
            foreach ($admins as $admin) {
                try {
                    $admin->notify(new DietLifecycleNotification($data));
                } catch (\Throwable $exception) {
                    Log::warning('Diet lifecycle notification could not be stored.', [
                        'event' => $data['event'] ?? null,
                        'admin_id' => $admin->id,
                        'exception' => $exception,
                    ]);
                }
            }
        });
    }
}
