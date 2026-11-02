<?php

namespace App\Imports;

use App\Models\Food;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FoodsImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    public int $imported = 0;

    public int $skipped = 0;

    /** @var list<array<string, string>> */
    public array $failedRows = [];

    /** @var array<string, true> */
    private array $seenDishIds = [];

    public function collection(Collection $rows): void
    {
        $prepared = [];
        $dishIds = [];

        foreach ($rows as $row) {
            $row = is_array($row) ? $row : $row->toArray();
            $dishId = trim((string) ($row['dish_id'] ?? ''));
            $dishName = trim((string) ($row['dish_name'] ?? ''));

            if ($dishId === '' && $dishName === '') {
                $this->skipped++;

                continue;
            }

            $data = [
                'dish_id' => $dishId,
                'dish_name' => $dishName !== '' ? $dishName : null,
                'food_group' => $row['food_group'] ?? null,
                'slot' => $row['slot'] ?? null,
                'diet_type' => $row['diet_type'] ?? null,
                'is_veg' => $row['is_veg'] ?? null,
                'region' => $row['region'] ?? null,
                'energy_kcal' => $row['energy_kcal'] ?? null,
            ];

            if ($dishId === '') {
                $this->failedRows[] = $this->failedRow($data, 'dish_id is required');

                continue;
            }

            $prepared[] = $data;
            $dishIds[] = $dishId;
        }

        if ($prepared === []) {
            return;
        }

        $existingIds = array_fill_keys(
            Food::query()->whereIn('dish_id', array_values(array_unique($dishIds)))->pluck('dish_id')->all(),
            true,
        );
        $insertable = [];

        foreach ($prepared as $data) {
            $dishId = $data['dish_id'];
            if (isset($existingIds[$dishId]) || isset($this->seenDishIds[$dishId])) {
                $reason = isset($existingIds[$dishId])
                    ? 'Duplicate dish_id (already in database)'
                    : 'Duplicate dish_id (repeated in file)';
                $this->failedRows[] = $this->failedRow($data, $reason);

                continue;
            }

            $this->seenDishIds[$dishId] = true;
            $insertable[] = $data;
        }

        if ($insertable === []) {
            return;
        }

        $timestamp = now();
        $insertRows = array_map(fn (array $row) => $row + [
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], $insertable);

        try {
            DB::table((new Food)->getTable())->insert($insertRows);
            $this->imported += count($insertRows);
        } catch (\Throwable) {
            // Retry individually only on a failed batch so one malformed row does not discard valid rows.
            foreach ($insertable as $data) {
                try {
                    Food::query()->create($data);
                    $this->imported++;
                } catch (\Throwable $exception) {
                    $this->failedRows[] = $this->failedRow($data, $exception->getMessage());
                }
            }
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    private function failedRow(array $data, string $reason): array
    {
        return [
            'dish_id' => (string) ($data['dish_id'] ?: '(blank)'),
            'dish_name' => (string) ($data['dish_name'] ?? ''),
            'food_group' => (string) ($data['food_group'] ?? ''),
            'slot' => (string) ($data['slot'] ?? ''),
            'reason' => $reason,
        ];
    }
}
