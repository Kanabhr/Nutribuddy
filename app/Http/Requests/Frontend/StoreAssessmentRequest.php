<?php

namespace App\Http\Requests\Frontend;

use App\Models\AssessmentOption;
use App\Models\AssessmentQuestion;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        // Dynamically build rules for every active question.
        $questions = AssessmentQuestion::active()->pluck('id');

        $rules = [
            'answers'   => ['required', 'array'],
        ];

        foreach ($questions as $qId) {
            $rules["answers.{$qId}"] = [
                'required',
                'integer',
                // Ensure the option actually belongs to this question.
                function ($attribute, $value, $fail) use ($qId) {
                    $exists = AssessmentOption::where('id', $value)
                        ->where('question_id', $qId)
                        ->exists();
                    if (! $exists) {
                        $fail("Invalid answer selected for question {$qId}.");
                    }
                },
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'answers.required' => 'Please answer all questions before submitting.',
            'answers.array'    => 'Invalid submission format.',
        ];
    }
}
