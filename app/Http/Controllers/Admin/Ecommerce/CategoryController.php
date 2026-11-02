<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ecommerce\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    public function index(): View
    {
        return view('admin.ecommerce.categories.index', [
            'categories' => Category::with('parent')->latest()->get(),
            'parentCategories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        Category::create($validated);
        Cache::forget('storefront.product_catalog_meta.v1');
        Cache::forget('storefront.product_catalog_meta.v2');

        return back()->with('success', 'Category created successfully.');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $validated = $request->validated();

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        $category->update($validated);
        Cache::forget('storefront.product_catalog_meta.v1');
        Cache::forget('storefront.product_catalog_meta.v2');

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists() || $category->products()->exists()) {
            return back()->with('error', 'Cannot delete category with child categories or products.');
        }

        $category->delete();
        Cache::forget('storefront.product_catalog_meta.v1');
        Cache::forget('storefront.product_catalog_meta.v2');

        return back()->with('success', 'Category deleted successfully.');
    }
}
