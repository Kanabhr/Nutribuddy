<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\DietPlan;
use App\Models\NutritionLead;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Handles the nutritionist review queue's chart preparation and state changes.
 */
class NutritionLeadReviewService
{
    public function __construct(
        private readonly FoodFilterPipeline $foodFilters,
        private readonly DietChartGeneratorService $chartGenerator,
        private readonly DietLifecycleNotificationService $notifications,
    ) {}

    /** @return array{lead: NutritionLead, assessment: ?\App\Models\Nutrition\ChildAssessment, dietPlan: ?DietPlan, foodsByMeal: \Illuminate\Support\Collection, mealsByDay: \Illuminate\Support\Collection, products: \Illuminate\Support\Collection} */
    public function buildReviewData(NutritionLead $lead): array
    {
        $lead->load(['customer', 'child', 'subscription.subscriptionPlan']);
        $assessment = $lead->child?->assessments()
            ->with(['answers.question', 'clinicalFlags'])
            ->latest('created_at')
            ->first();
        $dietPlan = $assessment?->dietPlans()
            ->with(['meals.items.food'])
            ->latest('created_at')
            ->first();
        $foodsByMeal = collect();
        $mealsByDay = collect();

        if ($dietPlan) {
            $assessment = $dietPlan->assessment;
            $eligibleFoods = $this->foodFilters->getEligibleFoods($assessment);
            $foodsByMeal = $dietPlan->meals->mapWithKeys(
                fn ($meal) => [$meal->id => $this->foodFilters->forSlot($eligibleFoods, $meal->meal_slot)]
            );
            $slotsPerDay = match ($assessment->meal_pattern) {
                '3 meals' => 3,
                '2 + snacks' => 4,
                default => 5,
            };
            $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $mealsByDay = $dietPlan->meals->sortBy('sort_order')->groupBy(
                fn ($meal) => $dayNames[intdiv($meal->sort_order, $slotsPerDay)] ?? 'Day ' . (intdiv($meal->sort_order, $slotsPerDay) + 1)
            );
        }

        $profile = $dietPlan?->plan_data['child_profile'] ?? [];
        $allergies = $profile['allergies'] ?? $assessment?->allergies ?? [];
        $allergies = is_array($allergies) ? $allergies : [$allergies];
        $clinicalFlags = $assessment?->clinicalFlags ?? collect();

        return [
            'lead' => $lead,
            'assessment' => $assessment,
            'dietPlan' => $dietPlan,
            'foodsByMeal' => $foodsByMeal,
            'mealsByDay' => $mealsByDay,
            'dietType' => $profile['diet_type'] ?? $assessment?->diet_type ?? 'Not provided',
            'allergies' => $allergies,
            'mealPattern' => $profile['meal_pattern'] ?? $assessment?->meal_pattern ?? 'Not provided',
            'cookingMedium' => $profile['cooking_medium'] ?? $assessment?->cooking_medium ?? 'Not provided',
            'clinicalFlags' => $clinicalFlags,
            'hasUnavailableMeals' => $foodsByMeal->contains(fn ($eligibleFoods) => $eligibleFoods->isEmpty()),
            'products' => $dietPlan
                ? Product::where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
        ];
    }

    /** @return array<string, mixed> */
    public function generateChart(NutritionLead $lead): array
    {
        $assessment = $lead->child?->assessments()->latest()->first();
        abort_unless($assessment, 422, 'The questionnaire has not been completed.');
        abort_if($assessment->dietPlans()->exists(), 422, 'A chart already exists for this assessment.');

        $answers = $assessment->answers()
            ->pluck('answer_value', 'question_id')
            ->map(fn ($value) => json_decode($value, true))
            ->all();

        return $this->chartGenerator->generateDietChart($assessment, $answers);
    }

    /**
     * Update meal choices only when every selected food is safe for its slot.
     *
     * @param array{foods: array<int, int>, supplement_product_ids?: array<int, int>} $data
     */
    public function updateDietPlan(DietPlan $dietPlan, array $data): bool
    {
        $eligibleFoods = $this->foodFilters->getEligibleFoods($dietPlan->assessment);
        $meals = $dietPlan->meals;
        $selectedFoods = [];

        foreach ($meals as $meal) {
            $foodId = $data['foods'][$meal->id] ?? null;
            if (! $foodId || ! $this->foodFilters->forSlot($eligibleFoods, $meal->meal_slot)->contains('id', $foodId)) {
                return false;
            }
            $selectedFoods[$meal->id] = $foodId;
        }

        DB::transaction(function () use ($dietPlan, $data, $meals, $selectedFoods): void {
            foreach ($meals as $meal) {
                $meal->items()->update(['food_id' => $selectedFoods[$meal->id]]);
            }

            $planData = $dietPlan->plan_data ?? [];
            if (array_key_exists('supplement_product_ids', $data)) {
                $planData['supplement_product_ids'] = $data['supplement_product_ids'] ?? [];
            }

            $dietPlan->update([
                ...$this->pendingReviewAttributes(),
                'plan_data' => $planData,
            ]);
        });

        return true;
    }

    /** @param array{supplement_product_ids?: array<int, int>} $data */
    public function updateRecommendedProducts(DietPlan $dietPlan, array $data): void
    {
        DB::transaction(function () use ($dietPlan, $data): void {
            $dietPlan->update([
                ...$this->pendingReviewAttributes(),
                'plan_data' => array_merge($dietPlan->plan_data ?? [], [
                    'supplement_product_ids' => $data['supplement_product_ids'] ?? [],
                ]),
            ]);
        });
    }

    /** @param array{review_notes?: string|null} $data */
    public function approve(NutritionLead $lead, DietPlan $dietPlan, int $adminId, array $data): void
    {
        DB::transaction(function () use ($lead, $dietPlan, $adminId, $data): void {
            $dietPlan->update([
                'status' => 'active',
                'review_status' => 'approved',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'review_notes' => $data['review_notes'] ?? null,
            ]);
            $lead->update(['status' => 'completed', 'completed_at' => now()]);
        });

        $this->notifications->chartApproved($lead, $dietPlan);
    }

    /** @return array{status:string,review_status:string,reviewed_by:null,reviewed_at:null,review_notes:null} */
    private function pendingReviewAttributes(): array
    {
        return [
            'status' => 'pending_review',
            'review_status' => 'pending_review',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_notes' => null,
        ];
    }
}
