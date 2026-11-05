<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\Nutrition\DietPlanItem;
use App\Models\Nutrition\DietPlanMeal;
use App\Models\Nutrition\FoodMaster;
use App\Support\PlanAccess;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class RuleBasedDietPlanEngine
{
    public function __construct(
        private readonly ChildProfileService $profiles,
        private readonly FoodFilterPipeline $filters,
        private readonly TargetEngine $targets,
    ) {}

    public function generate(ChildAssessment $assessment, array $answers = []): array
    {
        if (isset($answers['_seed'])) {
            mt_srand((int) $answers['_seed']);
        }

        $profile = $this->profiles->normalize($assessment, $answers);
        $clinicalFlags = app(RiskFlaggingEngine::class)->generateFlags($assessment, $profile);
        if ($clinicalFlags->isNotEmpty()) {
            $profile['professional_review_required'] = true;
            $assessment->forceFill(['professional_review_required' => true])->save();
        }

        $target = $this->targets->getTargets($assessment->child);
        if (! $target) {
            throw new RuntimeException('No ICMR nutrition target is configured for this child.');
        }

        $foods = $this->filters->getEligibleFoods($assessment);
        if ($foods->isEmpty()) {
            throw new RuntimeException('No foods meet this child\'s safety and dietary restrictions.');
        }

        // Resolve plan length from the child's active subscription.
        $slug  = PlanAccess::slugForChild($assessment->child) ?? 'basic';
        $start = now()->startOfDay();
        $N     = PlanAccess::planDays($slug, $start);

        // Cache each food's nutrients once so the accessor is not rebuilt per call.
        $nutrientCache = [];
        foreach ($foods as $food) {
            $nutrientCache[$food->id] = $food->nutrients;
        }

        $targetValues = $target->toNutrientArray();

        // $history[food_id] => last day_index it was used (for gap scoring).
        // $allUses[food_id] => list of day_indices it has appeared (for variety penalty).
        $history  = [];
        $allUses  = [];
        $schedule = [];
        $debug    = ['profile' => $profile, 'eligible_food_count' => $foods->count(), 'meals' => []];

        for ($dayIndex = 0; $dayIndex < $N; $dayIndex++) {
            $date       = $start->copy()->addDays($dayIndex);
            $weekdayName = $date->format('l'); // Monday … Sunday (repeats)

            $dailyTotals = array_fill_keys(array_keys($targetValues), 0.0);

            foreach ($this->slots($profile['meal_pattern']) as $slot) {
                $candidates = $this->filters->forSlot($foods, $slot);
                if ($candidates->isEmpty()) {
                    throw new RuntimeException("No safe food is configured for {$slot}.");
                }

                $ranked   = $this->rank($candidates, $slot, $profile, $targetValues, $dailyTotals, $history, $allUses, $dayIndex, $nutrientCache);
                $selected = $this->pick($ranked);
                $schedule[$dayIndex][$slot] = $this->plate($selected, $slot, $nutrientCache);

                $this->addNutrition($dailyTotals, $selected, $nutrientCache);

                $history[$selected->id]   = $dayIndex;
                $allUses[$selected->id][] = $dayIndex;

                $debug['meals'][] = [
                    'day_index'        => $dayIndex,
                    'day'              => $weekdayName,
                    'slot'             => $slot,
                    'selected_food_id' => $selected->id,
                    'candidate_scores' => $ranked->take(5)->map(fn ($r) => [
                        'food_id' => $r['food']->id,
                        'score'   => $r['score'],
                        'reasons' => $r['reasons'],
                    ])->values()->all(),
                ];
            }

            $schedule[$dayIndex]['_daily_nutrition'] = $dailyTotals;
            $schedule[$dayIndex]['_date']            = $date->format('Y-m-d');
            $schedule[$dayIndex]['_weekday']         = $weekdayName;
        }

        $this->validate($schedule, $foods, $profile);

        $dailyNutrition = collect($schedule)->mapWithKeys(
            fn ($meals, $idx) => [$idx => $meals['_daily_nutrition']]
        )->all();

        $dailyGaps  = $this->gaps($dailyNutrition, $targetValues);
        $gapSummary = $this->summariseGaps($dailyGaps, $targetValues);

        $plan = $this->save($assessment, $schedule, $dailyNutrition, $dailyGaps, $gapSummary, $profile, $debug, $start, $N);

        return [
            'success'                    => true,
            'child_id'                   => $assessment->child_id,
            'assessment_id'              => $assessment->id,
            'diet_plan_id'               => $plan->id,
            'child_profile'              => $profile,
            'daily_nutrients'            => $dailyNutrition,
            'nutrient_gaps'              => $gapSummary,
            'professional_review_required' => $profile['professional_review_required'],
            'generated_at'               => now()->toIso8601String(),
        ];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function slots(string $pattern): array
    {
        return match ($pattern) {
            '3 meals'    => ['Breakfast', 'Lunch', 'Dinner'],
            // Two mains and two snacks is the least-assumptive interpretation of this pattern.
            '2 + snacks' => ['Breakfast', 'Morning Snack', 'Lunch', 'Evening Snack'],
            default      => ['Breakfast', 'Morning Snack', 'Lunch', 'Evening Snack', 'Dinner'],
        };
    }

    private function rank(
        Collection $foods,
        string     $slot,
        array      $profile,
        array      $targets,
        array      $totals,
        array      $history,
        array      $allUses,
        int        $dayIndex,
        array      $nutrientCache,
    ): Collection {
        $ranked = $foods->map(function (FoodMaster $food) use ($profile, $targets, $totals, $history, $allUses, $dayIndex, $nutrientCache) {
            $nutrients = $nutrientCache[$food->id] ?? $food->nutrients;

            $nutrition = 0.0;
            foreach ($targets as $nutrient => $target) {
                if ($target <= 0) {
                    continue;
                }
                $remaining  = max(0, $target - ($totals[$nutrient] ?? 0));
                $nutrition += min(1, (float) ($nutrients->{$nutrient} ?? 0) / max(1, $remaining));
            }
            $score   = min(30, $nutrition * 2.5);
            $reasons = ['meal_slot_match', 'diet_match', 'allergy_safe'];

            $preferredRegions = preg_split('/\s*,\s*/', (string) ($profile['region'] ?? 'No preference')) ?: [];
            if (collect($preferredRegions)->contains(fn ($r) => strtolower($r) === strtolower((string) $food->region))) {
                $score   += 5;
                $reasons[] = 'region_match';
            }
            if ($food->kid_accept_1_5) {
                $score   += min(5, (float) $food->kid_accept_1_5);
                $reasons[] = 'kid_acceptance';
            }
            if ($profile['cooking_medium'] && str_contains(strtolower((string) $food->method), strtolower($profile['cooking_medium']))) {
                $score   += 5;
                $reasons[] = 'cooking_compatibility';
            }
            if (($profile['dairy_glasses'] ?? 0) >= 2 && $this->flag($food, 'contains_dairy')) {
                $score -= 4;
            }

            // Repeat-gap penalty: the food's own repeat_gap_days (default 3) — unchanged from original.
            $lastUsed = $history[$food->id] ?? null;
            $gap      = max(1, (int) ($food->repeat_gap_days ?: 3));
            if ($lastUsed !== null && $dayIndex - $lastUsed < $gap) {
                $score   -= 20;
                $reasons[] = 'repetition_penalty';
            }

            // Variety scoring: -8 if the dish appeared in the previous 7 days,
            // and an additional -2 for each earlier use in this plan.
            $uses = $allUses[$food->id] ?? [];
            if (! empty($uses)) {
                $recentUses = array_filter($uses, fn (int $d) => $dayIndex - $d <= 7);
                if (! empty($recentUses)) {
                    $score   -= 8;
                    $reasons[] = 'recent_7day_use';
                }
                $olderUses = array_filter($uses, fn (int $d) => $dayIndex - $d > 7);
                $score   -= 2 * count($olderUses);
            }

            return ['food' => $food, 'score' => max(0.1, $score + 15), 'reasons' => $reasons];
        })->sortByDesc('score')->values();

        // Prefer candidates respecting the repeat gap; relax only if the database has no alternatives.
        $fresh = $ranked->filter(fn ($item) => ! in_array('repetition_penalty', $item['reasons'], true));

        return $fresh->isNotEmpty() ? $fresh : $ranked;
    }

    private function pick(Collection $ranked): FoodMaster
    {
        $top   = $ranked->take(5)->values();
        $total = $top->sum('score');
        $point = mt_rand() / mt_getrandmax() * $total;
        foreach ($top as $item) {
            $point -= $item['score'];
            if ($point <= 0) {
                return $item['food'];
            }
        }

        return $top->first()['food'];
    }

    private function plate(FoodMaster $food, string $slot, array $nutrientCache): array
    {
        $nutrients = [];
        $cached    = $nutrientCache[$food->id] ?? $food->nutrients;
        foreach ($this->nutrientKeys() as $key) {
            $nutrients[$key] = (float) ($cached->{$key} ?? 0);
        }

        return [
            'slot'             => $slot,
            'items'            => [[
                'food_id'   => $food->id,
                'dish_id'   => $food->dish_id,
                'food_name' => $food->dish_name,
                'portion_g' => $food->serving_g,
                'nutrients' => $nutrients,
            ]],
            'total_nutrients'  => $nutrients,
        ];
    }

    private function addNutrition(array &$totals, FoodMaster $food, array $nutrientCache): void
    {
        $cached = $nutrientCache[$food->id] ?? $food->nutrients;
        foreach (array_keys($totals) as $key) {
            $totals[$key] += (float) ($cached->{$key} ?? 0);
        }
    }

    /**
     * Per-day nutrient gaps keyed by day_index.
     * Stored in plan_data['daily_gaps'].
     */
    private function gaps(array $daily, array $targets): array
    {
        return collect($daily)->map(function ($totals) use ($targets) {
            return collect($targets)
                ->filter(fn ($target) => $target > 0)
                ->map(fn ($target, $key) => [
                    'target'          => $target,
                    'achieved'        => $totals[$key] ?? 0,
                    'adequacy_percent' => round(($totals[$key] ?? 0) / $target * 100, 2),
                ])
                ->all();
        })->all();
    }

    /**
     * Roll the per-day gaps up to a single summary per nutrient.
     * adequacy_percent is averaged across days; gap_category is derived from that average.
     * Nutrients with target <= 0 are skipped.
     * Stored in plan_data['nutrient_gaps'].
     */
    private function summariseGaps(array $dailyGaps, array $targets): array
    {
        $summary = [];
        foreach ($targets as $nutrient => $target) {
            if ($target <= 0) {
                continue;
            }
            $totals = collect($dailyGaps)->pluck($nutrient)->filter()->values();
            if ($totals->isEmpty()) {
                continue;
            }
            $avgAchieved   = $totals->avg('achieved');
            $avgAdequacy   = $totals->avg('adequacy_percent');
            $summary[$nutrient] = [
                'target'          => $target,
                'absorbed_amount' => round($avgAchieved, 4),
                'adequacy_percent' => round($avgAdequacy, 2),
                'gap_category'    => $this->gapCategory($avgAdequacy),
            ];
        }

        return $summary;
    }

    private function gapCategory(float $adequacy): string
    {
        if ($adequacy >= 90)  return 'Met';
        if ($adequacy >= 70)  return 'Partial gap';
        if ($adequacy >= 50)  return 'Significant gap';
        return 'Critical gap';
    }

    private function validate(array $schedule, Collection $eligible, array $profile): void
    {
        $ids = $eligible->pluck('id')->all();
        foreach ($schedule as $meals) {
            foreach ($meals as $slot => $plate) {
                if (in_array($slot, ['_daily_nutrition', '_date', '_weekday'], true)) {
                    continue;
                }
                if (empty($plate['items']) || ! in_array($plate['items'][0]['food_id'], $ids, true)) {
                    throw new RuntimeException('Generated plan failed safety validation.');
                }
            }
        }
    }

    private function save(
        ChildAssessment $assessment,
        array  $schedule,
        array  $dailyNutrition,
        array  $dailyGaps,
        array  $gapSummary,
        array  $profile,
        array  $debug,
        Carbon $start,
        int    $N,
    ): DietPlan {
        $plan = DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'version'             => (int) $assessment->dietPlans()->max('version') + 1,
            'status'              => 'pending_review',
            'review_status'       => 'pending_review',
            'ai_summary'          => 'Rule-based plan awaiting nutritionist review.',
            'plan_data'           => [
                'schedule'        => [
                    'start_date' => $start->format('Y-m-d'),
                    'days'       => $N,
                ],
                'child_profile'   => $profile,
                'daily_nutrients' => $dailyNutrition,
                'daily_gaps'      => $dailyGaps,
                'nutrient_gaps'   => $gapSummary,
                'validation'      => [
                    'allergy_safe'            => true,
                    'diet_compliant'          => true,
                    'nutrition_checked'       => true,
                    'medical_review_required' => $profile['professional_review_required'],
                ],
                'generation_debug' => $debug,
            ],
        ]);

        $order = 0;
        foreach ($schedule as $dayIndex => $meals) {
            foreach ($meals as $slot => $plate) {
                if (in_array($slot, ['_daily_nutrition', '_date', '_weekday'], true)) {
                    continue;
                }
                $meal = DietPlanMeal::create([
                    'diet_plan_id' => $plan->id,
                    'meal_slot'    => $slot,
                    'timing'       => null,
                    'focus'        => 'Rule-based nutrition selection',
                    'sort_order'   => $order++,
                    'day_index'    => $dayIndex,
                ]);
                foreach ($plate['items'] as $item) {
                    DietPlanItem::create([
                        'diet_plan_meal_id' => $meal->id,
                        'food_id'           => $item['food_id'],
                        'portion_size'      => $item['portion_g'].'g',
                    ]);
                }
            }
        }

        return $plan;
    }

    private function nutrientKeys(): array
    {
        return [
            'energy_kcal', 'protein_g', 'fat_g', 'fiber_g', 'calcium_mg',
            'iron_mg', 'zinc_mg', 'magnesium_mg', 'vitamin_a_mcg', 'vitamin_c_mg',
            'vitamin_d_mcg', 'thiamin_b1_mg', 'riboflavin_b2_mg', 'niacin_b3_mg',
            'vitamin_b6_mg', 'folate_mcg', 'vitamin_b12_mcg',
        ];
    }

    private function yes(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'yes', 'true'], true);
    }

    private function flag(FoodMaster $food, string $field): bool
    {
        return $this->yes($food->getAttributes()[$field] ?? null);
    }
}
