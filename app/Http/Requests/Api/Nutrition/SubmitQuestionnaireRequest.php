<?php

namespace App\Http\Requests\Api\Nutrition;

use App\Models\Nutrition\QuestionnaireQuestion;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class SubmitQuestionnaireRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $input = $this->all();

        foreach (array_keys($input) as $key) {
            if (! preg_match('/^q_([1-9]\d*)\[\]$/', (string) $key, $matches)) {
                continue;
            }

            $normalizedKey = 'q_'.$matches[1];

            // Keep collisions invalid so two differently named values cannot silently overwrite each other.
            if (! array_key_exists($normalizedKey, $input)) {
                $input[$normalizedKey] = $input[$key];
                unset($input[$key]);
            }
        }

        $this->replace($input);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = [
            'child_id' => ['nullable', 'integer', 'exists:children,id'],
            'plan_slug' => ['nullable', 'string', 'max:255'],
            'cycle' => ['nullable', 'string', 'in:monthly,quarterly,annually'],
        ];

        foreach ($this->all() as $key => $value) {
            if (preg_match('/^q_[1-9]\d*$/', (string) $key)) {
                $rules[$key] = ['nullable', function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_scalar($value) && ! is_array($value)) {
                        $fail('The answer must be a scalar value or a list of values.');
                    }

                    if (is_array($value) && collect($value)->contains(fn ($item) => ! is_scalar($item) && $item !== null)) {
                        $fail('The answer list may contain only scalar values.');
                    }
                }];
            }
        }

        if ($this->input('plan_slug') === 'basic') {
            $questions = $this->basicQuestions();
            $dietQuestion = $questions->firstWhere('sort_order', 6);
            $isVegan = strtolower((string) $this->input('q_'.$dietQuestion?->id)) === 'vegan';

            foreach ($questions as $question) {
                $key = 'q_'.$question->id;
                $label = strtolower((string) $question->question);
                $type = strtolower((string) $question->answer_type);
                $isRegionQuestion = str_contains($label, 'region') && str_contains($label, 'cuisine');
                $options = preg_split('/\s+\/\s+/', (string) $question->options_scale, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $options = array_values(array_filter(array_map('trim', $options), fn ($option) => $option !== '—'));

                if ($question->is_optional
                    || str_contains($label, 'height')
                    || str_contains($label, 'weight')
                    || (str_contains(strtolower((string) $question->only_ask_if), 'vegan') && $isVegan)) {
                    if (str_contains($label, 'height')) {
                        $rules[$key] = ['nullable', function (string $attribute, mixed $value, \Closure $fail): void {
                            if ($value !== null && $value !== '' && ((! is_string($value) && ! is_numeric($value))
                                || strlen((string) $value) > 80
                                || app(\App\Services\Nutrition\ChildProfileService::class)->heightInCentimeters($value) === null)) {
                                $fail('Enter height in cm or ft/in.');
                            }
                        }];
                    } elseif (str_contains($label, 'weight')) {
                        $rules[$key] = ['nullable', 'numeric', 'min:0', 'max:300'];
                    }

                    continue;
                }

                if (str_contains($type, 'multi') || $isRegionQuestion) {
                    $rules[$key] = ['required', 'array', 'min:1'];
                    $rules[$key.'.*'] = ['required', 'string', Rule::in($options)];
                } elseif (str_contains($type, 'choice')) {
                    $rules[$key] = ['required', 'string', Rule::in($options)];
                } elseif (str_contains($type, 'date')) {
                    $rules[$key] = ['required', 'date', 'before_or_equal:today'];
                } elseif (str_contains($type, 'number')) {
                    $rules[$key] = ['required', 'numeric', 'min:0', 'max:300'];
                } else {
                    $rules[$key] = ['required', 'string', 'max:1000'];
                }
            }
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (array_keys($this->all()) as $key) {
                if (preg_match('/^q_[1-9]\d*\[\]$/', (string) $key)) {
                    $validator->errors()->add($key, 'Submit each answer using its question key and an array value.');
                }
            }

            $questionIds = collect(array_keys($this->all()))
                ->map(fn ($key) => preg_match('/^q_([1-9]\d*)$/', (string) $key, $matches) ? (int) $matches[1] : null)
                ->filter()
                ->unique();

            $existingIds = QuestionnaireQuestion::whereIn('id', $questionIds)->pluck('id')->all();

            foreach ($questionIds->diff($existingIds) as $missingId) {
                $validator->errors()->add("q_{$missingId}", 'The selected question does not exist.');
            }

            if ($this->input('plan_slug') === 'basic') {
                $basicQuestions = $this->basicQuestions();
                foreach ($questionIds->diff($basicQuestions->pluck('id')) as $unexpectedId) {
                    $validator->errors()->add("q_{$unexpectedId}", 'Only Basic questionnaire answers are accepted here.');
                }

                foreach ($basicQuestions as $question) {
                    $label = strtolower((string) $question->question);
                    $answer = $this->input('q_'.$question->id);
                    if (str_contains($label, 'region') && str_contains($label, 'cuisine')
                        && is_array($answer)
                        && count($answer) > 1
                        && in_array('No preference', $answer, true)) {
                        $validator->errors()->add('q_'.$question->id, 'No preference cannot be combined with a region.');
                    }
                }
            }
        });
    }

    private ?Collection $basicQuestionCache = null;

    private function basicQuestions(): Collection
    {
        return $this->basicQuestionCache ??= QuestionnaireQuestion::query()
            ->where('model', '1 Basic')
            ->orderBy('sort_order')
            ->get(['id', 'question', 'answer_type', 'options_scale', 'only_ask_if', 'is_optional', 'sort_order']);
    }
}
