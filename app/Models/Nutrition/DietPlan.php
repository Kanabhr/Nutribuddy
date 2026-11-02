<?php

namespace App\Models\Nutrition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietPlan extends Model
{
    protected $table = 'diet_plans';

    protected $fillable = [
        'child_assessment_id',
        'version',
        'status',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'ai_summary',
        'plan_data',
    ];

    protected $casts = [
        'plan_data' => 'json',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the assessment this plan belongs to.
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ChildAssessment::class, 'child_assessment_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    /**
     * Get all meals in this plan.
     */
    public function meals(): HasMany
    {
        return $this->hasMany(DietPlanMeal::class, 'diet_plan_id')->orderBy('sort_order');
    }

    /**
     * Get active diet plans only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get archived diet plans.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Archive this plan and set a new one as active.
     */
    public function archive(): bool
    {
        return $this->update(['status' => 'archived']);
    }
}
