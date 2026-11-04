<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\Child;
use App\Models\Nutrition\IcmrNutritionTarget;

class TargetEngine
{
    /**
     * Fetches the specific ICMR targets based on the child's age and sex.
     *
     * Lookup order:
     *  1. Sex-specific row for the child's exact age band (narrowest band wins,
     *     via ORDER BY band width ASC then id ASC).
     *  2. 'all' row for the same age band (same tie-breaking).
     *  3. Absolute catch-all: any row, ordered by id — only reached for ages
     *     outside every seeded band (< 1 or > 18 with current data).
     */
    public function getTargets(Child $child)
    {
        $age = \Carbon\Carbon::parse($child->dob)->age;

        // Base query: rows whose band covers the child's age.
        // Narrowest band first (age_max - age_min ASC) so an exact 1-year
        // band beats a 3-year band if bands ever overlap; id breaks ties.
        $query = IcmrNutritionTarget::where('age_min', '<=', $age)
                                    ->where('age_max', '>=', $age)
                                    ->orderByRaw('(age_max - age_min) asc')
                                    ->orderBy('id');

        // 1. Sex-specific row
        $sexSpecific = (clone $query)->where('sex', $child->sex)->first();
        if ($sexSpecific) {
            return $sexSpecific;
        }

        // 2. 'all' row for the same band (clone so the base query is untouched)
        $target = (clone $query)->where('sex', 'all')->first();
        if ($target) {
            return $target;
        }

        // 3. Catch-all: age is outside every seeded band — return closest row
        //    (cheapest safety net; avoids a null return crashing callers).
        return IcmrNutritionTarget::orderBy('id')->first();
    }
}
