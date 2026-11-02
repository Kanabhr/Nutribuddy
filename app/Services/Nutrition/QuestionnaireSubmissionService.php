<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\QuestionnaireAnswer;
use App\Models\Nutrition\QuestionnaireQuestion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QuestionnaireSubmissionService
{
    /** @param array<string, mixed> $validated */
    public function submit(User $user, array $validated): array
    {
        return DB::transaction(function () use ($user, $validated): array {
            $child = isset($validated['child_id'])
                ? $user->children()->findOrFail($validated['child_id'])
                : null;

            $answers = collect($validated)
                ->filter(fn ($value, $key) => preg_match('/^q_[1-9]\d*$/', (string) $key))
                ->mapWithKeys(fn ($value, $key) => [(int) substr($key, 2) => $value]);
            $sortOrders = QuestionnaireQuestion::query()
                ->whereIn('id', $answers->keys())
                ->pluck('sort_order', 'id');
            $answersByOrder = $answers->mapWithKeys(fn ($value, $questionId) => [
                $sortOrders[$questionId] ?? $questionId => $value,
            ]);

            $name = $answersByOrder->get(1) ?? $child?->name ?? 'Child';
            $dobInput = $answersByOrder->get(2);
            $dob = is_string($dobInput) && strlen($dobInput) > 5 && strtotime($dobInput) !== false
                ? Carbon::parse($dobInput)
                : null;
            $sexInput = $answersByOrder->get(3, 'boy');
            $sex = strtolower((string) (is_array($sexInput) ? ($sexInput[0] ?? 'boy') : $sexInput));
            $sex = in_array($sex, ['boy', 'girl'], true) ? $sex : 'boy';

            $child ??= Child::create([
                'user_id' => $user->id,
                'name' => $name,
                'dob' => $dob,
                'sex' => $sex,
            ]);

            $previousAssessment = $child->assessments()->with('answers')->latest()->first();
            $assessment = ChildAssessment::create([
                'child_id' => $child->id,
                'model_level' => $validated['plan_slug'] ?? 'basic',
                'current_weight_kg' => (float) ($answersByOrder->get(4) ?? 0) ?: null,
                'current_height_cm' => app(ChildProfileService::class)->heightInCentimeters($answersByOrder->get(5)),
                'diet_type' => $this->answerText($answersByOrder->get(6), 'vegetarian'),
                'allergies' => $this->answerText($answersByOrder->get(7), 'None'),
                'region' => $this->answerText($answersByOrder->get(8), 'Pan India'),
                'activity_level' => $this->answerText($answersByOrder->get(13)),
                'main_goal' => $this->answerText($answersByOrder->get(22)),
                'budget' => $this->answerText($answersByOrder->get(35)),
                'picky_level' => $this->answerText($answersByOrder->get(16)),
                'current_season' => $this->currentIndianSeason(),
            ]);

            foreach ($previousAssessment?->answers ?? [] as $answer) {
                QuestionnaireAnswer::create([
                    'child_assessment_id' => $assessment->id,
                    'question_id' => $answer->question_id,
                    'answer_value' => $answer->answer_value,
                ]);
            }

            foreach ($answers as $questionId => $value) {
                QuestionnaireAnswer::updateOrCreate(
                    ['child_assessment_id' => $assessment->id, 'question_id' => $questionId],
                    ['answer_value' => $value],
                );
            }

            return ['child_id' => $child->id, 'assessment_id' => $assessment->id];
        });
    }

    private function answerText(mixed $value, ?string $default = null): ?string
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return is_array($value) ? implode(', ', $value) : (string) $value;
    }

    private function currentIndianSeason(): string
    {
        return match (now()->month) {
            3, 4 => 'Vasant (Spring)',
            5, 6 => 'Greeshma (Summer)',
            7, 8, 9 => 'Varsha (Monsoon)',
            10, 11 => 'Sharad (Autumn)',
            12, 1 => 'Hemant (Pre-winter)',
            2 => 'Shishir (Winter)',
            default => 'Year-round',
        };
    }
}
