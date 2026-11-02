<?php

namespace App\Services\Ecommerce;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductManagementService
{
    public function __construct(
        private readonly ProductVariantSyncService $variants,
        private readonly ProductContentService $content
    ) {}


    public function store(Request $request, array $validated): RedirectResponse
    {
        

        $variations = $this->variants->normalizedVariations($validated['variations'] ?? []);
        $this->variants->validateVariationSkus($variations);
        $hasVariations = ! empty($variations);
        $parentStockQty = $hasVariations
            ? collect($variations)->sum(fn ($variation) => (int) ($variation['stock_qty'] ?? 0))
            : (int) $request->input('stock_qty', 0);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['sku'] = $this->variants->uniqueProductSku($validated['sku'] ?? null, $validated['name']);
        $validated['currency'] = 'INR';
        $validated['product_type'] = $hasVariations ? 'variable' : 'simple';
        $validated['is_variant_enabled'] = $hasVariations;
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['is_featured'] = (bool) ($validated['is_featured'] ?? false);
        $variantTypes = [];
        $submittedAttrValues = $request->input('product_attribute_values') ?? [];
        if (!empty($submittedAttrValues)) {
            $attributesList = \App\Models\Attribute::whereIn('id', array_keys($submittedAttrValues))->get();
            foreach ($attributesList as $attr) {
                $variantTypes[$attr->name] = $submittedAttrValues[$attr->id] ?? [];
            }
        }
        $validated['variant_types'] = $variantTypes;

        unset($validated['product_attributes'], $validated['product_attribute_values'], $validated['variations']);
        unset($validated['images'], $validated['card_image'], $validated['card_hover_image'], $validated['tag_images']);
        $this->content->unsetTransformSectionFields($validated);

        $product = Product::create($validated);
        $this->content->storeProductCardImages($request, $product);
        $this->content->saveTransformSectionContent($request, $product);
        $this->content->saveBenefitsCardsContent($request, $product);
        $this->content->saveProblemSectionContent($request, $product);

        $product->update(['tags' => $this->content->prepareProductTags($request)]);

        if ($hasVariations) {
            $this->variants->syncProductVariations($product, $variations);
        } else {
            $variant = $this->variants->ensureSimpleVariant($product);

            Inventory::create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'track_stock' => (bool) ($request->track_stock ?? true),
                'stock_qty' => $parentStockQty,
                'is_in_stock' => $parentStockQty > 0,
            ]);
        }

        Inventory::create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'track_stock' => (bool) ($request->track_stock ?? true),
            'stock_qty' => $parentStockQty,
            'is_in_stock' => $parentStockQty > 0,
        ]);

        // Handle Image Uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                \App\Models\ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'is_primary' => $index === 0,
                    'sort_order' => $index,
                ]);
            }
        }

        $this->forgetStorefrontCatalogCache();

        return redirect()->route('admin.ecommerce.products.index')->with('success', 'Product created successfully.');
    }


    public function updateProblemSolution(Request $request, Product $product, array $validated): RedirectResponse
    {
        $validated = $request->validate([
            'ps_brand_title' => ['nullable', 'string', 'max:255'],
            'ps_product_title' => ['nullable', 'string', 'max:255'],
            'ps_tagline_items' => ['nullable', 'array'],
            'ps_tagline_items.*' => ['nullable', 'string', 'max:120'],
            'ps_left_label' => ['nullable', 'string', 'max:255'],
            'ps_left_cards' => ['nullable', 'array'],
            'ps_left_cards.*.title' => ['nullable', 'string', 'max:120'],
            'ps_left_cards.*.text' => ['nullable', 'string', 'max:255'],
            'ps_left_cards.*.icon' => ['nullable', 'string', 'max:255'],
            'ps_left_card_images' => ['nullable', 'array'],
            'ps_left_card_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'ps_center_image' => ['nullable', 'string', 'max:255'],
            'ps_center_image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'ps_right_label' => ['nullable', 'string', 'max:255'],
            'ps_right_cards' => ['nullable', 'array'],
            'ps_right_cards.*.title' => ['nullable', 'string', 'max:120'],
            'ps_right_cards.*.text' => ['nullable', 'string', 'max:255'],
            'ps_right_cards.*.icon' => ['nullable', 'string', 'max:255'],
            'ps_right_card_images' => ['nullable', 'array'],
            'ps_right_card_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'ps_shelf_left_image' => ['nullable', 'string', 'max:255'],
            'ps_shelf_left_image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'ps_shelf_right_image' => ['nullable', 'string', 'max:255'],
            'ps_shelf_right_image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $updates = [
            'ps_brand_title' => trim((string) ($validated['ps_brand_title'] ?? '')) ?: null,
            'ps_product_title' => trim((string) ($validated['ps_product_title'] ?? '')) ?: null,
            'ps_tagline_items' => $this->content->prepareProblemSolutionTagline($validated['ps_tagline_items'] ?? []),
            'ps_left_label' => trim((string) ($validated['ps_left_label'] ?? '')) ?: null,
            'ps_left_cards' => $this->content->prepareProblemSolutionCards($request, 'ps_left_cards', 'ps_left_card_images'),
            'ps_center_image' => $validated['ps_center_image'] ?? null,
            'ps_right_label' => trim((string) ($validated['ps_right_label'] ?? '')) ?: null,
            'ps_right_cards' => $this->content->prepareProblemSolutionCards($request, 'ps_right_cards', 'ps_right_card_images'),
            'ps_shelf_left_image' => $validated['ps_shelf_left_image'] ?? null,
            'ps_shelf_right_image' => $validated['ps_shelf_right_image'] ?? null,
        ];

        $this->content->replaceProblemSolutionImage($request, $product, $updates, 'ps_center_image', 'ps_center_image_file');
        $this->content->replaceProblemSolutionImage($request, $product, $updates, 'ps_shelf_left_image', 'ps_shelf_left_image_file');
        $this->content->replaceProblemSolutionImage($request, $product, $updates, 'ps_shelf_right_image', 'ps_shelf_right_image_file');

        $product->forceFill($updates)->save();
        $this->forgetStorefrontCatalogCache();

        $redirectRoute = $request->boolean('return_to_hub')
            ? route('admin.ecommerce.products.problem-solution.index', ['product_id' => $product->id])
            : route('admin.ecommerce.products.problem-solution.edit', $product);

        return redirect($redirectRoute)->with('success', 'Problem solution section updated successfully.');
    }


    public function update(Request $request, Product $product, array $validated): RedirectResponse
    {
        

        $variations = $this->variants->normalizedVariations($validated['variations'] ?? []);
        $this->variants->validateVariationSkus($variations, $product);
        $hasVariations = ! empty($variations);
        $parentStockQty = $hasVariations
            ? collect($variations)->sum(fn ($variation) => (int) ($variation['stock_qty'] ?? 0))
            : (int) $request->input('stock_qty', 0);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['sku'] = $this->variants->uniqueProductSku($validated['sku'] ?? null, $validated['name'], $product->id);
        $validated['currency'] = 'INR';
        $validated['product_type'] = $hasVariations ? 'variable' : 'simple';
        $validated['is_variant_enabled'] = $hasVariations;
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['is_featured'] = (bool) ($validated['is_featured'] ?? false);
        $variantTypes = [];
        $submittedAttrValues = $request->input('product_attribute_values') ?? [];
        if (!empty($submittedAttrValues)) {
            $attributesList = \App\Models\Attribute::whereIn('id', array_keys($submittedAttrValues))->get();
            foreach ($attributesList as $attr) {
                $variantTypes[$attr->name] = $submittedAttrValues[$attr->id] ?? [];
            }
        }
        $validated['variant_types'] = $variantTypes;

        unset($validated['product_attributes'], $validated['product_attribute_values'], $validated['variations']);
        unset($validated['images'], $validated['card_image'], $validated['card_hover_image'], $validated['tag_images']);
        $this->content->unsetProblemSolutionFields($validated);
        $this->content->unsetTransformSectionFields($validated);

        $product->update($validated);
        $this->content->storeProductCardImages($request, $product);

        $product->update(['tags' => $this->content->prepareProductTags($request)]);
        $this->content->saveProblemSolutionContent($request, $product);
        $this->content->saveTransformSectionContent($request, $product);
        $this->content->saveBenefitsCardsContent($request, $product);
        $this->content->saveProblemSectionContent($request, $product);

        if ($hasVariations) {
            $this->variants->syncProductVariations($product, $variations, true);
        } else {
            $this->variants->deactivateMissingProductVariations($product);
            $variant = $this->variants->ensureSimpleVariant($product);

            Inventory::updateOrCreate(
                ['product_variant_id' => $variant->id],
                [
                    'product_id' => $product->id,
                    'track_stock' => (bool) ($request->track_stock ?? true),
                    'stock_qty' => $parentStockQty,
                    'is_in_stock' => $parentStockQty > 0,
                ]
            );
        }

        Inventory::updateOrCreate(
            ['product_id' => $product->id, 'product_variant_id' => null],
            [
                'track_stock' => (bool) ($request->track_stock ?? true),
                'stock_qty' => $parentStockQty,
                'is_in_stock' => $parentStockQty > 0,
            ]
        );

        // Handle Additional Image Uploads
        if ($request->hasFile('images')) {
            $lastSortOrder = $product->images()->max('sort_order') ?? -1;
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                \App\Models\ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'is_primary' => !$product->images()->where('is_primary', true)->exists() && $index === 0,
                    'sort_order' => $lastSortOrder + $index + 1,
                ]);
            }
        }

        $this->forgetStorefrontCatalogCache();

        return redirect()->route('admin.ecommerce.products.index')->with('success', 'Product updated successfully.');
    }


    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();
        $this->forgetStorefrontCatalogCache();

        return back()->with('success', 'Product moved to trash successfully.');
    }


    public function restore(int $product): RedirectResponse
    {
        $trashedProduct = Product::onlyTrashed()->findOrFail($product);
        $trashedProduct->restore();
        $this->forgetStorefrontCatalogCache();

        return back()->with('success', 'Product restored successfully.');
    }


    public function forceDestroy(int $product): RedirectResponse
    {
        $trashedProduct = Product::onlyTrashed()->with(['images'])->findOrFail($product);

        $this->permanentlyDeleteProduct($trashedProduct);
        $this->forgetStorefrontCatalogCache();

        return back()->with('success', 'Product permanently deleted successfully.');
    }


    public function bulkForceDestroy(array $validated): RedirectResponse
    {
        

        $products = Product::onlyTrashed()
            ->with(['images'])
            ->whereIn('id', $validated['product_ids'])
            ->get();

        foreach ($products as $product) {
            $this->permanentlyDeleteProduct($product);
        }
        $this->forgetStorefrontCatalogCache();

        return back()->with('success', $products->count() . ' product(s) permanently deleted successfully.');
    }


    private function permanentlyDeleteProduct(Product $product): void
    {
        if ($product->card_image_path) {
            Storage::disk('public')->delete($product->card_image_path);
        }

        if ($product->card_hover_image_path) {
            Storage::disk('public')->delete($product->card_hover_image_path);
        }

        $this->content->deleteTransformImage($product->transform_main_image);
        collect($product->transform_results ?? [])
            ->pluck('image')
            ->each(fn ($path) => $this->content->deleteTransformImage($path));

        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $product->forceDelete();
    }


    public function updateInventory(Product $product, array $validated): RedirectResponse
    {
        

        Inventory::updateOrCreate(
            ['product_id' => $product->id, 'product_variant_id' => null],
            [
                'track_stock' => (bool) ($validated['track_stock'] ?? false),
                'stock_qty' => $validated['stock_qty'],
                'reserved_qty' => $validated['reserved_qty'] ?? 0,
                'low_stock_threshold' => $validated['low_stock_threshold'] ?? 5,
                'is_in_stock' => (bool) ($validated['is_in_stock'] ?? false),
            ]
        );
        $this->forgetStorefrontCatalogCache();

        return back()->with('success', 'Product inventory updated successfully.');
    }


    public function deleteImage(\App\Models\ProductImage $image): RedirectResponse
    {
        Storage::disk('public')->delete($image->image_path);

        $image->delete();
        $this->forgetStorefrontCatalogCache();

        return back()->with('success', 'Image removed successfully.');
    }


    private function forgetStorefrontCatalogCache(): void
    {
        Cache::forget('storefront.product_catalog_meta.v1');
        Cache::forget('storefront.product_catalog_meta.v2');
    }
}
