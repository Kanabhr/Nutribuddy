<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    protected static ?bool $settingsTableExists = null;

    protected static array $cachedValues = [];

    public static function get(string $key, $default = null)
    {
        if (! static::settingsTableExists()) {
            return $default;
        }

        if (array_key_exists($key, static::$cachedValues)) {
            $cachedValue = static::$cachedValues[$key];
            // If cached value is an array, return default
            if (is_array($cachedValue)) {
                return $default;
            }
            return $cachedValue ?? $default;
        }

        try {
            $value = static::query()->where('key', $key)->value('value');
        } catch (\Throwable) {
            return $default;
        }

        // If the value from DB is somehow an array, use default
        if (is_array($value)) {
            static::$cachedValues[$key] = $default;
            return $default;
        }

        // Handle JSON/serialized strings that decode to arrays
        if (is_string($value) && !empty($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                static::$cachedValues[$key] = $default;
                return $default;
            }
        }

        static::$cachedValues[$key] = $value;

        return $value ?? $default;
    }

    public static function set(string $key, $value, string $group = 'general')
    {
        if (! static::settingsTableExists()) {
            return null;
        }

        static::$cachedValues[$key] = $value;

        return static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
    }

    protected static function settingsTableExists(): bool
    {
        if (static::$settingsTableExists !== null) {
            return static::$settingsTableExists;
        }

        try {
            static::$settingsTableExists = Schema::hasTable((new static())->getTable());
        } catch (\Throwable) {
            static::$settingsTableExists = false;
        }

        return static::$settingsTableExists;
    }
}
