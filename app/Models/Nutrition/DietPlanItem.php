<?php

namespace App\Models\Nutrition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietPlanItem extends Model
{
    protected $table = 'diet_plan_items';

    protected $fillable = [
        'diet_plan_meal_id',
        'food_id',
        'is_alternative',
        'portion_size',
    ];

    protected $casts = [
        'is_alternative' => 'boolean',
    ];

    /**
     * Get the meal this item belongs to.
     */
    public function meal(): BelongsTo
    {
        return $this->belongsTo(DietPlanMeal::class, 'diet_plan_meal_id');
    }

    /**
     * Get the food for this item.
     */
    public function food(): BelongsTo
    {
        return $this->belongsTo(FoodMaster::class, 'food_id');
    }

    /**
     * Scope: Get only primary items (non-alternatives)
     */
    public function scopePrimary($query)
    {
        return $query->where('is_alternative', false);
    }

    /**
     * Scope: Get only alternative items
     */
    public function scopeAlternatives($query)
    {
        return $query->where('is_alternative', true);
    }
}
