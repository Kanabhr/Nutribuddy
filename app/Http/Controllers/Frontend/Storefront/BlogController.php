<?php

namespace App\Http\Controllers\Frontend\Storefront;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;

class BlogController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $query = BlogPost::with(['category', 'author'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at');

        $activeCategory = $request->query('category', 'all');
        $activeTag = $request->query('tag');

        if (!empty($activeCategory) && $activeCategory !== 'all') {
            $query->whereHas('category', function ($q) use ($activeCategory) {
                $q->where('slug', $activeCategory)
                    ->orWhere('name', $activeCategory)
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($activeCategory)]);
            });
        }

        if (!empty($activeTag)) {
            $query->where(function ($q) use ($activeTag) {
                $q->where('tags', 'LIKE', '%' . $activeTag . '%');
            });
        }

        $backendBlogPosts = $query->paginate(8);

        $totalPublishedPosts = BlogPost::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->count();

        $categories = \App\Models\BlogCategory::where('is_active', true)
            ->withCount(['posts' => function ($q) {
                $q->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
            }])
            ->orderBy('name')
            ->get();

        $allTags = BlogPost::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('tags')
            ->where('tags', '!=', '')
            ->pluck('tags')
            ->flatMap(function ($tagString) {
                return array_filter(array_map('trim', explode(',', $tagString)));
            })
            ->unique()
            ->values()
            ->all();

        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'html' => view('frontend.storefront.blog.partials.posts-grid', compact('backendBlogPosts'))->render(),
                'total' => $backendBlogPosts->total(),
                'activeCategory' => $activeCategory,
                'activeTag' => $activeTag,
            ]);
        }

        return view('frontend.storefront.blog.index', compact('backendBlogPosts', 'categories', 'totalPublishedPosts', 'activeCategory', 'allTags', 'activeTag'));
    }

    public function show(string|int $id)
    {
        $blogPost = BlogPost::with(['category', 'author'])
            ->where(function ($q) use ($id) {
                $q->where('slug', $id);
                if (is_numeric($id)) {
                    $q->orWhere('id', (int) $id);
                }
            })
            ->firstOrFail();

        if ($blogPost->status !== 'published' || ($blogPost->published_at && $blogPost->published_at->isFuture())) {
            if (!auth('admin')->check()) {
                abort(404);
            }
        }
        
        $relatedBlogs = BlogPost::with('category')
            ->when($blogPost->blog_category_id, function ($q) use ($blogPost) {
                $q->where('blog_category_id', $blogPost->blog_category_id);
            }, function ($q) {
                $q->whereNull('blog_category_id');
            })
            ->where('id', '!=', $blogPost->id)
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take(3)
            ->get();
            
        $recentBlogs = BlogPost::with('category')
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('id', '!=', $blogPost->id)
            ->latest('id')
            ->take(4)
            ->get();
            
        $popularBlogs = BlogPost::with('category')
            ->where('status', 'published')
            ->where('id', '!=', $blogPost->id)
            ->inRandomOrder()
            ->take(4)
            ->get();
            
        return view('frontend.storefront.blog.show', compact('blogPost', 'relatedBlogs', 'recentBlogs', 'popularBlogs'));
    }

}
