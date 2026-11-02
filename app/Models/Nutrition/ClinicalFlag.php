<?php

namespace App\Models\Nutrition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalFlag extends Model
{
    protected $table = 'clinical_flags';

    protected $fillable = [
        'child_assessment_id',
        'flag_name',
        'reason',
        'priority',
    ];

    /**
     * Get the assessment this flag belongs to.
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ChildAssessment::class, 'child_assessment_id');
    }

    /**
     * Get flags with specific priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Order by priority (high → medium → low).
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderByRaw("CASE WHEN priority = 'high' THEN 1 WHEN priority = 'medium' THEN 2 ELSE 3 END");
    }
}
