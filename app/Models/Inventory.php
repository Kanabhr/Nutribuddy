<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'track_stock',
        'stock_qty',
        'reserved_qty',
        'low_stock_threshold',
        'is_in_stock',
    ];

    protected function casts(): array
    {
        return [
            'track_stock' => 'boolean',
            'stock_qty' => 'integer',
            'reserved_qty' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_in_stock' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
