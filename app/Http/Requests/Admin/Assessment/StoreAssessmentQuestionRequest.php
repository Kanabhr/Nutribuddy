<?php

namespace App\Http\Requests\Admin\Assessment;

class StoreAssessmentQuestionRequest extends AssessmentQuestionRequest
{
    public function rules(): array
    {
        $rules = $this->questionRules();
        unset($rules['options.*.id'], $rules['options.*._delete']);

        return $rules;
    }
}
