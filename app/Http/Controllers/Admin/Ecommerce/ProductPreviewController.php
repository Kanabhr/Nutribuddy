<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Response;

/**
 * Point 37: admin preview of a SAVED product, rendered with the same view the shop uses (pages.product),
 * including products that are still inactive. It shows saved data only, not unsaved form edits.
 * The storefront page itself is not touched; a banner and a noindex tag are added to this response only.
 */
class ProductPreviewController extends Controller
{
    public function show(Product $product): Response
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
        ])->findOrFail($product->id);

        // Same "related products" logic as the shop page.
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

        $reviewState = 'guest';
        $userReview = null;

        $html = view('pages.product', compact('product', 'relatedProducts', 'reviewState', 'userReview'))->render();

        $status = $product->is_active ? 'Live' : 'Inactive: customers cannot see this page yet';
        $banner = '<div style="position:fixed;top:0;left:0;right:0;z-index:2147483647;background:#1f2a44;color:#fff;font:600 13px/1.4 system-ui,sans-serif;padding:8px 14px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.25)">'
            . 'PREVIEW (admin only) &middot; ' . e($product->name) . ' &middot; ' . e($status)
            . ' &middot; shows saved data, save your edits first</div><div style="height:34px"></div>';

        $withBanner = preg_replace('/<body([^>]*)>/i', '<body$1>' . $banner, $html, 1, $count);
        $html = $count > 0 ? $withBanner : $banner . $html;
        $html = preg_replace('/<\/head>/i', '<meta name="robots" content="noindex, nofollow"></head>', $html, 1);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}