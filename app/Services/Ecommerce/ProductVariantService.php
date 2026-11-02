<?php

namespace App\Services\Ecommerce;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

class ProductVariantService
{
    public function create(array $data): ProductVariant
    {
        $attributes = isset($data['attributes']) ? json_decode($data['attributes'], true) : null;
        $isDefault = (bool) ($data['is_default'] ?? false);
        if ($isDefault) {
            ProductVariant::where('product_id', $data['product_id'])->update(['is_default' => false]);
        }

        $variant = ProductVariant::create([
            'product_id' => $data['product_id'],
            'name' => $data['name'],
            'sku' => $this->uniqueSku($data['sku'] ?? null, $data['name']),
            'attributes' => $attributes,
            'price' => $data['price'],
            'compare_at_price' => $data['compare_at_price'] ?? null,
            'cost_price' => $data['cost_price'] ?? null,
            'currency' => 'INR',
            'is_default' => $isDefault,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'position' => $data['position'] ?? 0,
        ]);

        Inventory::updateOrCreate(
            ['product_variant_id' => $variant->id],
            [
                'product_id' => $variant->product_id,
                'track_stock' => (bool) ($data['track_stock'] ?? true),
                'stock_qty' => $data['stock_qty'] ?? 0,
                'reserved_qty' => $data['reserved_qty'] ?? 0,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
                'is_in_stock' => (bool) ($data['is_in_stock'] ?? true),
            ]
        );
        Product::whereKey($variant->product_id)->update(['is_variant_enabled' => true, 'product_type' => 'variable']);

        return $variant;
    }

    public function update(ProductVariant $variant, array $data, bool $hasStock): void
    {
        $attributes = isset($data['attributes']) ? json_decode($data['attributes'], true) : null;
        $isDefault = (bool) ($data['is_default'] ?? false);
        if ($isDefault) {
            ProductVariant::where('product_id', $variant->product_id)
                ->where('id', '!=', $variant->id)->update(['is_default' => false]);
        }

        $variant->update([
            'name' => $data['name'],
            'sku' => $this->uniqueSku($data['sku'] ?? null, $data['name'], $variant->id),
            'attributes' => $attributes,
            'price' => $data['price'],
            'compare_at_price' => $data['compare_at_price'] ?? null,
            'cost_price' => $data['cost_price'] ?? null,
            'is_default' => $isDefault,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'position' => $data['position'] ?? 0,
            'currency' => 'INR',
        ]);

        if ($hasStock) {
            Inventory::updateOrCreate(
                ['product_variant_id' => $variant->id],
                [
                    'product_id' => $variant->product_id,
                    'track_stock' => (bool) ($data['track_stock'] ?? false),
                    'stock_qty' => $data['stock_qty'],
                    'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
                    'is_in_stock' => (bool) ($data['is_in_stock'] ?? false),
                ]
            );
        }
    }

    public function updateInventory(ProductVariant $variant, array $data): void
    {
        Inventory::updateOrCreate(
            ['product_variant_id' => $variant->id],
            [
                'product_id' => $variant->product_id,
                'track_stock' => (bool) ($data['track_stock'] ?? false),
                'stock_qty' => $data['stock_qty'],
                'reserved_qty' => $data['reserved_qty'] ?? 0,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
                'is_in_stock' => (bool) ($data['is_in_stock'] ?? false),
            ]
        );
    }

    public function delete(ProductVariant $variant): void
    {
        $productId = $variant->product_id;
        Inventory::where('product_variant_id', $variant->id)->delete();
        $variant->delete();

        if (! ProductVariant::where('product_id', $productId)->exists()) {
            Product::whereKey($productId)->update(['is_variant_enabled' => false, 'product_type' => 'simple']);
        }
    }

    private function uniqueSku(?string $sku, string $name, ?int $ignoreVariantId = null): string
    {
        $baseSku = trim((string) $sku);
        if ($baseSku === '') {
            $baseSku = 'NB-' . Str::upper(Str::random(4)) . '-' . Str::upper(Str::slug($name ?: 'variant', '-'));
        }
        $baseSku = Str::upper(Str::slug($baseSku, '-')) ?: 'NB-' . Str::upper(Str::random(8));
        $candidate = Str::limit($baseSku, 240, '');
        $suffix = 1;

        while (ProductVariant::where('sku', $candidate)
            ->when($ignoreVariantId, fn ($query) => $query->whereKeyNot($ignoreVariantId))->exists()) {
            $suffixText = '-' . $suffix++;
            $candidate = Str::limit($baseSku, 255 - strlen($suffixText), '') . $suffixText;
        }

        return $candidate;
    }
}
