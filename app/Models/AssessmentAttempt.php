<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'total_score',
        'max_score',
        'percentage',
        'result_level',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_score'  => 'integer',
            'max_score'    => 'integer',
            'percentage'   => 'float',
            'completed_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AssessmentAnswer::class, 'attempt_id');
    }

    public function sectionScores(): HasMany
    {
        return $this->hasMany(AssessmentSectionScore::class, 'attempt_id');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Emoji + text label for result level.
     */
    public function resultLevelEmoji(): string
    {
        return match ($this->result_level) {
            'Excellent'         => '🌟',
            'Good'              => '👍',
            'Average'           => '⚠️',
            default             => '🔴',
        };
    }

    /**
     * CSS colour class for the result level badge.
     */
    public function resultLevelClass(): string
    {
        return match ($this->result_level) {
            'Excellent' => 'level-excellent',
            'Good'      => 'level-good',
            'Average'   => 'level-average',
            default     => 'level-poor',
        };
    }
}
