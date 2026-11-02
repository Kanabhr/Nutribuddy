<?php

namespace App\Services\Nutrition;

use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FoodTableDataService
{
    /** @return array<string, mixed> */
    public function response(Request $request): array
    {
        $columns = [
            1 => 'dish_id',
            2 => 'dish_name',
            3 => 'food_group',
            4 => 'slot',
            5 => 'diet_type',
            6 => 'is_veg',
            7 => 'region',
            8 => 'energy_kcal',
        ];

        $totalData = Food::count();
        $query = Food::query();

        foreach (['food_group', 'slot', 'diet_type', 'is_veg', 'region'] as $filter) {
            if (filled($request->input($filter))) {
                $query->where($filter, $request->input($filter));
            }
        }

        $search = $request->input('search.value');
        if (filled($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('dish_id', 'LIKE', "%{$search}%")
                    ->orWhere('dish_name', 'LIKE', "%{$search}%")
                    ->orWhere('food_group', 'LIKE', "%{$search}%")
                    ->orWhere('slot', 'LIKE', "%{$search}%")
                    ->orWhere('diet_type', 'LIKE', "%{$search}%")
                    ->orWhere('is_veg', 'LIKE', "%{$search}%")
                    ->orWhere('region', 'LIKE', "%{$search}%");
            });
        }

        $filteredQuery = clone $query;
        $totalFiltered = $filteredQuery->count();
        $columnIndex = (int) $request->input('order.0.column', 2);
        $orderColumn = $columns[$columnIndex] ?? 'dish_name';
        $direction = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';
        $start = (int) $request->input('start', 0);
        $foods = $query->select(array_merge(array_values($columns), ['id']))
            ->offset($start)
            ->limit((int) $request->input('length', 10))
            ->orderBy($orderColumn, $direction)
            ->orderBy('id')
            ->get();

        $data = $foods->values()->map(fn (Food $food, int $index) => [
            'serial_number' => $start + $index + 1,
            'id' => $food->id,
            'dish_id' => $food->dish_id,
            'dish_name' => $food->dish_name,
            'food_group' => $food->food_group,
            'slot' => $food->slot,
            'diet_type' => $food->diet_type,
            'is_veg' => $food->is_veg === 'Yes'
                ? '<span class="badge bg-success">Yes</span>'
                : ($food->is_veg === 'No' ? '<span class="badge bg-danger">No</span>' : $food->is_veg),
            'region' => $food->region,
            'energy_kcal' => $food->energy_kcal,
        ])->all();

        return [
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $data,
        ];
    }

    /** @return array<string, list<string>> */
    public function filterOptions(): array
    {
        return collect(['food_group', 'slot', 'diet_type', 'is_veg', 'region'])
            ->mapWithKeys(fn (string $column) => [
                $column => Food::query()->whereNotNull($column)->where($column, '<>', '')
                    ->distinct()->orderBy($column)->pluck($column)->all(),
            ])->all();
    }

    public function isReferencedByNutritionHistory(int $foodId): bool
    {
        return DB::table('diet_plan_items')->where('food_id', $foodId)->exists()
            || DB::table('deficiency_foods')->where('food_id', $foodId)->exists()
            || DB::table('meal_item_logs')->where('food_id', $foodId)->exists();
    }
}
