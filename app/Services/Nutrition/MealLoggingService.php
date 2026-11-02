<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\MealItemLog;
use Illuminate\Support\Carbon;

/**
 * Persists parent meal logs and assessment check-ins.
 */
class MealLoggingService
{
    /**
     * Create a follow-up assessment from the latest assessment and new measurements.
     *
     * @param array{weight_kg: int|float, height_cm: int|float} $data
     */
    public function saveCheckIn(ChildAssessment $previousAssessment, array $data): ChildAssessment
    {
        $assessment = new ChildAssessment();
        $assessment->fill($previousAssessment->only([
            'child_id',
            'model_level',
            'main_goal',
            'diet_type',
            'picky_level',
            'activity_level',
            'allergies',
            'region',
            'meal_pattern',
            'dairy_glasses',
            'cooking_medium',
            'medical_condition',
            'budget',
            'current_season',
            'growth_profile',
            'professional_review_required',
        ]));

        $assessment->initial_weight_kg = $previousAssessment->initial_weight_kg ?? $previousAssessment->current_weight_kg;
        $assessment->initial_height_cm = $previousAssessment->initial_height_cm ?? $previousAssessment->current_height_cm;
        $assessment->current_weight_kg = $data['weight_kg'];
        $assessment->current_height_cm = $data['height_cm'];

        $heightM = $data['height_cm'] / 100;
        $assessment->bmi = $heightM > 0 ? round($data['weight_kg'] / ($heightM * $heightM), 2) : null;
        $assessment->save();

        return $assessment;
    }

    /**
     * Toggle a food's consumed state for the given child and date.
     *
     * @param array{food_id: int, meal_id?: int|null, log_date?: string|null} $data
     */
    public function toggleMealItem(Child $child, int $userId, array $data): MealItemLog
    {
        $logDate = $data['log_date'] ?? Carbon::today()->toDateString();
        $query = MealItemLog::query()
            ->where('child_id', $child->id)
            ->where('food_id', $data['food_id'])
            ->whereDate('log_date', $logDate);

        if (isset($data['meal_id'])) {
            $query->where('meal_id', $data['meal_id']);
        } else {
            $query->whereNull('meal_id');
        }

        $log = $query->first() ?? new MealItemLog([
            'child_id' => $child->id,
            'meal_id' => $data['meal_id'] ?? null,
            'food_id' => $data['food_id'],
            'log_date' => $logDate,
        ]);

        $log->user_id = $userId;
        $log->is_consumed = $log->exists ? !$log->is_consumed : true;
        $log->consumed_at = $log->is_consumed ? now() : null;
        $log->save();

        return $log;
    }
}
