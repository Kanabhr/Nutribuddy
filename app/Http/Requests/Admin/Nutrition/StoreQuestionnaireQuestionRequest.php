<?php

namespace App\Http\Requests\Admin\Nutrition;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionnaireQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage diet chart') ?? false;
    }

    public function rules(): array
    {
        return [
            'model' => ['required', 'string'], 'section' => ['nullable', 'string'],
            'question' => ['required', 'string'], 'answer_type' => ['required', 'string'],
            'options_scale' => ['nullable', 'string'], 'clinical_reason' => ['nullable', 'string'],
            'chart_effect' => ['nullable', 'string'], 'only_ask_if' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer'],
        ];
    }
}
