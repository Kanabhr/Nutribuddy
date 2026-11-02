<?php

namespace App\Services\Ecommerce;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductVariantSyncService
{

    public function normalizedVariations(array $variations): array
    {
        return collect($variations)
            ->map(function (array $variation) {
                $attributes = collect($variation['attributes'] ?? [])
                    ->map(fn ($value) => trim((string) $value))
                    ->filter()
                    ->all();

                if (empty($attributes)) {
                    return null;
                }

                $variation['sku'] = trim((string) ($variation['sku'] ?? ''));
                $variation['attributes'] = $attributes;
                return $variation;
            })
            ->filter()
            ->values()
            ->all();
    }


    public function validateVariationSkus(array $variations, ?Product $product = null): void
    {
        $errors = [];
        $seen = [];
        $ownedVariantIds = $product
            ? $product->variants()->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        foreach ($variations as $index => $variation) {
            $sku = trim((string) ($variation['sku'] ?? ''));
            if ($sku === '') {
                continue;
            }

            $normalizedSku = Str::lower($sku);
            if (isset($seen[$normalizedSku])) {
                $errors["variations.$index.sku"] = "The variation SKU '{$sku}' is already used in this product form.";
                continue;
            }
            $seen[$normalizedSku] = $index;

            $variantId = isset($variation['id']) ? (int) $variation['id'] : null;
            if ($variantId && ! $product) {
                $errors["variations.$index.sku"] = 'Existing variation IDs cannot be submitted when creating a new product.';
                continue;
            }

            if ($variantId && $product && ! in_array($variantId, $ownedVariantIds, true)) {
                $errors["variations.$index.sku"] = "The variation SKU '{$sku}' belongs to another product variant.";
                continue;
            }

            $existingVariant = ProductVariant::where('sku', $sku)
                ->when($variantId, fn ($query) => $query->whereKeyNot($variantId))
                ->first();

            if ($existingVariant && (! $product || (int) $existingVariant->product_id !== (int) $product->id)) {
                $errors["variations.$index.sku"] = "The variation SKU '{$sku}' has already been taken.";
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }


    public function syncProductVariations(Product $product, array $variations, bool $deleteMissing = false): void
    {
        $keptVariantIds = [];
        $hasDefault = collect($variations)->contains(fn ($variation) => (bool) ($variation['is_default'] ?? false));

        // Map submitted attribute values to name arrays for validation
        $allowedValuesByName = [];
        $submittedAttrValues = request()->input('product_attribute_values') ?? [];
        if (!empty($submittedAttrValues)) {
            $attributesList = \App\Models\Attribute::whereIn('id', array_keys($submittedAttrValues))->get();
            foreach ($attributesList as $attr) {
                $allowedValuesByName[strtolower($attr->name)] = array_map(function($v) {
                    return strtolower(trim((string)$v));
                }, $submittedAttrValues[$attr->id] ?? []);
            }
        }

        foreach ($variations as $index => $variationData) {
            $attributes = $variationData['attributes'];
            $name = $variationData['name'] ?: $this->variationName($attributes);
            $variantId = $variationData['id'] ?? null;

            $variant = $variantId
                ? $product->variants()->whereKey($variantId)->first()
                : null;

            if (! $variant && ! empty($variationData['sku'])) {
                $variant = $product->variants()->where('sku', $variationData['sku'])->first();
            }

            if (! $variant) {
                $variant = new ProductVariant(['product_id' => $product->id]);
            }

            $isDefault = $hasDefault
                ? (bool) ($variationData['is_default'] ?? false)
                : $index === 0;

            $isActive = (bool) ($variationData['is_active'] ?? false);
            if ($isActive && !empty($allowedValuesByName)) {
                foreach ($attributes as $key => $value) {
                    $keyLower = strtolower($key);
                    $valueLower = strtolower(trim((string)$value));
                    if (isset($allowedValuesByName[$keyLower])) {
                        $matched = false;
                        foreach ($allowedValuesByName[$keyLower] as $allowedVal) {
                            if ($valueLower === $allowedVal) {
                                $matched = true;
                                break;
                            }
                            if (str_contains($valueLower, 'grape') && str_contains($allowedVal, 'grape')) {
                                $matched = true;
                                break;
                            }
                            if (str_contains($valueLower, 'banana') && str_contains($allowedVal, 'banana')) {
                                $matched = true;
                                break;
                            }
                            if (str_contains($valueLower, 'mango') && str_contains($allowedVal, 'mango')) {
                                $matched = true;
                                break;
                            }
                            if (str_contains($valueLower, 'apple') && str_contains($allowedVal, 'apple')) {
                                $matched = true;
                                break;
                            }
                        }
                        if (!$matched) {
                            $isActive = false;
                            break;
                        }
                    }
                }
            }

            $variant->fill([
                'product_id' => $product->id,
                'name' => $name,
                'sku' => $this->uniqueVariantSku($variationData['sku'] ?? null, $name, $variant->exists ? $variant->id : null),
                'attributes' => $attributes,
                'price' => $variationData['price'],
                'compare_at_price' => $variationData['compare_at_price'] ?? null,
                'cost_price' => $variationData['cost_price'] ?? null,
                'currency' => 'INR',
                'is_default' => $isDefault,
                'is_active' => $isActive,
                'position' => $index,
            ]);
            $variant->save();

            $keptVariantIds[] = $variant->id;

            Inventory::updateOrCreate(
                ['product_variant_id' => $variant->id],
                [
                    'product_id' => $product->id,
                    'track_stock' => true,
                    'stock_qty' => $variationData['stock_qty'] ?? 0,
                    'reserved_qty' => 0,
                    'low_stock_threshold' => 5,
                    'is_in_stock' => (int) ($variationData['stock_qty'] ?? 0) > 0,
                ]
            );
        }

        if ($deleteMissing) {
            $variantsToDeactivate = $product->variants()
                ->when($keptVariantIds, fn ($query) => $query->whereNotIn('id', $keptVariantIds))
                ->get();

            $this->deactivateProductVariants($variantsToDeactivate);
        }
    }


    public function deactivateMissingProductVariations(Product $product): void
    {
        $this->deactivateProductVariants($product->variants()->whereNotNull('attributes')->get());
    }


    private function deactivateProductVariants($variants): void
    {
        foreach ($variants as $variant) {
            $inOrders = \Illuminate\Support\Facades\DB::table('order_items')->where('product_variant_id', $variant->id)->exists();
            $inCarts = \Illuminate\Support\Facades\DB::table('cart_items')->where('product_variant_id', $variant->id)->exists();

            if (!$inOrders && !$inCarts) {
                Inventory::where('product_variant_id', $variant->id)->delete();
                $variant->delete();
            } else {
                $variant->forceFill([
                    'is_active' => false,
                    'is_default' => false,
                ])->save();

                Inventory::where('product_variant_id', $variant->id)->update([
                    'stock_qty' => 0,
                    'is_in_stock' => false,
                ]);
            }
        }
    }


    public function ensureSimpleVariant(Product $product): ProductVariant
    {
        $variant = $product->variants()->where(function ($query) {
            $query->whereNull('attributes')->orWhereJsonLength('attributes', 0);
        })->first();

        if (! $variant) {
            $variant = new ProductVariant(['product_id' => $product->id]);
        }

        $variant->fill([
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $this->uniqueVariantSku($product->sku . '-DEF', $product->name, $variant->exists ? $variant->id : null),
            'attributes' => null,
            'price' => $product->base_price,
            'compare_at_price' => $product->compare_at_price,
            'cost_price' => $product->cost_price,
            'currency' => 'INR',
            'is_default' => true,
            'is_active' => true,
            'position' => 0,
        ]);
        $variant->save();

        return $variant;
    }


    public function uniqueProductSku(?string $sku, string $name, ?int $ignoreProductId = null): string
    {
        $base = trim((string) $sku);
        if ($base === '') {
            $base = 'NB-' . Str::upper(Str::random(4)) . '-' . Str::slug($name ?: 'product');
        }

        return $this->uniqueSkuForModel(Product::class, $base, $ignoreProductId);
    }


    private function uniqueVariantSku(?string $sku, ?string $name = null, ?int $ignoreVariantId = null): string
    {
        $base = trim((string) $sku);
        if ($base === '') {
            $base = 'NBV-' . Str::upper(Str::random(4)) . '-' . Str::slug($name ?: 'variant');
        }

        return $this->uniqueSkuForModel(ProductVariant::class, $base, $ignoreVariantId);
    }


    private function uniqueSkuForModel(string $modelClass, string $baseSku, ?int $ignoreId = null): string
    {
        $baseSku = Str::limit(trim($baseSku), 220, '');
        $candidate = $baseSku !== '' ? $baseSku : 'NB-' . Str::upper(Str::random(8));
        $counter = 1;

        while (
            $modelClass::where('sku', $candidate)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $suffix = '-' . $counter++;
            $candidate = Str::limit($baseSku, 255 - strlen($suffix), '') . $suffix;
        }

        return $candidate;
    }


    private function variationName(array $attributes): string
    {
        return collect($attributes)
            ->map(fn ($value, $name) => "{$name}: {$value}")
            ->implode(' / ');
    }
}
