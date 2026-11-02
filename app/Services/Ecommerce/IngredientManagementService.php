<?php

namespace App\Services\Ecommerce;

use App\Models\Ingredient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Persists ingredients, their benefits, product links, and optional icons. */
class IngredientManagementService
{
    /** @param array<string, mixed> $data */
    public function create(array $data, ?UploadedFile $icon = null): Ingredient
    {
        $iconPath = $icon?->store('ingredients/icons', 'public');
        $attributes = $this->attributes($data);
        if ($iconPath) {
            $attributes['icon_path'] = $iconPath;
        }

        try {
            return DB::transaction(function () use ($attributes, $data): Ingredient {
                $ingredient = Ingredient::create($attributes);
                $this->syncBenefits($ingredient, $data['benefits'] ?? []);
                $ingredient->products()->sync($data['products'] ?? []);

                return $ingredient;
            });
        } catch (Throwable $exception) {
            if ($iconPath) {
                Storage::disk('public')->delete($iconPath);
            }
            throw $exception;
        }
    }

    /** @param array<string, mixed> $data */
    public function update(Ingredient $ingredient, array $data, ?UploadedFile $icon = null): void
    {
        $newIconPath = $icon?->store('ingredients/icons', 'public');
        $previousIconPath = $ingredient->icon_path;
        $attributes = $this->attributes($data, updating: true);
        if ($newIconPath) {
            $attributes['icon_path'] = $newIconPath;
        }

        try {
            DB::transaction(function () use ($ingredient, $attributes, $data): void {
                $ingredient = Ingredient::query()->whereKey($ingredient->id)->lockForUpdate()->firstOrFail();
                $ingredient->update($attributes);
                $this->syncBenefits($ingredient, $data['benefits'] ?? []);
                $ingredient->products()->sync($data['products'] ?? []);
            });
        } catch (Throwable $exception) {
            if ($newIconPath) {
                Storage::disk('public')->delete($newIconPath);
            }
            throw $exception;
        }

        if ($newIconPath && $previousIconPath) {
            Storage::disk('public')->delete($previousIconPath);
        }
    }

    public function delete(Ingredient $ingredient): void
    {
        $iconPath = $ingredient->icon_path;
        $ingredient->delete();

        if ($iconPath) {
            Storage::disk('public')->delete($iconPath);
        }
    }

    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    private function attributes(array $data, bool $updating = false): array
    {
        unset($data['icon'], $data['benefits'], $data['products']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? ! $updating);
        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);

        return $data;
    }

    /** @param array<int, string|null> $benefits */
    private function syncBenefits(Ingredient $ingredient, array $benefits): void
    {
        $ingredient->benefits()->delete();

        $rows = collect($benefits)
            ->filter(fn ($heading) => filled($heading))
            ->values()
            ->map(fn ($heading, $index) => [
                'heading' => trim((string) $heading),
                'sort_order' => $index,
            ])
            ->all();

        if ($rows) {
            $ingredient->benefits()->createMany($rows);
        }
    }
}
