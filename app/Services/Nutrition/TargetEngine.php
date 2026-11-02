<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\Child;
use App\Models\Nutrition\IcmrNutritionTarget;

class TargetEngine
{
    /**
     * Fetches the specific ICMR targets based on the child's age and sex.
     */
    public function getTargets(Child $child)
    {
        $age = \Carbon\Carbon::parse($child->dob)->age;
        
        $query = IcmrNutritionTarget::where('age_min', '<=', $age)
                                    ->where('age_max', '>=', $age);
                                    
        // Try to find sex-specific target first
        $sexSpecific = (clone $query)->where('sex', $child->sex)->first();
        if ($sexSpecific) {
            return $sexSpecific;
        }
        
        // Fallback to 'all' (usually for younger kids)
        $target = $query->where('sex', 'all')->first();
        if ($target) return $target;

        return IcmrNutritionTarget::first();
    }
}
