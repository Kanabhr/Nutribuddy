<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentAnswer extends Model
{
    protected $fillable = [
        'attempt_id',
        'question_id',
        'option_id',
        'obtained_score',
    ];

    protected function casts(): array
    {
        return [
            'obtained_score' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(AssessmentQuestion::class, 'question_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(AssessmentOption::class, 'option_id');
    }
}
