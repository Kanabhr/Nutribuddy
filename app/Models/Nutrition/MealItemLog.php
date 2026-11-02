<?php

namespace App\Models\Nutrition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealItemLog extends Model
{
    protected $fillable = [
        'child_id',
        'user_id',
        'meal_id',
        'food_id',
        'log_date',
        'is_consumed',
        'consumed_at',
    ];

    protected $casts = [
        'is_consumed' => 'boolean',
        'log_date' => 'date',
        'consumed_at' => 'datetime',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(FoodMaster::class, 'food_id');
    }
}