<?php

namespace App\Services;

use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentSectionScore;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssessmentService
{
    public const SECTORS = [
        'Growth & Physical Development' => 20,
        'Immunity & Resilience' => 20,
        'Brain & Cognitive Development' => 15,
        'Gut & Digestive Health' => 15,
        'Bones, Muscles & Strength' => 15,
        'Energy, Sleep & Lifestyle' => 15,
    ];

    // ─── Question Loading ─────────────────────────────────────────────────────

    /**
     * Return all active questions with their options, grouped by section.
     *
     * @return Collection<string, Collection<AssessmentQuestion>>
     */
    public function getActiveQuestionsGrouped(?int $limit = null): Collection
    {
        $query = AssessmentQuestion::active()
            ->ordered()
            ->with(['options' => fn ($q) => $q->orderBy('sort_order')]);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()
            ->groupBy('section')
            ->sortBy(function ($group, $section) {
                return array_search($section, array_keys(self::SECTORS));
            });
    }

    /**
     * Return a flat list of all active questions with options.
     *
     * @return Collection<AssessmentQuestion>
     */
    public function getActiveQuestions(?int $limit = null): Collection
    {
        $query = AssessmentQuestion::active()
            ->ordered()
            ->with(['options' => fn ($q) => $q->orderBy('sort_order')]);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    // ─── Score Calculation & Storage ──────────────────────────────────────────

    /**
     * Calculate scores from submitted answers and persist the attempt.
     *
     * @param  array  $answers  [ question_id => option_id, … ]
     */
    public function calculateAndStore(User $user, array $answers): AssessmentAttempt
    {
        // Eager-load questions + options for the answered question IDs.
        $questions = AssessmentQuestion::active()
            ->with(['options'])
            ->whereIn('id', array_keys($answers))
            ->get()
            ->keyBy('id');

        $totalScore = 0;
        $maxScore = 0;
        $sectionData = []; // section => ['score' => 0, 'max' => 0]

        $answersToInsert = [];

        foreach ($answers as $questionId => $optionId) {
            $question = $questions->get($questionId);
            if (! $question) {
                continue;
            }

            $option = $question->options->firstWhere('id', $optionId);
            if (! $option) {
                continue;
            }

            $obtained = (int) $option->score;
            $maxForQ = (int) $question->options->max('score');
            $section = $question->section;

            $totalScore += $obtained;
            $maxScore += $maxForQ;

            $sectionData[$section]['score'] = ($sectionData[$section]['score'] ?? 0) + $obtained;
            $sectionData[$section]['max'] = ($sectionData[$section]['max'] ?? 0) + $maxForQ;

            $answersToInsert[] = [
                'question_id' => $questionId,
                'option_id' => $optionId,
                'obtained_score' => $obtained,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $weightedPercentage = 0;
        foreach ($sectionData as $section => $data) {
            $sectionPct = $data['max'] > 0 ? ($data['score'] / $data['max']) * 100 : 0;
            $weight = self::SECTORS[$section] ?? 0;
            $weightedPercentage += ($sectionPct * $weight / 100);
        }
        $percentage = round($weightedPercentage, 2);

        $resultLevel = $this->getResultLevel($percentage);

        return DB::transaction(function () use ($user, $totalScore, $maxScore, $percentage, $resultLevel, $answersToInsert, $sectionData): AssessmentAttempt {
            $attempt = AssessmentAttempt::create([
                'user_id' => $user->id,
                'total_score' => $totalScore,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'result_level' => $resultLevel,
                'completed_at' => now(),
            ]);

            foreach ($answersToInsert as &$row) {
                $row['attempt_id'] = $attempt->id;
            }
            AssessmentAnswer::insert($answersToInsert);

            $sectionRows = [];
            foreach ($sectionData as $section => $data) {
                $sectionPct = $data['max'] > 0 ? round(($data['score'] / $data['max']) * 100, 2) : 0;
                $sectionRows[] = [
                    'attempt_id' => $attempt->id,
                    'section' => $section,
                    'score' => $data['score'],
                    'max_score' => $data['max'],
                    'percentage' => $sectionPct,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            AssessmentSectionScore::insert($sectionRows);

            return $attempt->fresh(['sectionScores', 'answers']);
        });
    }

    // ─── Result Level ─────────────────────────────────────────────────────────

    public function getResultLevel(float $percentage): string
    {
        return match (true) {
            $percentage >= 80 => 'Excellent',
            $percentage >= 60 => 'Good',
            $percentage >= 40 => 'Average',
            default => 'Needs Improvement',
        };
    }

    // ─── Comparison ───────────────────────────────────────────────────────────

    /**
     * Build a comparison array between two attempts.
     *
     * @return array{overall: array, sections: array}
     */
    public function compareAttempts(AssessmentAttempt $latest, AssessmentAttempt $previous): array
    {
        $overall = [
            'previous_score' => $previous->total_score,
            'current_score' => $latest->total_score,
            'previous_percentage' => $previous->percentage,
            'current_percentage' => $latest->percentage,
            'diff_score' => $latest->total_score - $previous->total_score,
            'diff_percentage' => round($latest->percentage - $previous->percentage, 2),
        ];

        // Build a map of section name => data for each attempt.
        $prevMap = $previous->sectionScores->keyBy('section');
        $latestMap = $latest->sectionScores->keyBy('section');
        $sections = $latestMap->keys()->merge($prevMap->keys())->unique();

        $sectionComparisons = [];
        foreach ($sections as $section) {
            $prevSec = $prevMap->get($section);
            $latSec = $latestMap->get($section);
            $sectionComparisons[$section] = [
                'previous_score' => $prevSec?->score ?? 0,
                'current_score' => $latSec?->score ?? 0,
                'previous_percentage' => $prevSec?->percentage ?? 0,
                'current_percentage' => $latSec?->percentage ?? 0,
                'max_score' => $latSec?->max_score ?? ($prevSec?->max_score ?? 0),
                'diff' => ($latSec?->score ?? 0) - ($prevSec?->score ?? 0),
            ];
        }

        return [
            'overall' => $overall,
            'sections' => $sectionComparisons,
        ];
    }
}
