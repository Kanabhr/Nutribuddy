<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\FoodMaster;
use Illuminate\Support\Collection;

class FoodFilterPipeline
{
    /**
     * The ICMR-NIN 2020 RDA/EAR note says the cereal-legume-milk composition of
     * the diet should be 3:1:2.5 for good protein quality.
     * Restoring a protected group when dislikes would wipe it from the eligible
     * pool is OUR OWN safeguard, not an ICMR rule. UNVERIFIED: needs nutritionist
     * confirmation. The tokens here are matched against food_group.
     */
    private const PROTECTED_GROUP_TOKENS = ['pulse', 'legume', 'dal', 'dairy', 'milk', 'cereal', 'grain'];

    /** Safety filters run before scoring. A rejected food never reaches selection. */
    public function getEligibleFoods(ChildAssessment $assessment, array $dislikedFoods = [], $currentSeason = 'Year-round'): Collection
    {
        $profile = ['diet_type' => $assessment->diet_type, 'allergies' => $assessment->allergies ?? []];

        // Step 1: apply only hard safety filters (diet + allergy).
        $safePool = FoodMaster::query()->get()
            ->filter(fn (FoodMaster $food) => $this->allowsSafe($food, $profile))
            ->values();

        // Step 2: soft-exclude dislikes with food-group protection fallback.
        return $this->applyDislikes($safePool, $dislikedFoods);
    }

    /**
     * Returns true when the food passes HARD safety rules (diet type + allergies).
     * Dislikes are NOT checked here; they are applied later so fallback is possible.
     */
    public function allowsSafe(FoodMaster $food, array $profile): bool
    {
        if (! $this->dietAllows($food, strtolower((string) ($profile['diet_type'] ?? 'vegetarian')))) {
            return false;
        }

        foreach ((array) ($profile['allergies'] ?? []) as $allergy) {
            if ($this->hasAllergen($food, strtolower((string) $allergy))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Legacy convenience method kept for callers that pass all three constraints
     * together (diet + allergy + dislike name match).
     *
     * The dislike check here uses the new isDisliked() (substring match on name
     * OR food_group).  This is backward-compatible for name-exact callers since
     * "Karela Sabji" still matches term "karela" as a substring.
     */
    public function allows(FoodMaster $food, array $profile, array $dislikedFoods = []): bool
    {
        if (! $this->allowsSafe($food, $profile)) {
            return false;
        }

        if ($dislikedFoods !== [] && $this->isDisliked($food, $dislikedFoods)) {
            return false;
        }

        return true;
    }

    /**
     * Returns true when the food matches a dislike term.
     *
     * Matching rules (any one sufficient):
     *   1. food name matches the term as a whole word (case-insensitive,
     *      optional plural suffix s/es).
     *   2. any food_group TOKEN (split on non-letter chars) matches the term
     *      the same way.
     *
     * Whole-word matching prevents false positives like "pea" matching "peanut"
     * or "dal" matching "dalia".
     *
     * Allergies always beat favourites: a disliked food that also triggers an
     * allergy is excluded by the allergy gate first and never reaches here.
     */
    public function isDisliked(FoodMaster $food, array $dislikedFoods): bool
    {
        $name        = strtolower((string) $food->dish_name);
        $groupTokens = self::groupTokens((string) ($food->food_group ?? ''));

        foreach ($dislikedFoods as $term) {
            $term = strtolower(trim((string) $term));
            if ($term === '') {
                continue;
            }
            if (self::wordMatches($term, $name)) {
                return true;
            }
            foreach ($groupTokens as $token) {
                if (self::wordMatches($term, $token)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Whole-word, case-insensitive match that also accepts an optional plural
     * suffix (s or es) on the term.
     *
     * Examples:
     *   wordMatches('pea',      'pea curry')      → true
     *   wordMatches('pea',      'peanut butter')  → false
     *   wordMatches('rice',     'licorice')        → false
     *   wordMatches('dal',      'dalia')           → false
     *   wordMatches('mushroom', 'mushrooms curry') → true  (plural)
     *   wordMatches('paneer',   'Paneer Bhurji')   → true  (case)
     *
     * The pattern allows es/s as optional suffix so "pulse" matches "pulses"
     * and "tomato" matches "tomatoes".
     */
    public static function wordMatches(string $term, string $text): bool
    {
        if ($term === '' || $text === '') {
            return false;
        }

        // Escape regex special chars in the term, then anchor it with word
        // boundaries and allow an optional plural suffix (es before s so that
        // "es" is tried first and avoids consuming only "s" when "es" is present).
        $escaped = preg_quote($term, '/');
        $pattern = '/\b' . $escaped . '(?:es|s)?\b/iu';

        return (bool) preg_match($pattern, $text);
    }

    /**
     * Split a food_group string into lower-case letter-only tokens.
     * E.g. "Cereal+Pulse"  → ['cereal', 'pulse']
     *      "Dairy/Salad"   → ['dairy', 'salad']
     *
     * @return string[]
     */
    private static function groupTokens(string $group): array
    {
        $parts = preg_split('/[^a-zA-Z]+/', $group, -1, PREG_SPLIT_NO_EMPTY);
        return array_map('strtolower', $parts ?: []);
    }

    public function forSlot(Collection $foods, string $slot): Collection
    {
        $wanted = strtolower(str_contains($slot, 'Snack') ? 'snack' : $slot);

        return $foods->filter(function (FoodMaster $food) use ($wanted) {
            $slots = collect([$food->slot, $food->also_suitable_for])
                ->filter()->flatMap(fn ($value) => preg_split('/[,|\/]+/', strtolower((string) $value)))
                ->map(fn ($value) => trim($value));
            return $slots->contains($wanted);
        })->values();
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    /**
     * Apply dislike exclusions to a safety-filtered pool with two safety nets:
     *
     *  (a) Per-slot fallback: handled in the engine — the pool returned here may
     *      still be empty for a given slot, and the engine's slot loop restores
     *      the full safe pool for just that slot.
     *
     *  (b) Food-group safeguard: if a dislike term would eliminate an entire
     *      protected food group from the whole pool, we restore foods in that
     *      group so at least one cereal, legume, and dairy option survives.
     *      The 3:1:2.5 cereal:legume:milk figure (ICMR-NIN 2020 note) says it
     *      "should" be that for protein quality; the restore logic is our own,
     *      UNVERIFIED, and pending nutritionist confirmation.
     */
    private function applyDislikes(Collection $safePool, array $dislikedFoods): Collection
    {
        if ($dislikedFoods === []) {
            return $safePool;
        }

        $filtered = $safePool->filter(fn (FoodMaster $f) => ! $this->isDisliked($f, $dislikedFoods))->values();

        // Food-group protection: for each protected token, if the filtered pool
        // has ZERO foods with that group token but the safe pool has some,
        // restore those foods so the group survives in the eligible pool.
        $restored = $filtered;
        foreach (self::PROTECTED_GROUP_TOKENS as $token) {
            $inFiltered = $filtered->filter(fn (FoodMaster $f) => str_contains(strtolower((string) ($f->food_group ?? '')), $token));
            if ($inFiltered->isEmpty()) {
                $inSafe = $safePool->filter(fn (FoodMaster $f) => str_contains(strtolower((string) ($f->food_group ?? '')), $token));
                if ($inSafe->isNotEmpty()) {
                    // Restore the safe-pool foods for this group (they are NOT
                    // excluded by allergy/diet, only by preference).
                    $restored = $restored->merge($inSafe)->unique('id')->values();
                }
            }
        }

        return $restored;
    }

    private function dietAllows(FoodMaster $food, string $diet): bool
    {
        return match ($diet) {
            'vegan' => $this->flag($food, 'is_vegan') && ! $this->flag($food, 'contains_dairy') && ! $this->flag($food, 'contains_egg') && ! $this->flag($food, 'contains_honey'),
            'jain' => $this->flag($food, 'is_jain_ok'),
            'eggetarian' => ! $this->isMeatOrFish($food),
            'non-vegetarian' => true,
            default => $this->flag($food, 'is_veg') && ! $this->flag($food, 'contains_egg'),
        };
    }

    private function hasAllergen(FoodMaster $food, string $allergy): bool
    {
        // For each allergy token we check the corresponding flag on the food row.
        // If the flag is NULL or empty (i.e. not explicitly set to "Yes") we treat the
        // food as UNSAFE for that allergy to err on the side of child safety.
        return match ($allergy) {
            // 'dairy' and 'lactose' both exclude foods with contains_dairy = Yes.
            // Nutritionist note: a nutritionist may relax the lactose restriction to allow
            // low-lactose dairy (e.g. hard cheese, ghee) on a case-by-case basis; the system
            // keeps it strict by default as a safety choice; needs nutritionist confirmation.
            'dairy', 'lactose' => $this->flagUnsafe($food, 'contains_dairy'),
            'nuts'             => $this->flagUnsafe($food, 'contains_nuts'),
            'gluten'           => $this->flagUnsafe($food, 'contains_gluten'),
            'egg'              => $this->flagUnsafe($food, 'contains_egg'),
            'soy'              => $this->flagUnsafe($food, 'contains_soy'),
            'sesame'           => $this->flagUnsafe($food, 'contains_sesame'),
            'honey'            => $this->flagUnsafe($food, 'contains_honey'),
            default            => false,
        };
    }

    /**
     * Returns true (unsafe) when the food has the allergy flag set to Yes,
     * AND also when the flag column is NULL or empty – i.e. the data entry team
     * has not yet verified the food.  Failing safe protects the child.
     */
    private function flagUnsafe(FoodMaster $food, string $field): bool
    {
        $raw = $food->getAttributes()[$field] ?? null;

        // Explicitly "No" → safe.  Anything else (Yes, null, '', unknown) → unsafe.
        return ! in_array(strtolower(trim((string) $raw)), ['no', '0', 'false'], true);
    }

    private function isMeatOrFish(FoodMaster $food): bool
    {
        return str_contains(strtolower((string) $food->diet_type), 'non')
            || in_array(strtolower((string) $food->food_group), ['meat', 'fish', 'poultry'], true);
    }

    private function yes(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'yes', 'true'], true);
    }

    private function flag(FoodMaster $food, string $field): bool
    {
        return $this->yes($food->getAttributes()[$field] ?? null);
    }
}