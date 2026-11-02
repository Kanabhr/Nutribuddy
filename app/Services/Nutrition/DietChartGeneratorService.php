<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\ChildAssessment;

/** Backwards-compatible entry point for all existing chart endpoints. */
class DietChartGeneratorService
{
    public function __construct(
        private readonly RuleBasedDietPlanEngine $engine,
        private readonly DietLifecycleNotificationService $notifications,
    ) {}

    public function generateDietChart(ChildAssessment $assessment, array $questionnaireAnswers = []): array
    {
        $result = $this->engine->generate($assessment, $questionnaireAnswers);

        if (($result['success'] ?? false) && ! empty($result['diet_plan_id'])) {
            $dietPlan = \App\Models\Nutrition\DietPlan::find($result['diet_plan_id']);
            if ($dietPlan) {
                $this->notifications->chartGenerated($assessment, $dietPlan);
            }
        }

        return $result;
    }
}
