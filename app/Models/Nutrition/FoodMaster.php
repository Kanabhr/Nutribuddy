<?php

namespace App\Models\Nutrition;

use Illuminate\Database\Eloquent\Model;

class FoodMaster extends Model
{
    protected $table = 'foods';

    protected $casts = [
        'is_veg'                      => 'boolean',
        'is_vegan'                    => 'boolean',
        'is_jain_ok'                  => 'boolean',
        'contains_egg'                => 'boolean',
        'contains_dairy'              => 'boolean',
        'contains_nuts'               => 'boolean',
        'contains_gluten'             => 'boolean',
        'ingredient_list_per_serving' => 'json',
        'method'                      => 'json',
        'goal_tags'                   => 'json',
    ];

    // ---------------------------------------------------------------
    // Accessors — bridge old service names ↔ real column names
    // ---------------------------------------------------------------

    /** Used by generator: $food->food_name */
    public function getFoodNameAttribute()
    {
        return $this->dish_name;
    }

    /** Used by generator: $food->meal_slot */
    public function getMealSlotAttribute()
    {
        return $this->slot;
    }

    /** Used by generator: $food->regional_name */
    public function getRegionalNameAttribute()
    {
        return $this->local_name;
    }

    /**
     * getNutrientsAttribute — returns a simple object with ALL expected
     * nutrient keys mapped to the real column names in `foods` table.
     *
     * Uses new column names (carb_g, fibre_total_g, sfa_g, mufa_g, pufa_g,
     * free_sugar_g, vit_c_mg, vit_d_ug, b1_mg..b12_ug) AND keeps backwards-
     * compatible aliases (fiber_g, vitamin_c_mg, vitamin_d_mcg, thiamin_b1_mg, etc.)
     * so the generator and other services continue to work.
     */
    public function getNutrientsAttribute()
    {
        return (object) [
            // ==================== Macros ====================
            'energy_kcal'    => (float) ($this->attributes['energy_kcal']   ?? 0),
            'protein_g'      => (float) ($this->attributes['protein_g']     ?? 0),
            'fat_g'          => (float) ($this->attributes['fat_g']         ?? 0),
            'carb_g'         => (float) ($this->attributes['carb_g']        ?? 0),

            // ==================== Fat breakdown ====================
            'sfa_g'          => (float) ($this->attributes['sfa_g']         ?? 0),
            'mufa_g'         => (float) ($this->attributes['mufa_g']        ?? 0),
            'pufa_g'         => (float) ($this->attributes['pufa_g']        ?? 0),

            // ==================== Fiber & Sugar ====================
            'fibre_total_g'  => (float) ($this->attributes['fibre_total_g'] ?? 0),
            'fibre_sol_g'    => (float) ($this->attributes['fibre_sol_g']   ?? 0),
            'free_sugar_g'   => (float) ($this->attributes['free_sugar_g']  ?? 0),

            // Purana alias (backwards-compatible)
            'fiber_g'        => (float) ($this->attributes['fibre_total_g'] ?? 0),

            // ==================== Minerals ====================
            'calcium_mg'     => (float) ($this->attributes['calcium_mg']    ?? 0),
            'iron_mg'        => (float) ($this->attributes['iron_mg']       ?? 0),
            'zinc_mg'        => (float) ($this->attributes['zinc_mg']       ?? 0),
            'magnesium_mg'   => (float) ($this->attributes['magnesium_mg']  ?? 0),
            'phosphorus_mg'  => (float) ($this->attributes['phosphorus_mg'] ?? 0),
            'potassium_mg'   => (float) ($this->attributes['potassium_mg']  ?? 0),
            'sodium_mg'      => (float) ($this->attributes['sodium_mg']     ?? 0),
            'selenium_ug'    => (float) ($this->attributes['selenium_ug']   ?? 0),

            // ==================== Vitamins ====================
            'vitamin_a_mcg'  => (float) ($this->attributes['vit_a_rae_ug']  ?? 0),
            'vit_c_mg'       => (float) ($this->attributes['vit_c_mg']      ?? 0),
            'vit_d_ug'       => (float) ($this->attributes['vit_d_ug']      ?? 0),
            'vit_e_mg'       => (float) ($this->attributes['vit_e_mg']      ?? 0),

            // Purane aliases (backwards-compatible)
            'vitamin_c_mg'   => (float) ($this->attributes['vit_c_mg']      ?? 0),
            'vitamin_d_mcg'  => (float) ($this->attributes['vit_d_ug']      ?? 0),

            // ==================== B-vitamins ====================
            'b1_mg'          => (float) ($this->attributes['b1_mg']         ?? 0),
            'b2_mg'          => (float) ($this->attributes['b2_mg']         ?? 0),
            'b3_mg'          => (float) ($this->attributes['b3_mg']         ?? 0),
            'b6_mg'          => (float) ($this->attributes['b6_mg']         ?? 0),
            'folate_ug'      => (float) ($this->attributes['folate_ug']     ?? 0),
            'b12_ug'         => (float) ($this->attributes['b12_ug']        ?? 0),

            // Purane aliases (backwards-compatible)
            'thiamin_b1_mg'      => (float) ($this->attributes['b1_mg']     ?? 0),
            'riboflavin_b2_mg'   => (float) ($this->attributes['b2_mg']     ?? 0),
            'niacin_b3_mg'       => (float) ($this->attributes['b3_mg']     ?? 0),
            'vitamin_b6_mg'      => (float) ($this->attributes['b6_mg']     ?? 0),
            'folate_mcg'         => (float) ($this->attributes['folate_ug'] ?? 0),
            'vitamin_b12_mcg'    => (float) ($this->attributes['b12_ug']    ?? 0),
        ];
    }

    // ---------------------------------------------------------------
    // Scopes (unchanged)
    // ---------------------------------------------------------------

    public function scopeForAge($query, int $age)
    {
        return $query; // new schema has no age_min/age_max
    }

    public function scopeForSlot($query, string $slot)
    {
        return $query->where('slot', $slot);
    }

    public function scopeForDietType($query, string $dietType)
    {
        return match ($dietType) {
            'vegan'           => $query->where('is_vegan', true),
            'jain'            => $query->where('is_jain_ok', true),
            'vegetarian'      => $query->where('is_veg', true)->where('contains_egg', false),
            'non-vegetarian'  => $query,
            default           => $query,
        };
    }

    public function scopeWithoutAllergens($query, array $allergens)
    {
        foreach ($allergens as $allergen) {
            match (trim(strtolower($allergen))) {
                'dairy'  => $query->where('contains_dairy', false),
                'nuts'   => $query->where('contains_nuts', false),
                'gluten' => $query->where('contains_gluten', false),
                'egg'    => $query->where('contains_egg', false),
                default  => null,
            };
        }
        return $query;
    }

    public function scopeForRegion($query, string $region)
    {
        return $query->where(function ($q) use ($region) {
            $q->where('region', $region)->orWhere('region', 'Pan-India');
        });
    }

    public function scopeInCostBand($query, string $band)
    {
        $validBands = match ($band) {
            'economy'  => ['economy'],
            'moderate' => ['economy', 'moderate'],
            'flexible' => ['economy', 'moderate', 'flexible'],
            default    => ['economy', 'moderate', 'flexible'],
        };
        return $query->whereIn('cost_band', $validBands);
    }

    public function scopeWithGoalTag($query, string $tag)
    {
        return $query->where(function ($q) use ($tag) {
            $q->where('best_fit_goal', $tag)
              ->orWhere('goal_tags', 'LIKE', '%' . $tag . '%');
        });
    }

    public function scopeActive($query)
    {
        return $query; // new schema has no active column — all rows are active
    }

    public function scopeForSeason($query, string $season)
    {
        return $query; // new schema has no season column
    }
}