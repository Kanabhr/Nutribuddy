<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\Nutrition\DietPlanItem;
use App\Models\Nutrition\DietPlanMeal;
use App\Models\Nutrition\FoodMaster;
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
            throw new RuntimeException('No foods meet this child’s safety and dietary restrictions.');
        }

        $targetValues = $target->toNutrientArray();
        $history = [];
        $debug = ['profile' => $profile, 'eligible_food_count' => $foods->count(), 'meals' => []];
        $week = [];

        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $dayIndex => $day) {
            $dailyTotals = array_fill_keys(array_keys($targetValues), 0.0);
            foreach ($this->slots($profile['meal_pattern']) as $slot) {
                $candidates = $this->filters->forSlot($foods, $slot);
                if ($candidates->isEmpty()) {
                    throw new RuntimeException("No safe food is configured for {$slot}.");
                }

                $ranked = $this->rank($candidates, $slot, $profile, $targetValues, $dailyTotals, $history, $dayIndex);
                $selected = $this->pick($ranked);
                $week[$day][$slot] = $this->plate($selected, $slot);
                $this->addNutrition($dailyTotals, $selected);
                $history[$selected->id] = $dayIndex;
                $debug['meals'][] = ['day' => $day, 'slot' => $slot, 'selected_food_id' => $selected->id, 'candidate_scores' => $ranked->take(5)->map(fn ($r) => ['food_id' => $r['food']->id, 'score' => $r['score'], 'reasons' => $r['reasons']])->values()->all()];
            }
            $week[$day]['_daily_nutrition'] = $dailyTotals;
        }

        $this->validate($week, $foods, $profile);
        $dailyNutrition = collect($week)->map(fn ($meals) => $meals['_daily_nutrition'])->all();
        $gaps = $this->gaps($dailyNutrition, $targetValues);
        $plan = $this->save($assessment, $week, $dailyNutrition, $gaps, $profile, $debug);

        return ['success' => true, 'child_id' => $assessment->child_id, 'assessment_id' => $assessment->id, 'diet_plan_id' => $plan->id, 'child_profile' => $profile, 'daily_nutrients' => $dailyNutrition, 'nutrient_gaps' => $gaps, 'professional_review_required' => $profile['professional_review_required'], 'generated_at' => now()->toIso8601String()];
    }

    private function slots(string $pattern): array
    {
        return match ($pattern) {
            '3 meals' => ['Breakfast', 'Lunch', 'Dinner'],
            // Existing UI does not define this further; two mains and two snacks is the least-assumptive interpretation.
            '2 + snacks' => ['Breakfast', 'Morning Snack', 'Lunch', 'Evening Snack'],
            default => ['Breakfast', 'Morning Snack', 'Lunch', 'Evening Snack', 'Dinner'],
        };
    }

    private function rank(Collection $foods, string $slot, array $profile, array $targets, array $totals, array $history, int $day): Collection
    {
        $ranked = $foods->map(function (FoodMaster $food) use ($profile, $targets, $totals, $history, $day) {
            $nutrition = 0.0;
            foreach ($targets as $nutrient => $target) {
                if ($target <= 0) {
                    continue;
                }
                $remaining = max(0, $target - ($totals[$nutrient] ?? 0));
                $nutrition += min(1, (float) ($food->nutrients->{$nutrient} ?? 0) / max(1, $remaining));
            }
            $score = min(30, $nutrition * 2.5);
            $reasons = ['meal_slot_match', 'diet_match', 'allergy_safe'];

            $preferredRegions = preg_split('/\s*,\s*/', (string) ($profile['region'] ?? 'No preference')) ?: [];
            if (collect($preferredRegions)->contains(fn ($region) => strtolower($region) === strtolower((string) $food->region))) {
                $score += 5;
                $reasons[] = 'region_match';
            }
            if ($food->kid_accept_1_5) {
                $score += min(5, (float) $food->kid_accept_1_5);
                $reasons[] = 'kid_acceptance';
            }
            if ($profile['cooking_medium'] && str_contains(strtolower((string) $food->method), strtolower($profile['cooking_medium']))) {
                $score += 5;
                $reasons[] = 'cooking_compatibility';
            }
            if (($profile['dairy_glasses'] ?? 0) >= 2 && $this->flag($food, 'contains_dairy')) {
                $score -= 4;
            }

            $lastUsed = $history[$food->id] ?? null;
            $gap = max(1, (int) ($food->repeat_gap_days ?: 3));
            if ($lastUsed !== null && $day - $lastUsed < $gap) {
                $score -= 20;
                $reasons[] = 'repetition_penalty';
            }

            return ['food' => $food, 'score' => max(0.1, $score + 15), 'reasons' => $reasons];
        })->sortByDesc('score')->values();

        // Prefer candidates respecting their repeat gap; only relax variety if the database has no alternative.
        $fresh = $ranked->filter(fn ($item) => ! in_array('repetition_penalty', $item['reasons'], true));

        return $fresh->isNotEmpty() ? $fresh : $ranked;
    }

    private function pick(Collection $ranked): FoodMaster
    {
        $top = $ranked->take(5)->values();
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

    private function plate(FoodMaster $food, string $slot): array
    {
        $nutrients = [];
        foreach ($this->nutrientKeys() as $key) {
            $nutrients[$key] = (float) ($food->nutrients->{$key} ?? 0);
        }

        return ['slot' => $slot, 'items' => [['food_id' => $food->id, 'dish_id' => $food->dish_id, 'food_name' => $food->dish_name, 'portion_g' => $food->serving_g, 'nutrients' => $nutrients]], 'total_nutrients' => $nutrients];
    }

    private function addNutrition(array &$totals, FoodMaster $food): void
    {
        foreach (array_keys($totals) as $key) {
            $totals[$key] += (float) ($food->nutrients->{$key} ?? 0);
        }
    }

    private function gaps(array $daily, array $targets): array
    {
        return collect($daily)->map(function ($totals) use ($targets) {
            return collect($targets)->map(fn ($target, $key) => ['target' => $target, 'achieved' => $totals[$key] ?? 0, 'adequacy_percent' => $target > 0 ? round(($totals[$key] ?? 0) / $target * 100, 2) : null])->all();
        })->all();
    }

    private function validate(array $week, Collection $eligible, array $profile): void
    {
        $ids = $eligible->pluck('id')->all();
        foreach ($week as $meals) {
            foreach ($meals as $slot => $plate) {
                if ($slot === '_daily_nutrition') {
                    continue;
                }
                if (empty($plate['items']) || ! in_array($plate['items'][0]['food_id'], $ids, true)) {
                    throw new RuntimeException('Generated plan failed safety validation.');
                }
            }
        }
    }

    private function save(ChildAssessment $assessment, array $week, array $daily, array $gaps, array $profile, array $debug): DietPlan
    {
        $plan = DietPlan::create(['child_assessment_id' => $assessment->id, 'version' => (int) $assessment->dietPlans()->max('version') + 1, 'status' => 'pending_review', 'review_status' => 'pending_review', 'ai_summary' => 'Rule-based plan awaiting nutritionist review.', 'plan_data' => ['child_profile' => $profile, 'daily_nutrients' => $daily, 'nutrient_gaps' => $gaps, 'validation' => ['allergy_safe' => true, 'diet_compliant' => true, 'nutrition_checked' => true, 'medical_review_required' => $profile['professional_review_required']], 'generation_debug' => $debug]]);
        $order = 0;
        foreach ($week as $meals) {
            foreach ($meals as $slot => $plate) {
                if ($slot === '_daily_nutrition') {
                    continue;
                }
                $meal = DietPlanMeal::create(['diet_plan_id' => $plan->id, 'meal_slot' => $slot, 'timing' => null, 'focus' => 'Rule-based nutrition selection', 'sort_order' => $order++]);
                foreach ($plate['items'] as $item) {
                    DietPlanItem::create(['diet_plan_meal_id' => $meal->id, 'food_id' => $item['food_id'], 'portion_size' => $item['portion_g'].'g']);
                }
            }
        }

        return $plan;
    }

    private function nutrientKeys(): array
    {
        return ['energy_kcal', 'protein_g', 'fat_g', 'fiber_g', 'calcium_mg', 'iron_mg', 'zinc_mg', 'magnesium_mg', 'vitamin_a_mcg', 'vitamin_c_mg', 'vitamin_d_mcg', 'thiamin_b1_mg', 'riboflavin_b2_mg', 'niacin_b3_mg', 'vitamin_b6_mg', 'folate_mcg', 'vitamin_b12_mcg'];
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
