<?php

namespace App\Models\Nutrition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeficiencyRule extends Model
{
    protected $table = 'deficiency_rules';

    protected $fillable = [
        'sign',
        'likely_gap',
        'confirmation_test',
        'smart_combination',
        'common_group',
    ];

    /**
     * Get all deficiency foods for this rule.
     */
    public function foods(): HasMany
    {
        return $this->hasMany(DeficiencyFood::class, 'rule_id');
    }

    /**
     * Find rules by visible signs.
     */
    public static function findBySigns(array $signs)
    {
        return self::whereIn('sign', $signs)->get();
    }
}
