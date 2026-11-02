<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    use HasFactory;

    protected $appends = ['display_price', 'display_compare_price'];

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'attributes',
        'price',
        'compare_at_price',
        'cost_price',
        'currency',
        'is_default',
        'is_active',
        'position',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    /**
     * Get the price to be displayed on storefront.
     * Includes GST if 'show_in_checkout' is false.
     */
    public function getDisplayPriceAttribute(): float
    {
        $price = (float) $this->price;
        $taxRate = $this->product->taxRate;
        
        if ($taxRate && ! (bool) $taxRate->show_in_checkout) {
            $rate = (float) $taxRate->rate;
            $price += ($price * $rate) / 100;
        }
        
        return (float) round($price, 0);
    }

    /**
     * Get the compare price to be displayed on storefront.
     * Includes GST if 'show_in_checkout' is false.
     */
    public function getDisplayComparePriceAttribute(): float
    {
        $price = (float) ($this->compare_at_price ?? 0);
        if ($price <= 0) return 0.0;

        $taxRate = $this->product->taxRate;
        
        if ($taxRate && ! (bool) $taxRate->show_in_checkout) {
            $rate = (float) $taxRate->rate;
            $price += ($price * $rate) / 100;
        }
        
        return (float) round($price, 0);
    }
}
