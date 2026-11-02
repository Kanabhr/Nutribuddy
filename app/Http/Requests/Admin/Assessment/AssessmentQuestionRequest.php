<?php

namespace App\Http\Requests\Admin\Assessment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class AssessmentQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage assessment') ?? false;
    }

    protected function questionRules(bool $updating = false): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'section' => ['required', Rule::in(array_keys(\App\Services\AssessmentService::SECTORS))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.id' => [$updating ? 'nullable' : 'sometimes', 'integer'],
            'options.*.option_text' => ['required', 'string', 'max:255'],
            'options.*.score' => ['required', 'integer', 'min:0', 'max:99'],
            'options.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'options.*._delete' => [$updating ? 'nullable' : 'sometimes', 'boolean'],
        ];
    }
}
