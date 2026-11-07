<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\FoodMaster;

/**
 * Scales a food's per-serving nutrient values to a given portion size.
 *
 * Ratio = portion_g / serving_g.
 * When serving_g is missing, zero, or the portion cannot be parsed, ratio = 1
 * (i.e. no scaling; the raw per-serving values are returned unchanged).
 *
 * All returned values are floats.
 */
class PortionScaler
{
    /**
     * Scale nutrients by portion_g / serving_g.
     *
     * @param  FoodMaster          $food      The food model (provides serving_g and the
     *                                        nutrients accessor with all keys).
     * @param  string|float|int    $portionG  Portion in grams, may be a string like "217g".
     * @return array<string, float>           All nutrient keys with scaled float values.
     */
    public static function scale(FoodMaster $food, string|float|int $portionG): array
    {
        $ratio = self::ratio($food, $portionG);

        $nutrients = $food->nutrients; // object with all nutrient keys
        $result    = [];
        foreach ((array) $nutrients as $key => $value) {
            $result[$key] = (float) $value * $ratio;
        }
        return $result;
    }

    /**
     * Compute the scaling ratio for a food and portion size.
     *
     * Returns 1.0 when serving_g is absent / zero or portionG cannot be parsed.
     */
    public static function ratio(FoodMaster $food, string|float|int $portionG): float
    {
        $servingG = (float) ($food->serving_g ?? 0);
        if ($servingG <= 0) {
            return 1.0;
        }

        // Strip trailing 'g' (or any non-numeric suffix) so "217g" parses as 217.
        $parsed = is_string($portionG)
            ? (float) preg_replace('/[^0-9.]+/', '', $portionG)
            : (float) $portionG;

        if ($parsed <= 0) {
            return 1.0;
        }

        return $parsed / $servingG;
    }
}
