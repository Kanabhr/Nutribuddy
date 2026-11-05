<?php

namespace App\Services\Nutrition;

use App\Models\DietSubscription;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\QuestionnaireAnswer;
use App\Models\Nutrition\QuestionnaireQuestion;
use App\Models\User;
use App\Support\PlanAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QuestionnaireSubmissionService
{
    /** @param array<string, mixed> $validated */
    public function submit(User $user, array $validated): array
    {
        $result = DB::transaction(function () use ($user, $validated): array {
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

            // Rule 3: carry all answers from the previous assessment forward so the
            // engine sees the full picture (e.g. Basic answers when upgrading to Plus).
            foreach ($previousAssessment?->answers ?? [] as $answer) {
                QuestionnaireAnswer::create([
                    'child_assessment_id' => $assessment->id,
                    'question_id' => $answer->question_id,
                    'answer_value' => $answer->answer_value,
                ]);
            }

            foreach ($answers as $questionId => $value) {
                // Skip null, empty-string, and empty-array answers so the NOT NULL
                // constraint on answer_value is never violated for optional questions.
                // Any previous answer copied forward from an earlier assessment is kept.
                if ($value === null || $value === '' || $value === []) {
                    continue;
                }

                QuestionnaireAnswer::updateOrCreate(
                    ['child_assessment_id' => $assessment->id, 'question_id' => $questionId],
                    ['answer_value' => $value],
                );
            }

            return ['child_id' => $child->id, 'assessment_id' => $assessment->id];
        });

        // Rule 4: after a successful submit, generate the chart if the child's active
        // subscription now covers the plan answered.  This runs OUTSIDE the transaction
        // so the new assessment row is fully committed and visible.
        $this->maybeGenerateChartAfterSubmit($user, $result['child_id'], $validated['plan_slug'] ?? 'basic');

        return $result;
    }

    /**
     * If the child holds an active paid subscription at or below the just-submitted
     * plan level, and no chart exists yet for the new assessment, generate it now.
     */
    private function maybeGenerateChartAfterSubmit(User $user, int $childId, string $planSlug): void
    {
        // Only triggered for paid plans (plus / pro / premium).
        if (PlanAccess::rank($planSlug) <= PlanAccess::rank('basic')) {
            return;
        }

        $subscription = DietSubscription::query()
            ->where('user_id', $user->id)
            ->where('child_id', $childId)
            ->usable()
            ->with('subscriptionPlan')
            ->get()
            ->sortByDesc(fn ($s) => PlanAccess::rank($s->subscriptionPlan?->slug))
            ->first();

        if (! $subscription) {
            return;
        }

        // PostPaymentChartService is idempotent — safe to call even if already done.
        app(PostPaymentChartService::class)->generateIfReady($subscription);
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
