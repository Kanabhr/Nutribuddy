<?php

namespace App\Services\Nutrition;

class NutritionCalculator
{
    /**
     * Sums the nutrition of a given day's meal plan based on the servings.
     */
    public function sumDailyNutrition(array $dailyMeals)
    {
        $totals = [
            'energy_kcal' => 0,
            'protein_g' => 0,
            'fat_g' => 0,
            'fiber_g' => 0,
            'calcium_mg' => 0,
            'iron_mg' => 0,
            // ... Initialize other 28 nutrients ...
        ];

        foreach ($dailyMeals as $slot => $plateItems) {
            foreach ($plateItems as $item) {
                // Assuming $item is a FoodMaster model which has a relationship to nutrients
                $nutrients = $item->nutrients;
                if ($nutrients) {
                    $totals['energy_kcal'] += $nutrients->energy_kcal;
                    $totals['protein_g'] += $nutrients->protein_g;
                    $totals['fat_g'] += $nutrients->fat_g;
                    $totals['fiber_g'] += $nutrients->fiber_g;
                    $totals['calcium_mg'] += $nutrients->calcium_mg;
                    $totals['iron_mg'] += $nutrients->iron_mg;
                    // ... add others
                }
            }
        }

        return $totals;
    }
}
