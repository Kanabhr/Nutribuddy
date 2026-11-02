<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Admin\Content\BlogPostRequest;
use App\Http\Requests\Admin\Content\BulkDeleteBlogPostsRequest;
use Illuminate\View\View;
use App\Services\Content\BlogPostManagementService;

class BlogPostController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    public function index(): View
    {
        return view('admin.content.blog-posts.index', [
            'posts' => BlogPost::with(['category', 'author'])->latest()->get(),
            'trashCount' => BlogPost::onlyTrashed()->count(),
            'categories' => BlogCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'users' => User::where('role', 'admin')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('admin.content.blog-posts.create', [
            'categories' => BlogCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'users' => User::where('role', 'admin')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(BlogPost $blogPost): View
    {
        return view('admin.content.blog-posts.edit', [
            'post' => $blogPost,
            'categories' => BlogCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'users' => User::where('role', 'admin')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function trash(): View
    {
        return view('admin.content.blog-posts.trash', [
            'posts' => BlogPost::onlyTrashed()->with(['category', 'author'])->latest('deleted_at')->get(),
            'activeCount' => BlogPost::count(),
        ]);
    }

    public function store(BlogPostRequest $request, BlogPostManagementService $posts): RedirectResponse
    {
        $posts->create($request->validated(), $request->file('featured_image'));

        return redirect()->route('admin.ecommerce.blog-posts.index')->with('success', 'Blog post created successfully.');
    }

    public function update(BlogPostRequest $request, BlogPost $blogPost, BlogPostManagementService $posts): RedirectResponse
    {
        $posts->update($blogPost, $request->validated(), $request->file('featured_image'));

        return back()->with('success', 'Blog post updated successfully.');
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        $blogPost->delete();

        return back()->with('success', 'Blog post moved to trash successfully.');
    }

    public function restore(int $blogPost): RedirectResponse
    {
        $trashedPost = BlogPost::onlyTrashed()->findOrFail($blogPost);
        $trashedPost->restore();

        return back()->with('success', 'Blog post restored successfully.');
    }

    public function forceDestroy(int $blogPost, BlogPostManagementService $posts): RedirectResponse
    {
        $trashedPost = BlogPost::onlyTrashed()->findOrFail($blogPost);
        $posts->forceDelete($trashedPost);

        return back()->with('success', 'Blog post permanently deleted successfully.');
    }

    public function bulkForceDestroy(BulkDeleteBlogPostsRequest $request, BlogPostManagementService $postManagement): RedirectResponse
    {
        $validated = $request->validated();

        $posts = BlogPost::onlyTrashed()
            ->whereIn('id', $validated['post_ids'])
            ->get();

        foreach ($posts as $post) {
            $postManagement->forceDelete($post);
        }

        return back()->with('success', $posts->count() . ' blog post(s) permanently deleted successfully.');
    }

}
