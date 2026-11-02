<?php

namespace App\Models\Nutrition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireAnswer extends Model
{
    protected $table = 'questionnaire_answers';

    protected $fillable = [
        'child_assessment_id',
        'question_id',
        'answer_value',
    ];

    protected $casts = [
        'answer_value' => 'json',
    ];

    /**
     * Get the assessment this answer belongs to.
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ChildAssessment::class, 'child_assessment_id');
    }

    /**
     * Get the question this answer responds to.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireQuestion::class, 'question_id');
    }

    /**
     * Get the decoded answer value.
     */
    public function getDecodedValue()
    {
        return json_decode($this->answer_value, true);
    }
}
