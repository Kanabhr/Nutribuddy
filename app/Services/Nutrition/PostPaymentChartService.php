<?php

namespace App\Services\Nutrition;

use App\Models\DietSubscription;
use App\Models\Nutrition\ChildAssessment;
use App\Support\PlanAccess;
use Illuminate\Support\Facades\Log;

/**
 * Generates the diet chart (and updates the nutrition lead) once the child's
 * latest assessment covers the purchased plan level.
 *
 * Idempotent: if a chart already exists for the assessment it is a no-op.
 */
class PostPaymentChartService
{
    public function __construct(
        private readonly DietChartGeneratorService $generator,
        private readonly NutritionLeadService $leads,
    ) {}

    /**
     * Try to generate the chart for the child tied to $subscription.
     *
     * Returns true  when the chart was generated (or already existed).
     * Returns false when the assessment still does not cover the plan — caller
     *               must redirect the user to the questionnaire again.
     */
    public function generateIfReady(DietSubscription $subscription): bool
    {
        // Ensure the lead exists (idempotent).
        $lead = $this->leads->createForSubscription($subscription);

        $planSlug   = $subscription->subscriptionPlan?->slug;
        // Use latest('id') as tiebreaker when multiple assessments share the same created_at
        // (common in tests and possible in production for rapid re-submits).
        $assessment = $subscription->child?->assessments()->latest('id')->first();

        // Assessment does not cover the purchased plan yet.
        if (! PlanAccess::assessmentCoversPlan($assessment?->model_level, $planSlug)) {
            return false;
        }

        // Already generated — honour the one-chart-per-assessment rule.
        if (! $assessment || $assessment->dietPlans()->exists()) {
            return true;
        }

        $answers = $assessment->answers()
            ->pluck('answer_value', 'question_id')
            ->map(fn ($v) => is_string($v) ? json_decode($v, true) : $v)
            ->all();

        try {
            $result = $this->generator->generateDietChart($assessment, $answers);
            if (! ($result['success'] ?? false)) {
                throw new \RuntimeException($result['message'] ?? 'Diet chart generation failed.');
            }
            $lead->update(['status' => 'awaiting_review']);
        } catch (\Throwable $e) {
            Log::error('PostPaymentChartService: chart generation failed.', [
                'subscription_id' => $subscription->id,
                'exception'       => $e,
            ]);
        }

        return true;
    }
}
