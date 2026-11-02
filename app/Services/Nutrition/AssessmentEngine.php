<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\QuestionnaireQuestion;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;

class AssessmentEngine
{
    /**
     * Determines which questions to show based on the model level and child's profile.
     */
    public function getQuestionsForChild(Child $child, ChildAssessment $assessment)
    {
        $level = $assessment->model_level; // basic, plus, pro, premium
        
        $levelsToInclude = $this->getCumulativeLevels($level);
        
        $questions = QuestionnaireQuestion::whereIn('model', $levelsToInclude)
            ->orderBy('sort_order')
            ->get();
            
        return $questions->filter(function ($question) use ($child, $assessment) {
            return $this->evaluateShowIf($question->show_if, $child, $assessment);
        });
    }

    private function getCumulativeLevels($level)
    {
        $hierarchy = ['basic', 'plus', 'pro', 'premium'];
        $index = array_search($level, $hierarchy);
        if ($index === false) return ['basic'];
        
        return array_slice($hierarchy, 0, $index + 1);
    }

    private function evaluateShowIf($showIfJson, Child $child, ChildAssessment $assessment)
    {
        if (empty($showIfJson)) return true;
        
        $rules = is_array($showIfJson) ? $showIfJson : json_decode($showIfJson, true);
        if (!$rules) return true;
        
        $age = \Carbon\Carbon::parse($child->dob)->age;
        
        foreach ($rules as $key => $condition) {
            if ($key === 'age') {
                if (str_starts_with($condition, '<=') && !($age <= (int)substr($condition, 2))) return false;
                elseif (str_starts_with($condition, '>=') && !($age >= (int)substr($condition, 2))) return false;
                elseif (str_starts_with($condition, '<') && !($age < (int)substr($condition, 1))) return false;
                elseif (str_starts_with($condition, '>') && !($age > (int)substr($condition, 1))) return false;
                elseif (is_numeric($condition) && $age != $condition) return false;
            }
            if ($key === 'sex') {
                if ($child->sex !== strtolower($condition)) return false;
            }
        }
        
        return true;
    }
}
