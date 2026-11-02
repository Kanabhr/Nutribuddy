<?php

namespace App\Models\Nutrition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodNutrient extends Model
{
    protected $table = 'food_nutrients';

    protected $fillable = [
        'food_id',
        'energy_kcal',
        'protein_g',
        'fat_g',
        'fiber_g',
        'calcium_mg',
        'iron_mg',
        'zinc_mg',
        'magnesium_mg',
        'vitamin_a_mcg',
        'vitamin_c_mg',
        'vitamin_d_mcg',
        'thiamin_b1_mg',
        'riboflavin_b2_mg',
        'niacin_b3_mg',
        'vitamin_b6_mg',
        'folate_mcg',
        'vitamin_b12_mcg',
        'potassium_mg',
        'sodium_mg',
    ];

    protected $casts = [
        'food_id' => 'integer',
        'energy_kcal' => 'float',
        'protein_g' => 'float',
        'fat_g' => 'float',
        'fiber_g' => 'float',
        'calcium_mg' => 'float',
        'iron_mg' => 'float',
        'zinc_mg' => 'float',
        'magnesium_mg' => 'float',
        'vitamin_a_mcg' => 'float',
        'vitamin_c_mg' => 'float',
        'vitamin_d_mcg' => 'float',
        'thiamin_b1_mg' => 'float',
        'riboflavin_b2_mg' => 'float',
        'niacin_b3_mg' => 'float',
        'vitamin_b6_mg' => 'float',
        'folate_mcg' => 'float',
        'vitamin_b12_mcg' => 'float',
        'potassium_mg' => 'float',
        'sodium_mg' => 'float',
    ];

    /**
     * Get the food this nutrient data belongs to.
     */
    public function food(): BelongsTo
    {
        return $this->belongsTo(FoodMaster::class, 'food_id');
    }

    /**
     * Get the sum of a specific nutrient across multiple foods.
     */
    public static function sumNutrient(array $foodIds, string $nutrient): float
    {
        return self::whereIn('food_id', $foodIds)
            ->sum($nutrient) ?? 0;
    }
}
