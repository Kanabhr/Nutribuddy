<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\ClinicalFlag;
use App\Models\Nutrition\DeficiencyRule;
use Illuminate\Support\Collection;

class RiskFlaggingEngine
{
    /**
     * Correlates visible signs, bowel habits, and illnesses to generate flags.
     */
    /** @param array<string, mixed> $answers @return Collection<int, ClinicalFlag> */
    public function generateFlags(ChildAssessment $assessment, array $answers): Collection
    {
        $signs = collect($answers['visible_signs'] ?? [])
            ->map(fn ($sign) => trim((string) $sign))
            ->filter()
            ->unique()
            ->values();

        if ($signs->isEmpty()) {
            return collect();
        }

        $flags = collect();
        $rules = DeficiencyRule::whereIn('sign', $signs)->get();

        foreach ($rules as $rule) {
            $flags->push(ClinicalFlag::firstOrCreate([
                'child_assessment_id' => $assessment->id,
                'flag_name' => $rule->likely_gap,
                'reason' => 'Observed sign: '.$rule->sign,
            ], ['priority' => 'medium']));
        }

        return $flags;
    }
}
