<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ecommerce\BulkDeleteProductsRequest;
use App\Http\Requests\Admin\Ecommerce\StoreProductRequest;
use App\Http\Requests\Admin\Ecommerce\UpdateProductInventoryRequest;
use App\Http\Requests\Admin\Ecommerce\UpdateProductProblemSolutionRequest;
use App\Http\Requests\Admin\Ecommerce\UpdateProductRequest;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\TaxRate;
use App\Services\Ecommerce\ProductContentService;
use App\Services\Ecommerce\ProductManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;

    public function index(Request $request): View
    {
        $query = Product::with(['category', 'taxRate', 'variants', 'inventory', 'images']);
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return view('admin.ecommerce.products.index', [
            'products' => $query->latest()->get(),
            'trashCount' => Product::onlyTrashed()->count(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'taxRates' => TaxRate::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'rate']),
        ]);
    }

    public function trash(Request $request): View
    {
        $query = Product::onlyTrashed()->with(['category', 'taxRate', 'variants', 'inventory', 'images']);
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return view('admin.ecommerce.products.trash', [
            'products' => $query->latest('deleted_at')->get(),
            'activeCount' => Product::count(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('admin.ecommerce.products.create', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'taxRates' => TaxRate::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'rate']),
            'attributes' => Attribute::where('is_active', true)->orderBy('position')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request, ProductManagementService $products): RedirectResponse
    {
        return $products->store($request, $request->validated());
    }

    public function edit(Product $product, ProductContentService $content): View
    {
        return view('admin.ecommerce.products.edit', [
            'product' => $product,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'taxRates' => TaxRate::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'rate']),
            'attributes' => Attribute::where('is_active', true)->orderBy('position')->orderBy('name')->get(),
            'problemSolutionDefaults' => $content->defaultProblemSolutionContent(),
        ]);
    }

    public function problemSolutionIndex(Request $request, ProductContentService $content): View
    {
        $allProducts = Product::with(['category', 'images'])->latest()->get();
        $product = $request->filled('product_id')
            ? $allProducts->firstWhere('id', (int) $request->product_id)
            : $allProducts->first();
        if (! $product) {
            $product = $allProducts->first();
        }

        return view('admin.ecommerce.products.problem-solution', [
            'product' => $product,
            'products' => $allProducts,
            'defaults' => $content->defaultProblemSolutionContent(),
            'isProblemSolutionHub' => true,
        ]);
    }

    public function problemSolution(Product $product, ProductContentService $content): View
    {
        return view('admin.ecommerce.products.problem-solution', [
            'product' => $product,
            'defaults' => $content->defaultProblemSolutionContent(),
        ]);
    }

    public function updateProblemSolution(UpdateProductProblemSolutionRequest $request, Product $product, ProductManagementService $products): RedirectResponse
    {
        return $products->updateProblemSolution($request, $product, $request->validated());
    }

    public function update(UpdateProductRequest $request, Product $product, ProductManagementService $products): RedirectResponse
    {
        return $products->update($request, $product, $request->validated());
    }

    public function destroy(Product $product, ProductManagementService $products): RedirectResponse
    {
        return $products->destroy($product);
    }

    public function restore(int $product, ProductManagementService $products): RedirectResponse
    {
        return $products->restore($product);
    }

    public function forceDestroy(int $product, ProductManagementService $products): RedirectResponse
    {
        return $products->forceDestroy($product);
    }

    public function bulkForceDestroy(BulkDeleteProductsRequest $request, ProductManagementService $products): RedirectResponse
    {
        return $products->bulkForceDestroy($request->validated());
    }

    public function updateInventory(UpdateProductInventoryRequest $request, Product $product, ProductManagementService $products): RedirectResponse
    {
        return $products->updateInventory($product, $request->validated());
    }

    public function deleteImage(ProductImage $image, ProductManagementService $products): RedirectResponse
    {
        return $products->deleteImage($image);
    }
}
