<?php

namespace App\Services\Nutrition;

use App\Models\DietSubscription;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\Nutrition\MealItemLog;
use App\Support\PlanAccess;
use Illuminate\Support\Carbon;

class NutritionPageService
{
    public function __construct(private readonly DietChartDisplayService $chartDisplay) {}

    /**
     * Build the meal-plan view data while keeping the legacy empty and pending states.
     *
     * Rule 5: when the user has a paid subscription but has not yet submitted the
     * higher-level questionnaire, $questionnairePending is true and the view shows
     * a "Complete your questionnaire" banner instead of the chart.
     *
     * @return array<string, mixed>|null
     */
    public function buildMealPlan(int $userId, ?string $childId): ?array
    {
        $childQuery = Child::where('user_id', $userId);
        $child = $childId
            ? $childQuery->whereKey($childId)->first()
            : $childQuery->latest()->first();
        $assessment = $child?->assessments()->latest()->first();
        $mealChild = $child ? $this->mealChildHeader($child, $assessment) : null;

        // Rule 5: check whether the child's active subscription requires a questionnaire.
        $questionnairePending = false;
        $questionnaireUrl     = null;
        if ($child) {
            $subscription = DietSubscription::where('user_id', $userId)
                ->where('child_id', $child->id)
                ->usable()
                ->with('subscriptionPlan')
                ->get()
                ->sortByDesc(fn ($s) => PlanAccess::rank($s->subscriptionPlan?->slug))
                ->first();

            if ($subscription) {
                $planSlug = $subscription->subscriptionPlan?->slug;
                if (! PlanAccess::assessmentCoversPlan($assessment?->model_level, $planSlug)) {
                    $questionnairePending = true;
                    $questionnaireUrl     = route('frontend.nutrition.questionnaire.child', $child->id)
                        . '?plan=' . urlencode((string) $planSlug);
                }
            }
        }

        if ($questionnairePending) {
            return compact('questionnairePending', 'questionnaireUrl', 'mealChild');
        }

        if (! $assessment) {
            return $mealChild ? compact('mealChild') : null;
        }

        $dietPlan = DietPlan::whereHas('assessment', fn ($query) => $query->where('child_id', $child->id))
            ->where('status', 'active')
            ->where('review_status', 'approved')
            ->latest()
            ->first();

        if (! $dietPlan) {
            $reviewPending = DietPlan::whereHas('assessment', fn ($query) => $query->where('child_id', $child->id))
                ->where('status', 'pending_review')
                ->exists();

            return compact('reviewPending', 'mealChild');
        }

        $mealPlan = $this->buildMealPlanDays($dietPlan, $dietPlan->assessment ?? $assessment);

        return compact('mealPlan', 'mealChild');
    }

    /**
     * Header facts for the meal-plan page, all read from real data.
     * Region and season are not shown: nothing in the data supports them.
     *
     * @return array{name: string, age: ?string, diet: ?string}
     */
    private function mealChildHeader(Child $child, ?ChildAssessment $assessment): array
    {
        $age = $child->dob ? (int) $child->dob->age : null;
        $diet = trim((string) ($assessment?->diet_type ?? ''));

        return [
            'name' => (string) $child->name,
            'age'  => $age === null ? null : $age . ' ' . ($age === 1 ? 'year' : 'years'),
            'diet' => $diet !== '' ? ucfirst($diet) : null,
        ];
    }

    /**
     * Turn formatPlan() output (index-keyed days, real dates) into the small
     * structure the meal-plan page renders. Every day keeps its own meals, so a
     * week-3 Saturday is never replaced by the week-1 Saturday.
     *
     * @return array{start_date: ?string, total_days: int, is_monthly: bool, active_day_index: int, days: array<int, array<string, mixed>>}
     */
    private function buildMealPlanDays(DietPlan $dietPlan, ChildAssessment $assessment): array
    {
        $planDays = $this->chartDisplay->formatPlan($dietPlan, $assessment);
        $today    = Carbon::today()->toDateString();

        // Unicode escapes keep this file free of encoding trouble.
        $emoji = [
            'Breakfast'     => "\u{1F373}",
            'Morning Snack' => "\u{1F34E}",
            'Lunch'         => "\u{1F35B}",
            'Evening Snack' => "\u{1F95C}",
            'Snack'         => "\u{1F95C}",
            'Dinner'        => "\u{1F372}",
        ];

        $days = [];
        $activeIndex = 0;

        foreach ($planDays as $day) {
            $date   = Carbon::parse($day['date']);
            $meals  = [];
            $dayKcal = 0;

            foreach ($day['meals'] as $slot => $data) {
                if (empty($data['items'])) {
                    continue;
                }

                $items = [];
                foreach ($data['items'] as $item) {
                    $grams = (float) preg_replace('/[^0-9.]+/', '', (string) ($item['portion_g'] ?? ''));
                    $regional = trim((string) ($item['regional_name'] ?? ''));

                    $items[] = [
                        'name'     => (string) $item['food_name'],
                        'regional' => ($regional !== '' && strcasecmp($regional, (string) $item['food_name']) !== 0) ? $regional : null,
                        'portion'  => $grams > 0 ? (int) round($grams) . ' g' : null,
                        'kcal'     => (int) round($item['nutrients']['energy_kcal'] ?? 0),
                    ];
                }

                $kcal = (int) round($data['total_nutrients']['energy_kcal'] ?? 0);
                $dayKcal += $kcal;

                $meals[] = [
                    'slot'  => (string) $slot,
                    'emoji' => $emoji[$slot] ?? "\u{1F37D}",
                    'focus' => $data['focus'] ?? null,
                    'kcal'  => $kcal,
                    'items' => $items,
                ];
            }

            if ($day['date'] === $today) {
                $activeIndex = (int) $day['index'];
            }

            $days[] = [
                'index'   => (int) $day['index'],
                'week'    => (int) $day['week'],
                'weekday' => $date->format('l'),
                'short'   => $date->format('D'),
                'date'    => $date->format('j M'),
                'iso'     => $day['date'],
                'is_today' => $day['date'] === $today,
                'kcal'    => $dayKcal,
                'meals'   => $meals,
            ];
        }

        return [
            'start_date'       => $days[0]['iso'] ?? null,
            'total_days'       => count($days),
            'is_monthly'       => count($days) > 7,
            'active_day_index' => $activeIndex,
            'days'             => $days,
        ];
    }

    /**
     * Build the existing nutrition page view data for the requested user's child.
     *
     * @return array{child: ?Child, assessment: mixed, dietPlan: ?DietPlan, viewData: array<string, mixed>}
     */
    public function build(int $userId, ?string $childId): array
    {
        $childQuery = Child::where('user_id', $userId);
        $child = $childId
            ? $childQuery->whereKey($childId)->first()
            : $childQuery->latest()->first();

        if (! $child) {
            return ['child' => null, 'assessment' => null, 'dietPlan' => null, 'viewData' => []];
        }

        $assessment = $child->assessments()->latest()->first();
        if (! $assessment) {
            return ['child' => $child, 'assessment' => null, 'dietPlan' => null, 'viewData' => ['child' => $child]];
        }

        if (! $assessment->initial_weight_kg) {
            $assessment->initial_weight_kg = $assessment->current_weight_kg;
            $assessment->initial_height_cm = $assessment->current_height_cm;
            $assessment->save();
        }

        $plans = DietPlan::whereHas('assessment', fn ($query) => $query->where('child_id', $child->id));
        $dietPlan = (clone $plans)
            ->where('status', 'active')
            ->where('review_status', 'approved')
            ->latest()
            ->first();
        $latestPlan = $plans->latest()->first();

        // Resolve today's macro targets, treating each nutrient independently.
        // Priority 1: flat nutrient_gaps summary already stored in plan_data.
        // Priority 2: TargetEngine live lookup for the child's age/sex.
        // A nutrient whose target is <= 0 (e.g. fat_g = 0 in a sparse ICMR row) is
        // marked "not available" for that nutrient only — stored as null — so the
        // view hides just that bar/ring while the other nutrients still show.
        // todayTarget stays null only when no source covers this child at all (no
        // plan gaps and no ICMR row); the view then shows the full
        // "Target not available" state for every nutrient.
        $todayTarget = null;

        if ($dietPlan) {
            $planData = $dietPlan->plan_data;
            if (is_string($planData)) {
                $planData = json_decode($planData, true) ?? [];
            }
            // plan_data['nutrient_gaps'] is a flat per-nutrient summary:
            //   { "energy_kcal": { "target": 1700, ... }, "protein_g": { ... }, ... }
            $gaps = is_array($planData) ? ($planData['nutrient_gaps'] ?? []) : [];
            if (
                isset($gaps['energy_kcal']['target']) &&
                isset($gaps['protein_g']['target']) &&
                isset($gaps['fat_g']['target'])
            ) {
                $todayTarget = [
                    'energy_kcal' => $this->targetOrNull((float) $gaps['energy_kcal']['target']),
                    'protein_g'   => $this->targetOrNull((float) $gaps['protein_g']['target']),
                    'fat_g'       => $this->targetOrNull((float) $gaps['fat_g']['target']),
                ];
            }
        }

        // Fallback: live TargetEngine lookup (no plan yet, or plan lacks gaps).
        if ($todayTarget === null) {
            $icmrTarget = app(\App\Services\Nutrition\TargetEngine::class)->getTargets($child);
            if ($icmrTarget) {
                $todayTarget = [
                    'energy_kcal' => $this->targetOrNull((float) $icmrTarget->energy_kcal),
                    'protein_g'   => $this->targetOrNull((float) $icmrTarget->protein_g),
                    'fat_g'       => $this->targetOrNull((float) $icmrTarget->fat_g),
                ];
            }
        }

        // Each nutrient is resolved independently above. A null entry means
        // "not available" for that nutrient only; the view hides just that bar
        // or ring. The whole $todayTarget is null only when there is no source
        // for this child at all.

        $viewData = compact('child', 'assessment', 'latestPlan', 'todayTarget');
        if (! $dietPlan) {
            return ['child' => $child, 'assessment' => $assessment, 'dietPlan' => null, 'viewData' => $viewData];
        }

        // formatPlan() returns a flat list of day objects, one per plan day:
        //   [ ['index'=>0,'label'=>'Monday','date'=>'…','week'=>1,'meals'=>[…]], … ]
        // This replaces the old formatSevenDayPlan() call that collapsed every
        // multi-week plan into 7 weekday-keyed entries and showed week 1 meals
        // for every later week that shared the same weekday name.
        $planDays = $this->chartDisplay->formatPlan(
            $dietPlan,
            $dietPlan->assessment ?? $assessment
        );

        // $consumedItems is a flat array of food_id integers ticked as consumed
        // for today, scoped to both user_id and child_id for defence-in-depth.
        // (child_id alone is sufficient for ownership because every child belongs
        // to exactly one user, but filtering by user_id too prevents any future
        // data-model change from accidentally leaking another user's logs.)
        $consumedItems = MealItemLog::where('child_id', $child->id)
            ->where('user_id', $child->user_id)
            ->where('log_date', Carbon::today()->toDateString())
            ->where('is_consumed', true)
            ->pluck('food_id')
            ->toArray();

        return [
            'child' => $child,
            'assessment' => $assessment,
            'dietPlan' => $dietPlan,
            'viewData' => compact(
                'planDays',
                'dietPlan',
                'child',
                'assessment',
                'consumedItems',
                'latestPlan',
                'todayTarget'
            ),
        ];
    }

    /**
     * A nutrient target <= 0 is "not available": return null so only that
     * nutrient's bar/ring is hidden. Positive values pass through as floats.
     */
    private function targetOrNull(float $value): ?float
    {
        return $value > 0 ? $value : null;
    }
}