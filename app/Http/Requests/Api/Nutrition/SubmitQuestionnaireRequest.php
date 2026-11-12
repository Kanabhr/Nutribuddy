<?php

namespace App\Http\Requests\Api\Nutrition;

use App\Models\Nutrition\QuestionnaireQuestion;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

// NOTE: prepareForValidation coerces single-string answers into arrays for every
// multi-answer question (identified by answer_type containing 'multi' or sort_order = 8).
// This allows browsers that submit a single checkbox tick as a plain string to pass
// the ['array', 'min:1'] rule without a client-side fix.

class SubmitQuestionnaireRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $input = $this->all();

        // Step 1 – normalise legacy "q_N[]" browser keys to "q_N".
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

        // Step 2 – for every question whose answer_type contains 'multi' (case-insensitive)
        // or whose sort_order is 8 (the Allergies question), a plain string posted from a
        // browser that ticked exactly one checkbox must become a single-element array so the
        // ['array', 'min:1'] rule does not reject it.  An empty string collapses to [].
        $questionIds = collect(array_keys($input))
            ->map(fn ($k) => preg_match('/^q_([1-9]\d*)$/', (string) $k, $m) ? (int) $m[1] : null)
            ->filter()
            ->values()
            ->all();

        if ($questionIds !== []) {
            $multiIds = QuestionnaireQuestion::whereIn('id', $questionIds)
                ->where(function ($query): void {
                    $query->whereRaw('LOWER(answer_type) LIKE ?', ['%multi%'])
                        ->orWhere('sort_order', 8);
                })
                ->pluck('id')
                ->all();

            foreach ($multiIds as $id) {
                $key = 'q_'.$id;
                if (array_key_exists($key, $input) && is_string($input[$key])) {
                    $input[$key] = $input[$key] === '' ? [] : [$input[$key]];
                }
            }
        }

        // Step 3 – Q32 "Periods started?" must only be stored for girls.
        // If the child's gender (sort_order 3, Q3) is "boy" (case-insensitive),
        // drop any submitted Q32 answer so it is never persisted.
        // We determine gender from the submitted answer to the question at
        // sort_order 3; if that answer is not in this submission we cannot
        // tell gender here (carry-over path), so we leave Q32 alone and rely
        // on the server-side strip in QuestionnaireSubmissionService.
        $genderQuestion = QuestionnaireQuestion::where('sort_order', 3)->first();
        $periodsQuestion = QuestionnaireQuestion::where('sort_order', 32)->first();

        if ($genderQuestion && $periodsQuestion) {
            $genderKey  = 'q_'.$genderQuestion->id;
            $periodsKey = 'q_'.$periodsQuestion->id;

                   if (array_key_exists($genderKey, $input)) {
                $genderVal = strtolower(trim((string) (is_array($input[$genderKey]) ? ($input[$genderKey][0] ?? '') : $input[$genderKey])));
            } elseif (! empty($input['child_id']) && is_numeric($input['child_id'])) {
                // Upgrade form: the Sex question is not on the page, so use the saved child.
                // Only the signed-in parent's own children are looked up.
                $genderVal = strtolower(trim((string) $this->user()?->children()->whereKey((int) $input['child_id'])->value('sex')));
            } else {
                $genderVal = '';
            }

            if ($genderVal === 'boy' && array_key_exists($periodsKey, $input)) {
                unset($input[$periodsKey]);
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

        // Q31 blood report — optional file upload, validated by real MIME type.
        // MAX SIZE: 5 MB placeholder — unverified, owner to confirm.
        $periodsQuestion = QuestionnaireQuestion::where('sort_order', 32)->first();
        $bloodReportQuestion = QuestionnaireQuestion::where('sort_order', 31)->first();

        if ($bloodReportQuestion) {
            $bloodKey = 'q_'.$bloodReportQuestion->id;
            if ($this->hasFile($bloodKey)) {
                $rules[$bloodKey] = [
                    'nullable',
                    'file',
                    'max:'.(int) (\App\Services\Nutrition\VisibleSignsMap::BLOOD_REPORT_MAX_BYTES / 1024),
                    'mimes:pdf,jpg,jpeg,png,docx',
                ];
            } else {
                $rules[$bloodKey] = ['nullable'];
            }
        }

        // Q32 periods — not required; already stripped for boys in prepareForValidation.
        if ($periodsQuestion) {
            $rules['q_'.$periodsQuestion->id] = ['nullable', 'string', 'max:255'];
        }

        foreach ($this->all() as $key => $value) {
            if (preg_match('/^q_[1-9]\d*$/', (string) $key)) {
                // Don't overwrite blood report or periods rules already set above.
                if (isset($rules[$key])) {
                    continue;
                }
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

                // Height (sort_order 5, "Current height") and weight (sort_order 4,
                // "Current weight") are "always asked" questions that use custom
                // widgets on the front-end.  They are REQUIRED when the question is
                // marked always-asked (is_optional = false, matching the real seeder)
                // and nullable when explicitly optional (is_optional = true).  The
                // previous code special-cased them as always-nullable, which is why a
                // missing height silently passed validation and was never saved.
                //
                // Sanity limits (stated here in code):
                //  - Height: parsed via ChildProfileService::heightInCentimeters();
                //    must resolve to 30–250 cm (covers newborns ~46 cm through the
                //    tallest adults ~251 cm).
                //  - Weight: numeric, 0.5–300 kg (covers infants through extreme
                //    obesity; 0 is not a positive number).
                if (str_contains($label, 'height')) {
                    $presence = $question->is_optional ? 'nullable' : 'required';
                    $rules[$key] = [$presence, function (string $attribute, mixed $value, \Closure $fail): void {
                        // 'required'/'nullable' already handle presence — only validate
                        // format and range when a value is actually present.
                        if ($value === null || $value === '') {
                            return;
                        }
                        if ((! is_string($value) && ! is_numeric($value)) || strlen((string) $value) > 80) {
                            $fail('Enter height in cm or ft/in.');
                            return;
                        }
                        $cm = app(\App\Services\Nutrition\ChildProfileService::class)->heightInCentimeters($value);
                        if ($cm === null) {
                            $fail('Enter height in cm or ft/in.');
                        } elseif ($cm < 30 || $cm > 250) {
                            $fail('Height must be between 30 and 250 cm.');
                        }
                    }];
                    continue;
                }
                if (str_contains($label, 'weight')) {
                    $presence = $question->is_optional ? 'nullable' : 'required';
                    $rules[$key] = [$presence, 'numeric', 'min:0.5', 'max:300'];
                    continue;
                }

                if ($question->is_optional
                    || (str_contains(strtolower((string) $question->only_ask_if), 'vegan') && $isVegan)) {
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
