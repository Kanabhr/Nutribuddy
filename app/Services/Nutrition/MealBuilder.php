<?php

namespace App\Services\Nutrition;

class MealBuilder
{
    /**
     * Constructs a balanced meal plate based on meal slot and food groups.
     */
    public function buildPlate($eligibleFoods, $slotName)
    {
        // Filter pool to just this slot
        $slotFoods = $eligibleFoods->filter(function ($food) use ($slotName) {
            return strtolower($food->meal_slot) === strtolower($slotName);
        });

        if ($slotFoods->isEmpty()) {
            return [];
        }

        // Extremely simplified balanced plate logic
        // Ideally: Pick 1 base (Cereal) + 1 Protein (Pulse/Dairy) + 1 Micro (Fruit/Veg)
        $base = $slotFoods->whereIn('food_group', ['Cereal', 'Grain'])->random(1)->first();
        $protein = $slotFoods->whereIn('food_group', ['Pulse', 'Dairy', 'Egg', 'Meat'])->random(1)->first();
        $side = $slotFoods->whereIn('food_group', ['Fruit', 'Vegetable'])->random(1)->first();

        return array_filter([$base, $protein, $side]);
    }
}
