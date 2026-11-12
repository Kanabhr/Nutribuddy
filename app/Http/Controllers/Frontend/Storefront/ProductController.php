<?php

namespace App\Http\Controllers\Frontend\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Storefront\ProductReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->integer('per_page', 24), 12), 48);

        $catalogMeta = Cache::remember('storefront.product_catalog_meta.v3', now()->addMinutes(10), function () {
            $products = Product::query()
                ->where('is_active', true)
                ->with([
                    'category:id,name,slug,deleted_at',
                    'taxRate:id,rate,show_in_checkout',
                    'variants' => fn ($query) => $query
                        ->select(['id', 'product_id', 'price', 'compare_at_price', 'is_default', 'is_active', 'position'])
                        ->where('is_active', true)
                        ->orderBy('position')
                        ->orderBy('id'),
                ])
                ->select(['id', 'category_id', 'base_price', 'tax_rate_id', 'is_variant_enabled'])
                ->get();

            $categoryCounts = $products
                ->groupBy(fn ($item) => $item->category->slug ?? 'uncategorized')
                ->map(fn ($items, $slug) => [
                    'slug' => $slug,
                    'name' => $items->first()->category->name ?? 'Uncategorized',
                    'count' => $items->count(),
                ])
                ->sortBy('name')
                ->values();

            // Calculate prices using the same logic as cards: default variant price
            $prices = $products->map(function ($item) {
                if ($item->is_variant_enabled && $item->variants->isNotEmpty()) {
                    $variant = $item->variants->firstWhere('is_default', true) ?: $item->variants->first();
                    return (float) ($variant?->display_price ?? $item->display_price);
                }
                return (float) $item->display_price;
            });

            return [
                'categoryCounts' => $categoryCounts,
                'totalProducts' => $products->count(),
                'minPrice' => max(0, (int) floor($prices->min() ?? 0)),
                'maxPrice' => max(0, (int) ceil($prices->max() ?? 0)),
            ];
        });

        $products = Product::where('is_active', true)
            ->select([
                'id',
                'category_id',
                'tax_rate_id',
                'name',
                'slug',
                'sku',
                'short_description',
                'base_price',
                'compare_at_price',
                'shipping_price',
                'is_active',
                'is_featured',
                'flavor',
                'pack_size',
                'age_group',
                'dosage',
                'card_image_path',
                'card_hover_image_path',
                'tags',
                'deleted_at',
                'is_variant_enabled',
            ])
            ->with([
                'primaryImage:id,product_id,image_path,is_primary',
                'category:id,name,slug,deleted_at',
                'images:id,product_id,image_path,is_primary,sort_order',
                'taxRate:id,rate,show_in_checkout',
                'variants' => fn ($query) => $query
                    ->select(['id', 'product_id', 'name', 'sku', 'attributes', 'price', 'compare_at_price', 'is_default', 'is_active', 'position', 'image_path'])
                    ->where('is_active', true)
                    ->with(['inventory:id,product_variant_id,stock_qty,track_stock,is_in_stock'])
                    ->orderBy('position')
                    ->orderBy('id'),
            ])
            ->withCount(['reviews' => fn ($query) => $query->where('is_active', true)])
            ->withAvg(['reviews' => fn ($query) => $query->where('is_active', true)], 'rating')
            ->orderByDesc('is_featured')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()
            ->view('pages.all-products', [
                'products' => $products,
                'categoryCounts' => $catalogMeta['categoryCounts'],
                'totalProducts' => $catalogMeta['totalProducts'],
                'minPrice' => $catalogMeta['minPrice'],
                'maxPrice' => max($catalogMeta['minPrice'], $catalogMeta['maxPrice']),
            ]);
    }

    public function show($slug)
    {
        $product = Product::with([
            'category',
            'taxRate',
            'images',
            'variants.inventory',
            'variants.product.taxRate',
            'reviews.user',
            'ingredients' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id'),
            'ingredients.benefits',
            'ingredients.category',
        ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->with(['primaryImage', 'category', 'images', 'reviews', 'taxRate', 'variants.inventory', 'variants.product.taxRate'])
            ->limit(8)
            ->get();

        if ($relatedProducts->count() < 8) {
            $fallbackProducts = Product::where('id', '!=', $product->id)
                ->where('is_active', true)
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->with(['primaryImage', 'category', 'images', 'reviews', 'taxRate', 'variants.inventory', 'variants.product.taxRate'])
                ->limit(8 - $relatedProducts->count())
                ->get();

            $relatedProducts = $relatedProducts->concat($fallbackProducts);
        }
        // Review box state: guest | not_purchased | already_reviewed | can_review
        $reviewState = 'guest';
        $userReview = null;
        if (auth()->check()) {
            $reviewService = app(ProductReviewService::class);
            $userReview = $reviewService->existingReview($product, (int) auth()->id());
            if ($userReview) {
                $reviewState = 'already_reviewed';
            } elseif ($reviewService->hasPurchased($product, (int) auth()->id())) {
                $reviewState = 'can_review';
            } else {
                $reviewState = 'not_purchased';
            }
        }

        return response()
            ->view('pages.product', compact('product', 'relatedProducts', 'reviewState', 'userReview'));
    }
}
