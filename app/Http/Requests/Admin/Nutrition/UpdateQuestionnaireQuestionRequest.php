<?php

namespace App\Http\Requests\Admin\Nutrition;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionnaireQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage diet chart') ?? false;
    }

    public function rules(): array
    {
        return [
            'section' => ['nullable', 'string'],
            'question' => ['required', 'string'],
            'answer_type' => ['required', 'string'],
            'options_scale' => ['nullable', 'string'],
            'only_ask_if' => ['nullable', 'string'],
            'clinical_reason' => ['nullable', 'string'],
            'chart_effect' => ['nullable', 'string'],
            'is_optional' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer'],
        ];
    }
}
