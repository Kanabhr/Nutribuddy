<?php

namespace App\Services\Checkout;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class StateCityService
{
    private const STATE_CITY_JSON = 'app/india_states_cities.json';
    private const STATE_ALIASES = [
        'Dadra and Nagar Haveli' => 'Dadra and Nagar Haveli and Daman and Diu',
        'Daman and Diu' => 'Dadra and Nagar Haveli and Daman and Diu',
    ];

    private const REQUIRED_STATES = [
        'Andaman and Nicobar Islands',
        'Andhra Pradesh',
        'Arunachal Pradesh',
        'Assam',
        'Bihar',
        'Chandigarh',
        'Chhattisgarh',
        'Dadra and Nagar Haveli and Daman and Diu',
        'Delhi',
        'Goa',
        'Gujarat',
        'Haryana',
        'Himachal Pradesh',
        'Jammu and Kashmir',
        'Jharkhand',
        'Karnataka',
        'Kerala',
        'Ladakh',
        'Lakshadweep',
        'Madhya Pradesh',
        'Maharashtra',
        'Manipur',
        'Meghalaya',
        'Mizoram',
        'Nagaland',
        'Odisha',
        'Puducherry',
        'Punjab',
        'Rajasthan',
        'Sikkim',
        'Tamil Nadu',
        'Telangana',
        'Tripura',
        'Uttar Pradesh',
        'Uttarakhand',
        'West Bengal',
    ];

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

                $normalizedCode = is_numeric($code)
                    ? str_pad((string) $code, 2, '0', STR_PAD_LEFT)
                    : trim((string) $code);

                if ($normalizedCode === '') {
                    continue;
                }

                $states[$normalizedCode] = [
                    'name' => (string) ($state['name'] ?? ''),
                    'cities' => is_array($state['cities'] ?? null) ? $state['cities'] : [],
                ];
            }

            $merged = [];
            $nameIndex = []; // Index for O(1) lookups instead of O(n) search

            foreach ($states as $code => $state) {
                $name = trim($state['name']) !== '' ? trim($state['name']) : $code;
                $name = self::STATE_ALIASES[$name] ?? $name;
                $searchName = strtolower($name);

                if (! isset($nameIndex[$searchName])) {
                    $merged[$code] = [
                        'name' => $name,
                        'cities' => $state['cities'],
                    ];
                    $nameIndex[$searchName] = $code;
                    continue;
                }

                $existingCode = $nameIndex[$searchName];

                $merged[$existingCode]['cities'] = collect($merged[$existingCode]['cities'])
                    ->merge($state['cities'])
                    ->filter(fn ($city) => is_string($city) && trim($city) !== '')
                    ->unique(fn ($city) => strtolower($city))
                    ->values()
                    ->all();
            }

            // O(1) check for required states
            foreach (self::REQUIRED_STATES as $name) {
                $searchName = strtolower($name);
                
                if (! isset($nameIndex[$searchName])) {
                    $manualCode = 'manual-' . strtolower(str_replace([' ', 'and'], ['-', 'and'], $name));
                    $merged[$manualCode] = [
                        'name' => $name,
                        'cities' => [],
                    ];
                    $nameIndex[$searchName] = $manualCode;
                }
            }

            return $merged;
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
