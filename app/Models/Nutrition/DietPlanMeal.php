<?php

namespace App\Models\Nutrition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietPlanMeal extends Model
{
    protected $table = 'diet_plan_meals';

    protected $fillable = [
        'diet_plan_id',
        'meal_slot',
        'timing',
        'focus',
        'sort_order',
    ];

    /**
     * Get the diet plan this meal belongs to.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(DietPlan::class, 'diet_plan_id');
    }

    /**
     * Get all items in this meal.
     */
    public function items(): HasMany
    {
        return $this->hasMany(DietPlanItem::class, 'diet_plan_meal_id');
    }

    /**
     * Scope: Get meals by slot type
     */
    public function scopeBySlot($query, string $slot)
    {
        return $query->where('meal_slot', $slot);
    }

    /**
     * Scope: Order by meal time (breakfast → lunch → snack → dinner)
     */
    public function scopeOrderByMealTime($query)
    {
        $mealOrder = ['Breakfast' => 1, 'Lunch' => 2, 'Snack' => 3, 'Dinner' => 4];
        return $query->orderByRaw("FIELD(meal_slot, '" . implode("','", array_keys($mealOrder)) . "')");
    }
}
