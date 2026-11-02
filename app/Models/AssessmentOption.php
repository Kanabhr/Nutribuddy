<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentOption extends Model
{
    protected $fillable = [
        'question_id',
        'option_text',
        'score',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'score'      => 'integer',
            'sort_order' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function question(): BelongsTo
    {
        return $this->belongsTo(AssessmentQuestion::class, 'question_id');
    }
}
