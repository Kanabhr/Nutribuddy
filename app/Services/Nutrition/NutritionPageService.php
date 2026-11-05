<?php

namespace App\Services\Nutrition;

use App\Models\DietSubscription;
use App\Models\Nutrition\Child;
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
            return compact('questionnairePending', 'questionnaireUrl');
        }

        if (! $assessment) {
            return null;
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

            return compact('reviewPending');
        }

        $sevenDayPlan = $this->chartDisplay->formatSevenDayPlan(
            $dietPlan,
            $dietPlan->assessment ?? $assessment
        );
        $daysMap = [
            'Monday' => 'mon', 'Tuesday' => 'tue', 'Wednesday' => 'wed',
            'Thursday' => 'thu', 'Friday' => 'fri', 'Saturday' => 'sat', 'Sunday' => 'sun',
        ];
        $focusList = ['IMMUNITY BOOST', 'GROWTH & BONES', 'BRAIN POWER', 'ENERGY DAY', 'BONE STRENGTH', 'REST & RESTORE'];
        $jsDays = [];
        $dayIndex = 0;

        foreach ($sevenDayPlan as $dayName => $slots) {
            if (! isset($daysMap[$dayName])) {
                continue;
            }

            $meals = [];
            foreach ($slots as $slotName => $data) {
                $mealItems = array_column($data['items'], 'food_name');
                if (! $mealItems) {
                    continue;
                }

                $meals[] = [
                    'time' => $slotName,
                    'emoji' => 'ðŸ²',
                    'name' => implode(' + ', $mealItems),
                    'desc' => $data['focus'] ?? 'Healthy and nutritious meal.',
                    'tags' => ['Nutrition:protein'],
                ];
            }

            $jsDays[$daysMap[$dayName]] = [
                'label' => $dayName,
                'focus' => $focusList[$dayIndex % count($focusList)],
                'meals' => $meals,
            ];
            $dayIndex++;
        }

        return $jsDays ? compact('jsDays') : null;
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
        $todayTarget = ['energy_kcal' => 2100, 'protein_g' => 152, 'fat_g' => 61];

        $viewData = compact('child', 'assessment', 'latestPlan', 'todayTarget');
        if (! $dietPlan) {
            return ['child' => $child, 'assessment' => $assessment, 'dietPlan' => null, 'viewData' => $viewData];
        }

        $sevenDayPlan = $this->chartDisplay->formatSevenDayPlan(
            $dietPlan,
            $dietPlan->assessment ?? $assessment
        );
        $planData = $dietPlan->plan_data;
        if (is_string($planData)) {
            $planData = json_decode($planData, true) ?? [];
        }
        $nutrientGaps = is_array($planData) ? ($planData['nutrient_gaps'] ?? []) : [];

        $dailyTargets = [];
        foreach ($nutrientGaps as $day => $gaps) {
            $dailyTargets[$day] = [
                'energy_kcal' => $gaps['energy_kcal']['target'] ?? 1360,
                'protein_g' => $gaps['protein_g']['target'] ?? 16,
                'fat_g' => $gaps['fat_g']['target'] ?? 40,
            ];
        }

        $todayTarget = $dailyTargets[Carbon::now()->format('l')]
            ?? $dailyTargets['Monday']
            ?? ['energy_kcal' => 2100, 'protein_g' => 152, 'fat_g' => 61];
        $consumedItems = MealItemLog::where('child_id', $child->id)
            ->where('log_date', Carbon::today()->toDateString())
            ->where('is_consumed', true)
            ->pluck('food_id')
            ->toArray();

        return [
            'child' => $child,
            'assessment' => $assessment,
            'dietPlan' => $dietPlan,
            'viewData' => compact(
                'sevenDayPlan',
                'dietPlan',
                'child',
                'assessment',
                'consumedItems',
                'latestPlan',
                'todayTarget',
                'dailyTargets'
            ),
        ];
    }
}
