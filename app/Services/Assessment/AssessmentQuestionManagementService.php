<?php

namespace App\Services\Assessment;

use App\Models\AssessmentQuestion;
use Illuminate\Support\Facades\DB;

/** Persists assessment questions and their answer options as one unit. */
class AssessmentQuestionManagementService
{
    /** @param array<string, mixed> $data */
    public function create(array $data): AssessmentQuestion
    {
        return DB::transaction(function () use ($data): AssessmentQuestion {
            $question = AssessmentQuestion::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'section' => trim($data['section']),
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => true,
            ]);

            foreach ($data['options'] as $index => $option) {
                $question->options()->create([
                    'option_text' => $option['option_text'],
                    'score' => (int) $option['score'],
                    'sort_order' => $option['sort_order'] ?? $index,
                ]);
            }

            return $question;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(AssessmentQuestion $question, array $data): void
    {
        DB::transaction(function () use ($question, $data): void {
            $question = AssessmentQuestion::query()->whereKey($question->id)->lockForUpdate()->firstOrFail();
            $question->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'section' => trim($data['section']),
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            foreach ($data['options'] as $index => $optionData) {
                if (! empty($optionData['_delete'])) {
                    if (! empty($optionData['id'])) {
                        $question->options()->whereKey($optionData['id'])->delete();
                    }
                    continue;
                }

                $option = ! empty($optionData['id'])
                    ? $question->options()->whereKey($optionData['id'])->first()
                    : null;
                $attributes = [
                    'option_text' => $optionData['option_text'],
                    'score' => (int) $optionData['score'],
                    'sort_order' => $optionData['sort_order'] ?? $index,
                ];

                if ($option) {
                    $option->update($attributes);
                } elseif (empty($optionData['id'])) {
                    $question->options()->create($attributes);
                }
            }
        });
    }

    public function delete(AssessmentQuestion $question): void
    {
        $question->delete();
    }

    public function toggleActive(AssessmentQuestion $question): bool
    {
        return DB::transaction(function () use ($question): bool {
            $question = AssessmentQuestion::query()->whereKey($question->id)->lockForUpdate()->firstOrFail();
            $question->update(['is_active' => ! $question->is_active]);

            return $question->is_active;
        });
    }

    /** @param array<int, int> $orderedQuestionIds */
    public function reorder(array $orderedQuestionIds): void
    {
        DB::transaction(function () use ($orderedQuestionIds): void {
            foreach ($orderedQuestionIds as $position => $id) {
                AssessmentQuestion::whereKey($id)->update(['sort_order' => $position]);
            }
        });
    }
}
