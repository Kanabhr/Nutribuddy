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
 * Q26 option texts as of C7 (new labels):
 *   "Pale skin or eyes"           → sign "Pale eyes/skin"
 *   "Tiredness or low energy"     → sign "Tiredness"
 *   "Hair fall"                   → sign "Hair fall"
 *   "Brittle nails"               → sign "Brittle nails"
 *   "Mouth ulcers or cracks"      → sign "Mouth-corner cracks/ulcers"
 *   "Poor night vision"           → sign "Night-vision trouble"
 *   "Delayed teeth or bowed legs" → sign "Delayed teeth/bowed legs"
 *   "Poor concentration"          → sign "Poor focus"
 *   "None of the above"           → no flag
 *   "Not sure"                    → no flag
 *
 * Legacy labels (pre-C7) are also accepted via VisibleSignsMap so
 * already-saved answers continue to produce the correct flags.
 *
 * DeficiencyRulesSeeder and the deficiency_rules table are NOT changed.
 * The mapping lives entirely in VisibleSignsMap::toSigns().
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
        $rawSigns = collect($answers['visible_signs'] ?? [])
            ->map(fn ($sign) => trim((string) $sign))
            ->filter()
            ->values()
            ->all();

        // Map new/legacy labels to canonical deficiency_rules.sign strings.
        $signs = VisibleSignsMap::toSigns($rawSigns);

        if ($signs === []) {
            return collect();
        }

        $flags = collect();

        // Exact match on deficiency_rules.sign — seeded by DeficiencyRulesSeeder.
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
     * @param  array<string>  $signs  Q26 option values (new or legacy labels)
     * @return array<string>          unique likely_gap values
     */
    public function resolveGapLabels(array $signs): array
    {
        // Map new/legacy labels to canonical deficiency_rules.sign strings.
        $canonicalSigns = VisibleSignsMap::toSigns($signs);

        if ($canonicalSigns === []) {
            return [];
        }

        return DeficiencyRule::whereIn('sign', $canonicalSigns)
            ->pluck('likely_gap')
            ->unique()
            ->values()
            ->all();
    }
}
