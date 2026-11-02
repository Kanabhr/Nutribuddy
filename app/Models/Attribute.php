<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'values',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'values' => 'array',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Attribute $attribute) {
            if (! $attribute->slug) {
                $attribute->slug = Str::slug($attribute->name);
            }

            if (is_array($attribute->values)) {
                $attribute->values = collect($attribute->values)
                    ->map(fn ($value) => trim((string) $value))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }
        });
    }
}
