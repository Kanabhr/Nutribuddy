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
    // ------------------------------------------------------------------
    // Slot calorie-share constants (must sum to 1.0)
    // Breakfast 0.22, Morning Snack 0.13, Lunch 0.30,
    // Evening Snack 0.13, Dinner 0.22.
    // ------------------------------------------------------------------
    private const SLOT_SHARES = [
        'Breakfast'      => 0.22,
        'Morning Snack'  => 0.13,
        'Lunch'          => 0.30,
        'Evening Snack'  => 0.13,
        'Dinner'         => 0.22,
    ];

    // Bounded portion-scaling clamping range.
    private const SCALE_MIN = 0.8;
    private const SCALE_MAX = 1.3;

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

        $profile       = $this->profiles->normalize($assessment, $answers);
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

        // Compute daily energy target (energy_kcal from ICMR row).
        // Used to derive per-slot calorie budgets.
        $dailyEnergyTarget = (float) ($targetValues['energy_kcal'] ?? 0);

        // Pre-compute slot budgets for the child's actual meal pattern.
        // Budget is zero when no energy target is available (keeps legacy behaviour).
        $slotBudgets = $this->computeSlotBudgets(
            $this->slots($profile['meal_pattern']),
            $dailyEnergyTarget
        );

        // $history[food_id] => last day_index it was used (for gap scoring).
        // $allUses[food_id] => list of day_indices it has appeared (for variety penalty).
        $history  = [];
        $allUses  = [];
        $schedule = [];
        $debug    = ['profile' => $profile, 'eligible_food_count' => $foods->count(), 'meals' => []];

        for ($dayIndex = 0; $dayIndex < $N; $dayIndex++) {
            $date        = $start->copy()->addDays($dayIndex);
            $weekdayName = $date->format('l'); // Monday … Sunday (repeats)

            $dailyTotals = array_fill_keys(array_keys($targetValues), 0.0);

            foreach ($this->slots($profile['meal_pattern']) as $slot) {
                $candidates = $this->filters->forSlot($foods, $slot);
                if ($candidates->isEmpty()) {
                    throw new RuntimeException("No safe food is configured for {$slot}.");
                }

                $slotBudget = $slotBudgets[$slot] ?? 0.0;

                // -------------------------------------------------------
                // Lunch & Dinner: try to pick MAIN + optional SIDE.
                // All other slots: single-dish as before.
                // -------------------------------------------------------
                if (in_array($slot, ['Lunch', 'Dinner'], true)) {
                    $plate = $this->pickLunchDinner(
                        $candidates,
                        $slot,
                        $slotBudget,
                        $profile,
                        $targetValues,
                        $dailyTotals,
                        $history,
                        $allUses,
                        $dayIndex,
                        $nutrientCache,
                        $debug
                    );
                } else {
                    // Single-dish slot (Breakfast, snacks).
                    $ranked   = $this->rank($candidates, $slot, $profile, $targetValues, $dailyTotals, $history, $allUses, $dayIndex, $nutrientCache);
                    $selected = $this->pick($ranked);

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

                    $plate = $this->plateSingle($selected, $slot, $nutrientCache);
                    // Record history for single dish.
                    $history[$selected->id]   = $dayIndex;
                    $allUses[$selected->id][] = $dayIndex;
                }

                // -------------------------------------------------------
                // E. Bounded portion scaling for every slot.
                // After dish(es) are picked, scale portion_g by
                //   f = slot_budget / sum(dish kcal), clamped to [0.8, 1.3].
                // If slot_budget == 0 keep f = 1 (legacy behaviour).
                // -------------------------------------------------------
                $plate = $this->applyPortionScaling($plate, $slotBudget, $nutrientCache);

                $schedule[$dayIndex][$slot] = $plate;

                // Accumulate scaled nutrients into daily totals.
                foreach ($plate['items'] as $item) {
                    foreach (array_keys($dailyTotals) as $key) {
                        $dailyTotals[$key] += (float) ($item['nutrients'][$key] ?? 0);
                    }
                }
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
            'success'                      => true,
            'child_id'                     => $assessment->child_id,
            'assessment_id'                => $assessment->id,
            'diet_plan_id'                 => $plan->id,
            'child_profile'                => $profile,
            'daily_nutrients'              => $dailyNutrition,
            'nutrient_gaps'                => $gapSummary,
            'professional_review_required' => $profile['professional_review_required'],
            'generated_at'                 => now()->toIso8601String(),
        ];
    }

    // ------------------------------------------------------------------
    // Slot budget helpers
    // ------------------------------------------------------------------

    /**
     * Compute per-slot calorie budget = (slot share / sum of active shares) * daily target.
     *
     * @param  string[]    $activeSlots  The slots actually used for this child's pattern.
     * @param  float       $dailyTarget  Child's daily energy target (kcal). 0 = not available.
     * @return array<string, float>      Slot name => budget in kcal.
     */
    private function computeSlotBudgets(array $activeSlots, float $dailyTarget): array
    {
        if ($dailyTarget <= 0) {
            return array_fill_keys($activeSlots, 0.0);
        }

        $totalShare = 0.0;
        foreach ($activeSlots as $slot) {
            $totalShare += self::SLOT_SHARES[$slot] ?? 0.0;
        }

        if ($totalShare <= 0) {
            return array_fill_keys($activeSlots, 0.0);
        }

        $budgets = [];
        foreach ($activeSlots as $slot) {
            $share          = self::SLOT_SHARES[$slot] ?? 0.0;
            $budgets[$slot] = ($share / $totalShare) * $dailyTarget;
        }
        return $budgets;
    }

    // ------------------------------------------------------------------
    // Lunch / Dinner two-dish selection
    // ------------------------------------------------------------------

    /**
     * Pick a MAIN for Lunch/Dinner, then optionally a SIDE if the main's
     * kcal is below 85 % of the slot budget. Falls back to single-dish
     * behaviour when no MAIN is available.
     *
     * History is updated inside this method for both dishes.
     * Debug entries are also written here.
     */
    private function pickLunchDinner(
        Collection $candidates,
        string     $slot,
        float      $slotBudget,
        array      $profile,
        array      $targetValues,
        array      $dailyTotals,
        array      &$history,
        array      &$allUses,
        int        $dayIndex,
        array      $nutrientCache,
        array      &$debug,
    ): array {
        $weekdayName = ''; // populated in main loop via _weekday; we use day_index here

        // Filter MAIN and SIDE pools from the already-safety-filtered candidates.
        $mainPool = $candidates->filter(fn (FoodMaster $f) => FoodRole::isMain($f))->values();
        $sidePool = $candidates->filter(fn (FoodMaster $f) => FoodRole::isSide($f)
            && ! FoodRole::isSoleBanned($f))->values();

        // Fall back to single-dish if no MAIN available.
        if ($mainPool->isEmpty()) {
            // Use full candidates (not filtered to side-only) as fallback,
            // excluding sole-banned foods only when alternatives exist.
            $fallbackPool = $candidates->filter(fn (FoodMaster $f) => ! FoodRole::isSoleBanned($f))->values();
            if ($fallbackPool->isEmpty()) {
                $fallbackPool = $candidates; // last resort: even sole-banned
            }

            $ranked   = $this->rank($fallbackPool, $slot, $profile, $targetValues, $dailyTotals, $history, $allUses, $dayIndex, $nutrientCache);
            $selected = $this->pick($ranked);

            $debug['meals'][] = [
                'day_index'        => $dayIndex,
                'day'              => $this->weekdayFromIndex($dayIndex),
                'slot'             => $slot,
                'selected_food_id' => $selected->id,
                'role'             => 'main',
                'main_fallback'    => true,
                'candidate_scores' => $ranked->take(5)->map(fn ($r) => [
                    'food_id' => $r['food']->id,
                    'score'   => $r['score'],
                    'reasons' => $r['reasons'],
                ])->values()->all(),
            ];

            $history[$selected->id]   = $dayIndex;
            $allUses[$selected->id][] = $dayIndex;

            return $this->plateSingle($selected, $slot, $nutrientCache);
        }

        // 1. Pick MAIN.
        $rankedMain = $this->rank($mainPool, $slot, $profile, $targetValues, $dailyTotals, $history, $allUses, $dayIndex, $nutrientCache);
        $main       = $this->pick($rankedMain);

        $debug['meals'][] = [
            'day_index'        => $dayIndex,
            'day'              => $this->weekdayFromIndex($dayIndex),
            'slot'             => $slot,
            'selected_food_id' => $main->id,
            'role'             => 'main',
            'candidate_scores' => $rankedMain->take(5)->map(fn ($r) => [
                'food_id' => $r['food']->id,
                'score'   => $r['score'],
                'reasons' => $r['reasons'],
            ])->values()->all(),
        ];

        $history[$main->id]   = $dayIndex;
        $allUses[$main->id][] = $dayIndex;

        // 2. Decide whether to add a SIDE.
        $mainKcal = (float) (($nutrientCache[$main->id] ?? $main->nutrients)->energy_kcal ?? 0);
        $needSide = $slotBudget > 0 && $mainKcal < 0.85 * $slotBudget;

        if (! $needSide || $sidePool->isEmpty()) {
            return $this->plateSingle($main, $slot, $nutrientCache);
        }

        // 3. Pick SIDE: exclude main's food_id, prefer a different food_group
        //    when an alternative exists.
        $sidePool = $sidePool->filter(fn (FoodMaster $f) => $f->id !== $main->id);
        if ($sidePool->isEmpty()) {
            return $this->plateSingle($main, $slot, $nutrientCache);
        }

        // Try to avoid same food_group as main.
        $differentGroup = $sidePool->filter(fn (FoodMaster $f) => $f->food_group !== $main->food_group)->values();
        $sideRankPool   = $differentGroup->isNotEmpty() ? $differentGroup : $sidePool->values();

        $rankedSide = $this->rank($sideRankPool, $slot, $profile, $targetValues, $dailyTotals, $history, $allUses, $dayIndex, $nutrientCache);
        $side       = $this->pick($rankedSide);

        $history[$side->id]   = $dayIndex;
        $allUses[$side->id][] = $dayIndex;

        $debug['meals'][] = [
            'day_index'        => $dayIndex,
            'day'              => $this->weekdayFromIndex($dayIndex),
            'slot'             => $slot,
            'selected_food_id' => $side->id,
            'role'             => 'side',
            'candidate_scores' => $rankedSide->take(5)->map(fn ($r) => [
                'food_id' => $r['food']->id,
                'score'   => $r['score'],
                'reasons' => $r['reasons'],
            ])->values()->all(),
        ];

        return $this->plateDouble($main, $side, $slot, $nutrientCache);
    }

    /**
     * Minimal helper — day_index → weekday name.
     * The engine generates starting from now()->startOfDay(), and this
     * helper is only used for the debug log; the _weekday key in schedule
     * is set by the main loop. We accept it may be approximate here.
     */
    private function weekdayFromIndex(int $dayIndex): string
    {
        // The start is now()->startOfDay() when called from generate(); we
        // approximate by using the current date as day 0 for the debug label.
        return now()->startOfDay()->copy()->addDays($dayIndex)->format('l');
    }

    // ------------------------------------------------------------------
    // Plate helpers
    // ------------------------------------------------------------------

    private function plateSingle(FoodMaster $food, string $slot, array $nutrientCache): array
    {
        $cached    = $nutrientCache[$food->id] ?? $food->nutrients;
        $nutrients = [];
        foreach ($this->nutrientKeys() as $key) {
            $nutrients[$key] = (float) ($cached->{$key} ?? 0);
        }

        return [
            'slot'            => $slot,
            'items'           => [[
                'food_id'   => $food->id,
                'dish_id'   => $food->dish_id,
                'food_name' => $food->dish_name,
                'portion_g' => $food->serving_g,
                'nutrients' => $nutrients,
            ]],
            'total_nutrients' => $nutrients,
        ];
    }

    private function plateDouble(FoodMaster $main, FoodMaster $side, string $slot, array $nutrientCache): array
    {
        $makeItem = function (FoodMaster $food) use ($nutrientCache): array {
            $cached    = $nutrientCache[$food->id] ?? $food->nutrients;
            $nutrients = [];
            foreach ($this->nutrientKeys() as $key) {
                $nutrients[$key] = (float) ($cached->{$key} ?? 0);
            }
            return [
                'food_id'   => $food->id,
                'dish_id'   => $food->dish_id,
                'food_name' => $food->dish_name,
                'portion_g' => $food->serving_g,
                'nutrients' => $nutrients,
            ];
        };

        $mainItem = $makeItem($main);
        $sideItem = $makeItem($side);

        $total = [];
        foreach ($this->nutrientKeys() as $key) {
            $total[$key] = ($mainItem['nutrients'][$key] ?? 0) + ($sideItem['nutrients'][$key] ?? 0);
        }

        return [
            'slot'            => $slot,
            'items'           => [$mainItem, $sideItem],
            'total_nutrients' => $total,
        ];
    }

    // ------------------------------------------------------------------
    // E. Bounded portion scaling
    // ------------------------------------------------------------------

    /**
     * Scale all items in a plate toward the slot budget.
     *
     * f = slot_budget / sum(item kcal) clamped to [SCALE_MIN, SCALE_MAX].
     * Applied uniformly to every item in the slot.
     * portion_g is stored as a float (will be formatted as "Xg" in save()).
     * When slot_budget is 0 or total kcal is 0, f = 1 (no scaling).
     *
     * Items whose portion_g is null or <= 0 are left untouched (portion and
     * nutrients kept as-is; they contribute their nominal kcal to the sum but
     * are not themselves scaled).
     */
    private function applyPortionScaling(array $plate, float $slotBudget, array $nutrientCache): array
    {
        if ($slotBudget <= 0) {
            return $plate;
        }

        $totalKcal = 0.0;
        foreach ($plate['items'] as $item) {
            $totalKcal += (float) ($item['nutrients']['energy_kcal'] ?? 0);
        }

        if ($totalKcal <= 0) {
            return $plate;
        }

        $f = $slotBudget / $totalKcal;
        $f = max(self::SCALE_MIN, min(self::SCALE_MAX, $f));

        $scaledItems = [];
        foreach ($plate['items'] as $item) {
            // Skip scaling for items with a missing or zero portion_g.
            // Such items have no serving size; applying a factor would produce
            // meaningless (or "0g") portions in the stored plan.
            $portionG = $item['portion_g'] ?? null;
            if ($portionG === null || (float) $portionG <= 0) {
                $scaledItems[] = $item;
                continue;
            }

            $scaledNutrients = [];
            foreach ($item['nutrients'] as $key => $value) {
                $scaledNutrients[$key] = (float) $value * $f;
            }
            $scaledItems[] = array_merge($item, [
                'portion_g' => (float) $portionG * $f,
                'nutrients' => $scaledNutrients,
            ]);
        }

        $scaledTotal = [];
        foreach ($this->nutrientKeys() as $key) {
            $scaledTotal[$key] = 0.0;
            foreach ($scaledItems as $item) {
                $scaledTotal[$key] += (float) ($item['nutrients'][$key] ?? 0);
            }
        }

        return array_merge($plate, [
            'items'           => $scaledItems,
            'total_nutrients' => $scaledTotal,
        ]);
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

            // Repeat-gap penalty: the food's own repeat_gap_days (default 3).
            $lastUsed = $history[$food->id] ?? null;
            $gap      = max(1, (int) ($food->repeat_gap_days ?: 3));
            if ($lastUsed !== null && $dayIndex - $lastUsed < $gap) {
                $score   -= 20;
                $reasons[] = 'repetition_penalty';
            }

            // Variety scoring.
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

        // Prefer candidates respecting the repeat gap; relax only if no alternatives exist.
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
                    'target'           => $target,
                    'achieved'         => $totals[$key] ?? 0,
                    'adequacy_percent' => round(($totals[$key] ?? 0) / $target * 100, 2),
                ])
                ->all();
        })->all();
    }

    /**
     * Roll the per-day gaps up to a single summary per nutrient.
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
            $avgAchieved = $totals->avg('achieved');
            $avgAdequacy = $totals->avg('adequacy_percent');
            $summary[$nutrient] = [
                'target'           => $target,
                'absorbed_amount'  => round($avgAchieved, 4),
                'adequacy_percent' => round($avgAdequacy, 2),
                'gap_category'     => $this->gapCategory($avgAdequacy),
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
                foreach ($plate['items'] as $item) {
                    if (! in_array($item['food_id'], $ids, true)) {
                        throw new RuntimeException('Generated plan failed safety validation.');
                    }
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
                    // Store the scaled portion rounded to the nearest gram.
                    // When portion_g is null or <= 0 (food has no serving size),
                    // fall back to null so the column stays empty rather than "0g".
                    $rawPortion = $item['portion_g'] ?? null;
                    $portionSize = null;
                    if ($rawPortion !== null && (float) $rawPortion > 0) {
                        $portionSize = ((int) round((float) $rawPortion)) . 'g';
                    }
                    DietPlanItem::create([
                        'diet_plan_meal_id' => $meal->id,
                        'food_id'           => $item['food_id'],
                        'portion_size'      => $portionSize,
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
