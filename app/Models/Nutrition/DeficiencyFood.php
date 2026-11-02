<?php

namespace App\Models\Nutrition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeficiencyFood extends Model
{
    protected $table = 'deficiency_foods';

    protected $fillable = [
        'rule_id',
        'food_id',
    ];

    /**
     * Get the deficiency rule.
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(DeficiencyRule::class, 'rule_id');
    }

    /**
     * Get the food.
     */
    public function food(): BelongsTo
    {
        return $this->belongsTo(FoodMaster::class, 'food_id');
    }
}
