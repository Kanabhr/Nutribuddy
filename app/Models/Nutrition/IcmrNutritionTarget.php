<?php

namespace App\Models\Nutrition;

use Illuminate\Database\Eloquent\Model;

class IcmrNutritionTarget extends Model
{
    protected $table = 'icmr_nutrition_targets';

    protected $fillable = [
        'age_min',
        'age_max',
        'sex',
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
    ];

    protected $casts = [
        'age_min' => 'integer',
        'age_max' => 'integer',
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
    ];

    /**
     * Find targets for a specific age.
     */
    public static function forAge(int $age, string $sex = null)
    {
        $query = self::where('age_min', '<=', $age)
            ->where('age_max', '>=', $age);

        if ($sex) {
            // Try sex-specific first
            $sexSpecific = (clone $query)->where('sex', $sex)->first();
            if ($sexSpecific) {
                return $sexSpecific;
            }

            // Fallback to 'all'
            return $query->where('sex', 'all')->first();
        }

        return $query->first();
    }

    /**
     * Get all nutrients as array.
     */
    public function toNutrientArray(): array
    {
        return [
            'energy_kcal' => $this->energy_kcal,
            'protein_g' => $this->protein_g,
            'fat_g' => $this->fat_g,
            'fiber_g' => $this->fiber_g,
            'calcium_mg' => $this->calcium_mg,
            'iron_mg' => $this->iron_mg,
            'zinc_mg' => $this->zinc_mg,
            'magnesium_mg' => $this->magnesium_mg,
            'vitamin_a_mcg' => $this->vitamin_a_mcg,
            'vitamin_c_mg' => $this->vitamin_c_mg,
            'vitamin_d_mcg' => $this->vitamin_d_mcg,
            'thiamin_b1_mg' => $this->thiamin_b1_mg,
            'riboflavin_b2_mg' => $this->riboflavin_b2_mg,
            'niacin_b3_mg' => $this->niacin_b3_mg,
            'vitamin_b6_mg' => $this->vitamin_b6_mg,
            'folate_mcg' => $this->folate_mcg,
            'vitamin_b12_mcg' => $this->vitamin_b12_mcg,
        ];
    }
}
