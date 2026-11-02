<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\Nutrition\FoodMaster;
use App\Models\Nutrition\IcmrNutritionTarget;

class DietGenerationService
{
    /**
     * Generate a deterministic diet plan based on assessment answers and constraints.
     */
    public function generate(ChildAssessment $assessment): DietPlan
    {
        // 1. Questionnaire answers & Child Profile constraints
        $allergies = $assessment->allergies ?? [];
        $dietType = $assessment->diet_type;
        $region = $assessment->region;
        $budget = $assessment->budget;

        // Age + Sex calculation from child
        $child = $assessment->child;
        $ageInYears = now()->diffInYears($child->dob);
        $sex = $child->sex;

        // 2. Fetch ICMR targets
        $targets = IcmrNutritionTarget::where('age_min', '<=', $ageInYears)
            ->where('age_max', '>=', $ageInYears)
            ->where(function ($query) use ($sex) {
                $query->where('sex', $sex)->orWhere('sex', 'all');
            })
            ->first();

        // 3. Eligible food pool with Hard Filters
        $foodsQuery = FoodMaster::query()
            ->where(function ($q) use ($ageInYears) {
                $q->whereNull('age_min')->orWhere('age_min', '<=', $ageInYears);
            })
            ->where(function ($q) use ($ageInYears) {
                $q->whereNull('age_max')->orWhere('age_max', '>=', $ageInYears);
            });

        // Apply allergy restrictions strictly
        if (in_array('dairy', $allergies)) {
            $foodsQuery->where('contains_dairy', false);
        }
        if (in_array('nuts', $allergies)) {
            $foodsQuery->where('contains_nuts', false);
        }
        if (in_array('gluten', $allergies)) {
            $foodsQuery->where('contains_gluten', false);
        }
        if (in_array('egg', $allergies) || $dietType === 'vegetarian' || $dietType === 'vegan' || $dietType === 'jain') {
            $foodsQuery->where('contains_egg', false);
        }

        // Apply Diet Type restrictions
        if ($dietType === 'vegetarian') {
            $foodsQuery->where('veg_status', true);
        } elseif ($dietType === 'vegan') {
            $foodsQuery->where('vegan', true);
        } elseif ($dietType === 'jain') {
            $foodsQuery->where('jain_allowed', true);
        }

        $eligibleFoods = $foodsQuery->get();

        // 4. Meal composition (Breakfast, Lunch, Dinner, Snacks)
        // Group eligible foods by meal_slot
        
        // 5. Create new DietPlan record
        $dietPlan = DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'version' => 1,
            'status' => 'pending_review',
        ]);

        // (TODO: Implementation details of deterministic food selection, gap analysis, and inserting DietPlanMeals/Items)

        return $dietPlan;
    }
}
