<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentSectionScore extends Model
{
    protected $fillable = [
        'attempt_id',
        'section',
        'score',
        'max_score',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'score'      => 'integer',
            'max_score'  => 'integer',
            'percentage' => 'float',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class, 'attempt_id');
    }
}
