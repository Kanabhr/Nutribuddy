<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Admin\Content\BlogCategoryRequest;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogCategoryController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    public function index(): View
    {
        return view('admin.content.blog-categories.index', [
            'categories' => BlogCategory::withCount('posts')->latest()->get(),
        ]);
    }

    public function store(BlogCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        BlogCategory::create($validated);

        return back()->with('success', 'Blog category created successfully.');
    }

    public function update(BlogCategoryRequest $request, BlogCategory $blogCategory): RedirectResponse
    {
        $validated = $request->validated();

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        $blogCategory->update($validated);

        return back()->with('success', 'Blog category updated successfully.');
    }

    public function destroy(BlogCategory $blogCategory): RedirectResponse
    {
        if ($blogCategory->posts()->exists()) {
            return back()->with('error', 'Cannot delete category that has blog posts.');
        }

        $blogCategory->delete();

        return back()->with('success', 'Blog category deleted successfully.');
    }
}
