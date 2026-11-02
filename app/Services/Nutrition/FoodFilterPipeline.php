<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\FoodMaster;
use Illuminate\Support\Collection;

class FoodFilterPipeline
{
    /** Safety filters run before scoring. A rejected food never reaches selection. */
    public function getEligibleFoods(ChildAssessment $assessment, array $dislikedFoods = [], $currentSeason = 'Year-round'): Collection
    {
        $profile = ['diet_type' => $assessment->diet_type, 'allergies' => $assessment->allergies ?? []];

        return FoodMaster::query()->get()->filter(fn (FoodMaster $food) => $this->allows($food, $profile, $dislikedFoods))->values();
    }

    public function allows(FoodMaster $food, array $profile, array $dislikedFoods = []): bool
    {
        if (in_array(strtolower((string) $food->dish_name), array_map('strtolower', $dislikedFoods), true)) return false;
        if (! $this->dietAllows($food, strtolower((string) ($profile['diet_type'] ?? 'vegetarian')))) return false;

        foreach ((array) ($profile['allergies'] ?? []) as $allergy) {
            if ($this->hasAllergen($food, strtolower((string) $allergy))) return false;
        }

        return true;
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
        return match ($allergy) {
            'dairy' => $this->flag($food, 'contains_dairy'), 'nuts' => $this->flag($food, 'contains_nuts'),
            'gluten' => $this->flag($food, 'contains_gluten'), 'egg' => $this->flag($food, 'contains_egg'),
            // The schema has no lactose-specific flag. Do not treat all dairy as lactose by assumption.
            default => false,
        };
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
