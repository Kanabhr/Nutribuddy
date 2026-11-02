<?php

namespace App\Services;

use App\Models\Food;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\Nutrition\DietPlanDay;
use App\Models\Nutrition\DietPlanMeal;
use Carbon\Carbon;

class DietChartGeneratorService
{
    /**
     * Main entry point to generate a 7-day diet chart.
     */
    public function generate7DayPlan($assessmentId)
    {
        // 1. Fetch the Assessment
        $assessment = ChildAssessment::with('answers')->find($assessmentId);
        
        if (!$assessment) {
            throw new \Exception("Assessment not found.");
        }

        // 2. Extract Constraints & Goals
        $constraints = $this->extractConstraints($assessment);
        $caloricTarget = $this->calculateDailyCalories($assessment);

        // 3. Generate the Plan Schedule
        $planSchedule = $this->build7DaySchedule($constraints, $caloricTarget);

        // 4. Save to Database
        return $this->saveDietPlan($assessment, $planSchedule);
    }

    /**
     * Phase 1: Analyze Answers for Constraints (Allergies, Region, Veg/Non-Veg)
     */
    private function extractConstraints(ChildAssessment $assessment): array
    {
        // TODO: In a real implementation, map specific question IDs to these variables.
        // We will default to a standard profile for now based on your DB schema.
        
        $region = 'North'; // Default fallback, should check User Profile or Assessment
        $dietType = 'Veg'; // Veg / Non-Veg / Jain / Vegan

        // Allergies
        $allergies = [
            'dairy'  => false,
            'gluten' => false,
            'nuts'   => false,
            'egg'    => false,
            'soy'    => false,
        ];

        return [
            'region'    => $region,
            'dietType'  => $dietType,
            'allergies' => $allergies
        ];
    }

    /**
     * Phase 1.5: Calculate Caloric Targets (Using Age/Weight formulas)
     */
    private function calculateDailyCalories(ChildAssessment $assessment): int
    {
        // Example: Base formula (Can be updated with precise pediatric formulas like Schofield)
        // If age is 5 and weight is 18kg, caloric need might be around 1400 kcal.
        
        $age = $assessment->age_years ?? 5; // fallback
        $weight = $assessment->weight_kg ?? 18; // fallback
        
        // Basic placeholder formula
        $dailyCalories = 1000 + ($age * 100); 
        
        return $dailyCalories;
    }

    /**
     * Phase 2 & 3: Filter the master Food table and pick meals for 7 days
     */
    private function build7DaySchedule(array $constraints, int $dailyCaloriesTarget): array
    {
        $schedule = [];
        
        // Setup base query applying hard constraints
        $baseQuery = Food::query();
        
        if ($constraints['dietType'] === 'Veg') {
            $baseQuery->where('is_veg', 'Yes');
        } elseif ($constraints['dietType'] === 'Vegan') {
            $baseQuery->where('is_vegan', 'Yes');
        }

        if ($constraints['allergies']['dairy']) {
            $baseQuery->where('contains_dairy', 'No');
        }
        if ($constraints['allergies']['nuts']) {
            $baseQuery->where('contains_nuts', 'No');
        }

        // Fetch pools
        $breakfasts = (clone $baseQuery)->where('slot', 'Breakfast')->inRandomOrder()->get();
        $lunches    = (clone $baseQuery)->where('slot', 'Lunch')->inRandomOrder()->get();
        $snacks     = (clone $baseQuery)->where('slot', 'Snack')->orWhere('slot', 'Evening Snack')->inRandomOrder()->get();
        $dinners    = (clone $baseQuery)->where('slot', 'Dinner')->inRandomOrder()->get();

        // 7-Day Loop
        for ($day = 1; $day <= 7; $day++) {
            // Picking 1 of each (modulo ensures we don't run out of index if pool is small)
            $selectedBreakfast = $breakfasts[($day - 1) % max(1, $breakfasts->count())] ?? null;
            $selectedLunch     = $lunches[($day - 1) % max(1, $lunches->count())] ?? null;
            $selectedSnack     = $snacks[($day - 1) % max(1, $snacks->count())] ?? null;
            $selectedDinner    = $dinners[($day - 1) % max(1, $dinners->count())] ?? null;

            $schedule[$day] = [
                'Breakfast'     => $selectedBreakfast,
                'Lunch'         => $selectedLunch,
                'Evening Snack' => $selectedSnack,
                'Dinner'        => $selectedDinner
            ];
        }

        return $schedule;
    }

    /**
     * Phase 4: Save the generated schedule to the Database
     */
    private function saveDietPlan(ChildAssessment $assessment, array $schedule)
    {
        // 1. Create DietPlan
        // $plan = DietPlan::create([...]);
        
        // 2. Create DietPlanDays and DietPlanMeals linking to $dish_id
        
        // Returning the raw array for now to verify logic
        return $schedule;
    }
}
