<?php

namespace App\Http\Requests\Admin\Assessment;

class UpdateAssessmentQuestionRequest extends AssessmentQuestionRequest
{
    public function rules(): array
    {
        return $this->questionRules(true);
    }
}
