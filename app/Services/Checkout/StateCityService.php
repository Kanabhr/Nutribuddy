<?php

namespace App\Services\Checkout;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class StateCityService
{
    private const STATE_CITY_JSON = 'app/india_states_districts.json';

    /**
     * Memoized data per request to avoid multiple cache reads
     */
    private ?array $memoizedData = null;

    public function getStateCityData(): array
    {
        if ($this->memoizedData !== null) {
            return $this->memoizedData;
        }

        $this->memoizedData = Cache::rememberForever('india_states_cities_data_v2', function () {
            $path = storage_path(self::STATE_CITY_JSON);

            if (! File::exists($path)) {
                return [];
            }

            $data = json_decode(File::get($path), true);

            if (! is_array($data)) {
                return [];
            }

            $states = [];
            foreach ($data as $code => $state) {
                if (! is_array($state)) {
                    continue;
                }

                // Use the code as-is from the JSON, just trim and validate it
                $stateCode = trim((string) $code);

                if ($stateCode === '') {
                    continue;
                }

                // Support both 'cities' and 'districts' keys
                $items = $state['districts'] ?? $state['cities'] ?? [];

                $states[$stateCode] = [
                    'name' => (string) ($state['name'] ?? ''),
                    'cities' => is_array($items) ? $items : [],
                ];
            }

            return $states;
        });

        return $this->memoizedData;
    }

    public function checkoutStates(): array
    {
        return collect($this->getStateCityData())
            ->map(fn (array $state, string $code) => [
                'code' => $code,
                'name' => $state['name'] ?: $code,
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    public function checkoutCitiesForState(string $stateCode): ?array
    {
        $state = $this->getStateCityData()[$stateCode] ?? null;

        if (! $state) {
            return null;
        }

        return collect($state['cities'] ?? [])
            ->filter(fn ($city) => is_string($city) && trim($city) !== '')
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
