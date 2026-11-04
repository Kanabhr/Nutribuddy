<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\NutritionTip;
use Illuminate\Database\Eloquent\Collection;

/**
 * ComboEngine
 *
 * Retrieves nutrition_tips rows relevant to a child's goals and/or
 * risk flags, filtered by the child's age and sex.
 *
 * ──────────────────────────────────────────────────────────────────
 * Goal → Tip-category mapping  (NEEDS NUTRITIONIST REVIEW)
 * ──────────────────────────────────────────────────────────────────
 * The questionnaire Q22 goal labels (left column) do not match the
 * CSV tip category names (right column) one-to-one.  The mapping
 * below is the canonical translation used at runtime.
 *
 * Questionnaire goal (Q22)          │ Tip categories shown
 * ──────────────────────────────────┼────────────────────────────────
 * Height & strength                 │ Strength & Muscle, Bones & Calcium
 * Healthy weight gain               │ Energy & Growth, Strength & Muscle
 * Weight management                 │ Healthy Weight, Overall
 * Immunity                          │ Immunity
 * Brain & focus                     │ Brain & Focus
 * Fix picky eating                  │ Energy & Growth, Digestion & Gut, Overall
 * ──────────────────────────────────────────────────────────────────
 *
 * Deficiency flag → Tip-category mapping
 * ──────────────────────────────────────────────────────────────────
 * RiskFlaggingEngine flag (likely_gap)   │ Tip categories shown
 * ───────────────────────────────────────┼──────────────────────────
 * Iron (anemia)                          │ Iron & Blood
 * Protein / Iron                         │ Iron & Blood, Strength & Muscle
 * B-complex (B2/B3/B12)                  │ Overall
 * Vitamin A                              │ Eyes & Vision
 * Vitamin D (rickets)                    │ Bones & Calcium
 * Iron / Omega-3 / skipped breakfast     │ Iron & Blood, Brain & Focus
 * ──────────────────────────────────────────────────────────────────
 *
 * These mappings are listed in docs/seeder-notes.md under
 * "Needs nutritionist review".
 */
class ComboEngine
{
    /**
     * Questionnaire goal label → one or more nutrition_tips benefit categories.
     *
     * Keys are the exact option strings from Q22 options_scale.
     * NEEDS NUTRITIONIST REVIEW — see class docblock.
     */
    public const GOAL_CATEGORY_MAP = [
        'Height & strength'    => ['Strength & Muscle', 'Bones & Calcium'],
        'Healthy weight gain'  => ['Energy & Growth',   'Strength & Muscle'],
        'Weight management'    => ['Healthy Weight',    'Overall'],
        'Immunity'             => ['Immunity'],
        'Brain & focus'        => ['Brain & Focus'],
        'Fix picky eating'     => ['Energy & Growth',   'Digestion & Gut', 'Overall'],
    ];

    /**
     * Deficiency flag (likely_gap from deficiency_rules) → tip categories.
     *
     * NEEDS NUTRITIONIST REVIEW — see class docblock.
     */
    public const FLAG_CATEGORY_MAP = [
        'Iron (anemia)'                      => ['Iron & Blood'],
        'Protein / Iron'                     => ['Iron & Blood', 'Strength & Muscle'],
        'B-complex (B2/B3/B12)'              => ['Overall'],
        'Vitamin A'                          => ['Eyes & Vision'],
        'Vitamin D (rickets)'                => ['Bones & Calcium'],
        'Iron / Omega-3 / skipped breakfast' => ['Iron & Blood', 'Brain & Focus'],
    ];

    /**
     * Retrieve tips relevant to the child's goals and/or risk flags,
     * filtered by age and sex.
     *
     * @param  string|array  $goals  Q22 goal label(s) from the questionnaire
     * @param  array         $flags  likely_gap strings from deficiency_rules
     *                               (as returned by RiskFlaggingEngine)
     * @param  int|null      $age    Child's age in years (for age filter)
     * @param  string|null   $sex    'boy' | 'girl' | null (skip sex filter)
     * @return Collection<int, NutritionTip>
     */
    public function getCombos(
        string|array $goals,
        array $flags = [],
        ?int $age = null,
        ?string $sex = null
    ): Collection {
        $categories = $this->resolveCategories($goals, $flags);

        $query = NutritionTip::whereIn('benefit', $categories);

        // Age filter: include rows where age is null (applies to all ages)
        // OR the child's age falls within [age_min, age_max]
        if ($age !== null) {
            $query->where(function ($q) use ($age) {
                $q->whereNull('age_min')
                  ->orWhere(function ($q2) use ($age) {
                      $q2->where('age_min', '<=', $age)
                         ->where('age_max', '>=', $age);
                  });
            });
        }

        // Sex filter: include 'all' rows plus the child's specific sex
        if ($sex !== null) {
            $query->where(function ($q) use ($sex) {
                $q->where('sex', 'all')
                  ->orWhere('sex', $sex);
            });
        }

        return $query->get();
    }

    /**
     * Resolve goal labels and flag strings to tip category names.
     *
     * @param  string|array  $goals
     * @param  array         $flags
     * @return array  Unique list of tip category names
     */
    private function resolveCategories(string|array $goals, array $flags): array
    {
        $categories = [];

        // Map goal(s) to categories
        foreach ((array) $goals as $goal) {
            $mapped = self::GOAL_CATEGORY_MAP[$goal] ?? [$goal]; // fallback: use as-is
            $categories = array_merge($categories, $mapped);
        }

        // Map flag likely_gap strings to categories
        foreach ($flags as $flag) {
            $mapped = self::FLAG_CATEGORY_MAP[$flag] ?? [$flag]; // fallback: use as-is
            $categories = array_merge($categories, $mapped);
        }

        return array_unique($categories);
    }
}
