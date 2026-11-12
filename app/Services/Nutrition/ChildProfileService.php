<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\QuestionnaireQuestion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChildProfileService
{
    public function createProfile(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            $child = Child::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'dob' => now()->subYears($data['age']),
                'sex' => $data['sex'] === 'other' ? 'boy' : $data['sex'],
            ]);

            $assessment = ChildAssessment::create([
                'child_id' => $child->id,
                'model_level' => 'champion',
                'current_weight_kg' => (float) $data['weight'],
                'current_height_cm' => (float) $data['height'],
                'diet_type' => $data['diet'],
                'picky_level' => $data['picky'],
                'activity_level' => $data['activity'] ?? 'Moderately active',
                'allergies' => ($data['allergies'] ?? '') ?: null,
                'region' => $data['region'],
                'current_season' => $this->currentIndianSeason(),
            ]);

            try {
                $answers = [
                    1 => $data['age'], 2 => $data['sex'], 3 => $data['weight'],
                    4 => $data['height'], 5 => $data['diet'], 6 => $data['allergies'] ?? '',
                    7 => $data['region'], 8 => $data['picky'],
                ];
                app(DietChartGeneratorService::class)->generateDietChart($assessment, $answers);
            } catch (\Throwable $exception) {
                Log::warning('Diet chart generation failed during child profile creation.', [
                    'child_id' => $child->id,
                    'exception' => $exception,
                ]);
            }

            return compact('child', 'assessment');
        });
    }

    public function normalize(ChildAssessment $assessment, array $answers = []): array
    {
        $answers = $this->byQuestionOrder($answers);
        $child = $assessment->child;
        $weight = (float) ($answers[4] ?? $assessment->current_weight_kg ?? 0);
        $height = $this->heightInCentimeters($answers[5] ?? $assessment->current_height_cm) ?? 0;
        $bmi = $height > 0 ? round($weight / (($height / 100) ** 2), 2) : null;
        $medicalCondition = $this->text($answers[12] ?? null);
        $visibleSigns = $this->visibleSigns($answers[26] ?? []);
        $profile = [
            'age_years' => $child?->dob ? Carbon::parse($child->dob)->age : null,
            'age_months' => $child?->dob ? Carbon::parse($child->dob)->diffInMonths(now()) : null,
            'gender' => strtolower((string) ($answers[3] ?? $child?->sex ?? '')),
            'weight_kg' => $weight ?: null,
            'height_cm' => $height ?: null,
            'bmi' => $bmi,
            'diet_type' => $this->dietType($answers[6] ?? $assessment->diet_type),
            'allergies' => $this->allergies($answers[7] ?? $assessment->allergies),
            'region' => $this->region($answers[8] ?? $assessment->region),
            'meal_pattern' => $this->mealPattern($answers[9] ?? $assessment->meal_pattern),
            'dairy_glasses' => $this->dairyGlasses($answers[10] ?? $assessment->dairy_glasses),
            'cooking_medium' => $this->text($answers[11] ?? $assessment->cooking_medium),
            'medical_condition' => $medicalCondition,
            'visible_signs' => $visibleSigns,
            // No growth reference table exists yet, so no medical threshold is inferred.
            'growth_profile' => 'unknown',
            'professional_review_required' => $this->requiresReview($medicalCondition),
            // Answer 17: favourite / must-have foods (free text, comma/slash-separated).
            // Used only as a ranking boost — never a guarantee.
            'favourite_foods' => $this->foodList($answers[17] ?? null),
            // Answer 18: disliked / refused foods (free text, comma/slash-separated).
            // Used as a soft exclusion with fallback when a slot would be emptied.
            'disliked_foods'  => $this->foodList($answers[18] ?? null),
        ];

        $assessment->fill([
            'current_weight_kg' => $profile['weight_kg'], 'current_height_cm' => $profile['height_cm'],
            'diet_type' => $profile['diet_type'], 'allergies' => $profile['allergies'], 'region' => $profile['region'],
            'meal_pattern' => $profile['meal_pattern'], 'dairy_glasses' => $profile['dairy_glasses'],
            'cooking_medium' => $profile['cooking_medium'], 'medical_condition' => $profile['medical_condition'],
            'bmi' => $profile['bmi'], 'growth_profile' => $profile['growth_profile'],
            'professional_review_required' => $profile['professional_review_required'],
        ])->save();

        return $profile;
    }

    public function heightInCentimeters(mixed $value): ?float
    {
        if (is_numeric($value)) {
            $height = (float) $value;

            return $height > 0 ? $height : null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/\(\s*(\d+(?:\.\d+)?)\s*cm\s*\)/i', $value, $matches)
            || preg_match('/^\s*(\d+(?:\.\d+)?)\s*cm\s*$/i', $value, $matches)) {
            $height = (float) $matches[1];

            return $height > 0 ? $height : null;
        }

        if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*ft\s*(\d+(?:\.\d+)?)?\s*(?:in|inches?)?\s*$/i', $value, $matches)) {
            $feet = (float) $matches[1];
            $inches = isset($matches[2]) ? (float) $matches[2] : 0;
            $height = ($feet * 30.48) + ($inches * 2.54);

            return $height > 0 ? $height : null;
        }

        return null;
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

    private function byQuestionOrder(array $answers): array
    {
        $ids = array_filter(array_keys($answers), 'is_numeric');
        if ($ids === []) {
            return $answers;
        }
        $orders = QuestionnaireQuestion::whereIn('id', $ids)->pluck('sort_order', 'id');
        foreach ($orders as $id => $order) {
            $answers[$order] = $answers[$id];
        }

        return $answers;
    }

    private function dietType(mixed $value): string
    {
        return match (strtolower($this->text($value))) {
            'veg', 'vegetarian' => 'vegetarian', 'vegan' => 'vegan', 'non-veg', 'non veg', 'non-vegetarian' => 'non-vegetarian',
            'eggetarian', 'eggitarian' => 'eggetarian', 'jain' => 'jain', default => 'vegetarian',
        };
    }

    private function allergies(mixed $value): array
    {
        $values = is_array($value) ? $value : preg_split('/[,|]/', (string) $value);

        return collect($values)->map(fn ($item) => strtolower(trim((string) $item)))->map(function ($item) {
            // IMPORTANT: sesame, soy, and honey must be checked BEFORE the nuts/seeds
            // arm so that strings like "Sesame seeds" or "Soya" match their specific
            // token rather than falling through to 'nuts'.  The questionnaire option
            // "Nuts & seeds" does not contain 'sesame', 'soy', or 'honey', so it still
            // maps to 'nuts' correctly.
            return match (true) {
                str_contains($item, 'lactose')                             => 'lactose',
                str_contains($item, 'dairy')                               => 'dairy',
                str_contains($item, 'gluten') || str_contains($item, 'wheat') => 'gluten',
                str_contains($item, 'egg')                                 => 'egg',
                str_contains($item, 'sesame')                              => 'sesame',
                str_contains($item, 'soy')                                 => 'soy',
                str_contains($item, 'honey')                               => 'honey',
                str_contains($item, 'nut') || str_contains($item, 'seed') => 'nuts',
                default                                                    => null,
            };
        })->filter()->unique()->values()->all();
    }

    private function region(mixed $value): string
    {
        $values = is_array($value) ? $value : preg_split('/[,|]/', (string) $value);
        $regions = collect($values)->map(function ($region) {
            return match (strtolower(trim((string) $region))) {
                'north', 'north india' => 'North',
                'west', 'west india' => 'West',
                'east', 'east india' => 'East',
                'south', 'south india' => 'South',
                'no preference', 'pan india', 'none' => 'No preference',
                default => null,
            };
        })->filter()->unique()->values();

        if ($regions->isEmpty() || $regions->contains('No preference')) {
            return 'No preference';
        }

        return $regions->implode(', ');
    }

    private function mealPattern(mixed $value): string
    {
        return match (strtolower($this->text($value))) {
            '3 meals' => '3 meals', '2 + snacks', '2+snacks' => '2 + snacks', default => '3 meals + 2 snacks',
        };
    }

    private function dairyGlasses(mixed $value): int
    {
        return min(3, max(0, (int) preg_replace('/\D.*/', '', (string) $this->text($value))));
    }

    private function requiresReview(?string $condition): bool
    {
        return $condition !== null && ! in_array(strtolower($condition), ['none', 'no', 'n/a', 'nil'], true);
    }

    /** @return list<string> */
    private function visibleSigns(mixed $value): array
    {
        $signs = is_array($value) ? $value : preg_split('/[,|]/', (string) $value);

        return collect($signs)
            ->map(fn ($sign) => trim((string) $sign))
            ->reject(fn ($sign) => $sign === '' || \App\Services\Nutrition\VisibleSignsMap::isNoFlag($sign))
            ->unique()
            ->values()
            ->all();
    }

    private function text(mixed $value): ?string
    {
        $value = is_array($value) ? implode(', ', $value) : trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Parse a free-text food list (answer 17 or 18).
     *
     * Splits on commas, slashes, or pipes; trims whitespace; lowercases each token.
     * Drops:
     *   - empty strings
     *   - terms under 3 characters (too ambiguous: "no", "na", "ok" …)
     *   - explicit no-op words: none, no, nil, nothing, n/a, na
     *
     * Returns an empty array when the value is absent, blank, or entirely
     * composed of ignored terms (e.g. the answer "None").
     *
     * @return list<string>
     */
    public function foodList(mixed $value): array
    {
        if ($value === null || $value === '' || (is_array($value) && $value === [])) {
            return [];
        }

        $raw = is_array($value) ? implode(',', $value) : (string) $value;

        $ignored = ['none', 'no', 'nil', 'nothing', 'n/a', 'na'];

        return collect(preg_split('/[,\/|]+/', $raw))
            ->map(fn ($item) => strtolower(trim($item)))
            ->filter(fn ($item) => $item !== '')
            ->filter(fn ($item) => strlen($item) >= 3)
            ->filter(fn ($item) => ! in_array($item, $ignored, true))
            ->unique()
            ->values()
            ->all();
    }
}
