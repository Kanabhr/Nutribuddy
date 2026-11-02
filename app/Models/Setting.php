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
            return static::$cachedValues[$key] ?? $default;
        }

        try {
            $value = static::query()->where('key', $key)->value('value');
        } catch (\Throwable) {
            return $default;
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
