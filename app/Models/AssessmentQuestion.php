<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentQuestion extends Model
{
    protected $fillable = [
        'title',
        'description',
        'section',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function options(): HasMany
    {
        return $this->hasMany(AssessmentOption::class, 'question_id')
            ->orderBy('sort_order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AssessmentAnswer::class, 'question_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('section')->orderBy('sort_order');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function maxScore(): int
    {
        return (int) $this->options->max('score');
    }
}
