<?php

namespace App\Models\Nutrition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChildAssessment extends Model
{
    protected $fillable = [
        'child_id',
        'model_level',
        'current_weight_kg',
        'current_height_cm',
        'initial_weight_kg',
'initial_height_cm',
        'main_goal',
        'diet_type',
        'picky_level',
        'activity_level',
        'allergies',
        'region',
        'budget',
        'current_season',
        'meal_pattern',
        'dairy_glasses',
        'cooking_medium',
        'medical_condition',
        'bmi',
        'growth_profile',
        'professional_review_required',
        
    ];

    protected $casts = [
        'allergies' => 'json',
        'current_weight_kg' => 'float',
        'current_height_cm' => 'float',
        'initial_weight_kg' => 'float',
'initial_height_cm' => 'float',
        'bmi' => 'float',
        'professional_review_required' => 'boolean',
    ];

    /**
     * Get the child this assessment belongs to.
     */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /**
     * Get all questionnaire answers for this assessment.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QuestionnaireAnswer::class, 'child_assessment_id');
    }

    /**
     * Get all clinical flags for this assessment.
     */
    public function clinicalFlags(): HasMany
    {
        return $this->hasMany(ClinicalFlag::class, 'child_assessment_id');
    }

    /**
     * Get the latest diet plan for this assessment.
     */
    public function dietPlan(): HasOne
    {
        return $this->hasOne(DietPlan::class, 'child_assessment_id')->latestOfMany();
    }

    /**
     * Get all diet plans for this assessment.
     */
    public function dietPlans(): HasMany
    {
        return $this->hasMany(DietPlan::class, 'child_assessment_id');
    }

    /**
     * Get lab reports for this assessment.
     */
    public function labReports(): HasMany
    {
        return $this->hasMany(LabReport::class, 'child_assessment_id');
    }

    /**
     * Get supplements for this assessment.
     */
    public function supplements(): HasMany
    {
        return $this->hasMany(Supplement::class, 'child_assessment_id');
    }

    /**
     * Get answer value for a specific question.
     */
    public function getAnswerForQuestion(int $questionId)
    {
        return $this->answers()
            ->where('question_id', $questionId)
            ->first()?->answer_value;
    }
}
