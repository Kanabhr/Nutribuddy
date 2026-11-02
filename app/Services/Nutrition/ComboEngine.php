<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\NutritionTip;

class ComboEngine
{
    /**
     * Retrieves specific food combination tips based on child's goals/flags.
     */
    public function getCombos($goals, $flags)
    {
        // Example: $goals = 'Brain & Focus';
        // Example flag: 'Iron Risk';

        $tips = NutritionTip::where('benefit', $goals)
            ->orWhereIn('benefit', $flags)
            ->get();
            
        return $tips;
    }
}
