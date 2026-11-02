<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ecommerce\StoreProductVariantRequest;
use App\Http\Requests\Admin\Ecommerce\UpdateProductVariantInventoryRequest;
use App\Http\Requests\Admin\Ecommerce\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Ecommerce\ProductVariantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductVariantController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;

    public function index(): View
    {
        return view('admin.ecommerce.variants.index', [
            'variants' => ProductVariant::with(['product', 'inventory'])->latest()->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreProductVariantRequest $request, ProductVariantService $variants): RedirectResponse
    {
        $variants->create($request->validated());

        return back()->with('success', 'Variant created successfully.');
    }

    public function update(UpdateProductVariantRequest $request, ProductVariant $variant, ProductVariantService $variants): RedirectResponse
    {
        $variants->update($variant, $request->validated(), $request->has('stock_qty'));

        return back()->with('success', 'Variant updated successfully.');
    }

    public function updateInventory(UpdateProductVariantInventoryRequest $request, ProductVariant $variant, ProductVariantService $variants): RedirectResponse
    {
        $variants->updateInventory($variant, $request->validated());

        return back()->with('success', 'Variant inventory updated successfully.');
    }

    public function destroy(ProductVariant $variant, ProductVariantService $variants): RedirectResponse
    {
        $variants->delete($variant);

        return back()->with('success', 'Variant deleted successfully.');
    }
}
