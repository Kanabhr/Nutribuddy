<?php

namespace App\Http\Requests\Admin\Assessment;

use Illuminate\Foundation\Http\FormRequest;

class ReorderAssessmentQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage assessment') ?? false;
    }

    public function rules(): array
    {
        return ['order' => ['required', 'array'], 'order.*' => ['integer']];
    }
}
