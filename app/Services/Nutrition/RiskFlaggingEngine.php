<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\ClinicalFlag;
use App\Models\Nutrition\DeficiencyRule;
use Illuminate\Support\Collection;

/**
 * RiskFlaggingEngine
 *
 * Correlates the parent-visible signs answered in Q26 (questionnaire)
 * with DeficiencyRule rows and materialises ClinicalFlag records.
 *
 * Q26 option texts (exact, as seeded by QuestionnaireQuestionsSeeder):
 *   "Pale eyes/skin"
 *   "Tiredness"
 *   "Hair fall"
 *   "Brittle nails"
 *   "Mouth-corner cracks/ulcers"
 *   "Night-vision trouble"
 *   "Delayed teeth/bowed legs"
 *   "Poor focus"
 *   "None"
 *
 * DeficiencyRulesSeeder seeds one deficiency_rules row per option above
 * (except "None") using those strings as the `sign` column value.
 * This engine does an exact whereIn on deficiency_rules.sign — so the
 * strings must match precisely.
 */
class RiskFlaggingEngine
{
    /**
     * Generate ClinicalFlag records from a child's Q26 answers.
     *
     * @param  ChildAssessment         $assessment  The child's current assessment
     * @param  array<string, mixed>    $answers     Decoded questionnaire answers;
     *                                              expects 'visible_signs' key with
     *                                              an array of Q26 option strings
     * @return Collection<int, ClinicalFlag>
     */
    public function generateFlags(ChildAssessment $assessment, array $answers): Collection
    {
        $signs = collect($answers['visible_signs'] ?? [])
            ->map(fn($sign) => trim((string) $sign))
            ->filter()
            ->reject(fn($sign) => strtolower($sign) === 'none') // "None" has no rule
            ->unique()
            ->values();

        if ($signs->isEmpty()) {
            return collect();
        }

        $flags = collect();

        // Exact match on deficiency_rules.sign — seeded by DeficiencyRulesSeeder
        // using the Q26 option texts verbatim.
        $rules = DeficiencyRule::whereIn('sign', $signs)->get();

        foreach ($rules as $rule) {
            $flags->push(ClinicalFlag::firstOrCreate(
                [
                    'child_assessment_id' => $assessment->id,
                    'flag_name'           => $rule->likely_gap,
                    'reason'              => 'Observed sign: ' . $rule->sign,
                ],
                ['priority' => 'medium']
            ));
        }

        return $flags;
    }

    /**
     * Return likely_gap strings for matched signs, without persisting flags.
     * Useful for passing to ComboEngine::getCombos() as the $flags argument.
     *
     * @param  array<string>  $signs  Q26 option values
     * @return array<string>          unique likely_gap values
     */
    public function resolveGapLabels(array $signs): array
    {
        $filtered = collect($signs)
            ->map(fn($s) => trim((string) $s))
            ->filter()
            ->reject(fn($s) => strtolower($s) === 'none')
            ->unique()
            ->values()
            ->all();

        return DeficiencyRule::whereIn('sign', $filtered)
            ->pluck('likely_gap')
            ->unique()
            ->values()
            ->all();
    }
}
