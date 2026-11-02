# NutriBuddy Blog System - Changelog & Code Comparisons

This document lists all files modified or added during the Blog System enhancement. For each file, you will find the **Old Code**, the **Updated Code**, and an explanation of the changes made.

---

## Table of Contents
1. [Database Migration](#1-database-migration)
2. [Model: BlogPost.php](#2-model-appmodelsblogpostphp)
3. [Form Request: BlogPostRequest.php](#3-form-request-apphttprequestsadmincontentblogpostrequestphp)
4. [Service: BlogPostManagementService.php](#4-service-appservicescontentblogpostmanagementservicephp)
5. [Admin View: create.blade.php](#5-admin-view-resourcesviewsadmincontentblog-postscreatebladephp)
6. [Admin View: edit.blade.php](#6-admin-view-resourcesviewsadmincontentblog-postseditbladephp)
7. [Admin View: index.blade.php](#7-admin-view-resourcesviewsadmincontentblog-postsindexbladephp)
8. [Layout: layouts/main.blade.php](#8-layout-resourcesviewslayoutsmainbladephp)
9. [Frontend View: blog/show.blade.php](#9-frontend-view-resourcesviewsfrontendstorefrontblogshowbladephp)
10. [Frontend View: blog/index.blade.php](#10-frontend-view-resourcesviewsfrontendstorefrontblogindexbladephp)
11. [Frontend Controller: BlogController.php](#11-frontend-controller-apphttpcontrollersfrontendstorefrontblogcontrollerphp)
12. [Bugfix: UserInvoiceController.php](#12-bugfix-apphttpcontrollersfrontendaccountuserinvoicecontrollerphp)

---

## 1. Database Migrations
**File 1:** `database/migrations/2026_11_02_151029_add_author_name_and_tags_to_blog_posts_table.php`  
**Purpose:** Adds `author_name` (custom author string) and `tags` (comma-separated tags) to `blog_posts` table.

```php
public function up(): void
{
    Schema::table('blog_posts', function (Blueprint $table) {
        $table->string('author_name')->nullable()->after('author_id');
        $table->text('tags')->nullable()->after('meta_keywords');
    });
}
```

**File 2:** `database/migrations/2026_11_02_161422_add_short_description_to_blog_posts_table.php`  
**Purpose:** Adds `short_description` (text, nullable) for blog listing cards.

```php
public function up(): void
{
    Schema::table('blog_posts', function (Blueprint $table) {
        $table->text('short_description')->nullable()->after('slug');
    });
}
```

---

## 2. Model: `app/Models/BlogPost.php`
**Purpose:** Added `author_name`, `tags`, and `short_description` to `$fillable`. Updated `getExcerptAttribute()` to return `short_description` when present, falling back to content beginning.

### Updated Code:
```php
protected $fillable = [
    'blog_category_id',
    'author_id',
    'author_name',
    'title',
    'slug',
    'short_description',
    'content',
    'featured_image',
    'status',
    'published_at',
    'meta_title',
    'meta_description',
    'meta_keywords',
    'tags',
];

public function getExcerptAttribute(): string
{
    $rawText = !empty($this->short_description)
        ? $this->short_description
        : ($this->content ?? '');

    $clean = strip_tags($rawText);
    $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $clean = str_replace(["\xc2\xa0", '&nbsp;'], ' ', $clean);
    $clean = trim(preg_replace('/\s+/', ' ', $clean));

    return \Illuminate\Support\Str::words($clean, 100, '...');
}
```

---

## 3. Form Request: `app/Http/Requests/Admin/Content/BlogPostRequest.php`
**Purpose:** Validates custom `author_name`, `tags`, and maximum 100-word `short_description`, while properly ignoring the current post ID during slug uniqueness checks.

### Updated Code:
```php
public function rules(): array
{
    $post = $this->route('blog_post') ?? $this->route('blogPost');
    $postId = $post instanceof BlogPost ? $post->id : (is_numeric($post) ? (int) $post : null);

    return [
        'blog_category_id' => ['nullable', 'exists:blog_categories,id'],
        'author_id' => ['nullable', 'exists:users,id'],
        'author_name' => ['nullable', 'string', 'max:255'],
        'title' => ['required', 'string', 'max:255'],
        'slug' => ['nullable', 'string', 'max:255', Rule::unique('blog_posts', 'slug')->ignore($postId)],
        'short_description' => [
            'nullable',
            'string',
            function ($attribute, $value, $fail) {
                $words = count(preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY));
                if ($words > 100) {
                    $fail('The short description must not exceed 100 words (currently ' . $words . ' words).');
                }
            },
        ],
        'content' => ['required', 'string'],
        'featured_image' => ['nullable', 'image', 'max:2048'],
        'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        'published_at' => ['nullable', 'date'],
        'meta_title' => ['nullable', 'string', 'max:255'],
        'meta_description' => ['nullable', 'string'],
        'meta_keywords' => ['nullable', 'string'],
        'tags' => ['nullable', 'string', 'max:500'],
    ];
}
```

---

## 4. Service: `app/Services/Content/BlogPostManagementService.php`
**Purpose:** Automatically deduplicate conflicting slugs, auto-assign logged-in admin as audit `author_id` if empty, and sanitize slugs.

### Old Code:
```php
private function prepareAttributes(array $data, ?BlogPost $post = null): array
{
    $data['slug'] = $data['slug'] ?? Str::slug($data['title']);
    if ($data['status'] === 'published' && empty($data['published_at']) && ! $post?->published_at) {
        $data['published_at'] = now();
    }

    return $data;
}
```

### Updated Code:
```php
private function prepareAttributes(array $data, ?BlogPost $post = null): array
{
    $baseSlug = !empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['title'] ?? 'post');
    $slug = $baseSlug;
    $counter = 1;
    while (BlogPost::withTrashed()
        ->where('slug', $slug)
        ->when($post, fn ($q) => $q->where('id', '!=', $post->id))
        ->exists()) {
        $slug = "{$baseSlug}-{$counter}";
        $counter++;
    }
    $data['slug'] = $slug;

    if (empty($data['author_id'])) {
        $data['author_id'] = auth('admin')->id() ?? auth()->id();
    }

    if (($data['status'] ?? '') === 'published' && empty($data['published_at']) && ! $post?->published_at) {
        $data['published_at'] = now();
    }

    return $data;
}
```

---

## 5. Admin View: `resources/views/admin/content/blog-posts/create.blade.php`
**Purpose:** 
- Replaced `<select name="author_id">` with `<input type="text" name="author_name">`.
- Added live auto-slug generation as the user types the title (with manual edit override & re-sync button).
- Added Blog Tags input field with live interactive badge preview.
- Added comprehensive SEO section (`meta_title`, `meta_keywords`, `meta_description`, character counters, auto-fill button, and live Google Search SERP snippet preview).

### Old Author Field:
```html
<div class="col-md-4">
    <label class="form-label">Author</label>
    <select name="author_id" class="form-select">
        <option value="">Select</option>
        @foreach ($users as $user)
            <option value="{{ $user->id }}" {{ old('author_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
        @endforeach
    </select>
</div>
```

### Updated Author Field:
```html
<div class="col-md-4">
    <label class="form-label fw-semibold">Author</label>
    <div class="icon-field">
        <span class="icon">
            <iconify-icon icon="lucide:user"></iconify-icon>
        </span>
        <input type="text" name="author_name" id="author_name" class="form-control" placeholder="e.g. Dr. Ananya Sharma or NutriBuddy Team" value="{{ old('author_name') }}">
    </div>
    <small class="text-secondary-light text-xs">Enter doctor, nutritionist, or author name.</small>
</div>
```

### New Tags Field:
```html
<div class="col-12">
    <label class="form-label fw-semibold">Blog Tags</label>
    <div class="icon-field">
        <span class="icon">
            <iconify-icon icon="lucide:tags"></iconify-icon>
        </span>
        <input type="text" name="tags" id="tags_input" class="form-control" placeholder="e.g. Kids Nutrition, Immunity Booster, Healthy Recipes, Digestion" value="{{ old('tags') }}">
    </div>
    <div id="tags_preview_container" class="d-flex flex-wrap gap-1 mt-2"></div>
    <small class="text-secondary-light text-xs">Comma-separated tags. They will appear as badges on the blog post.</small>
</div>
```

### New SEO & Live Google Preview Card:
```html
<div class="col-12 mt-4">
    <div class="card border border-neutral-200">
        <div class="card-header bg-neutral-50 d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
            <div class="d-flex align-items-center gap-2">
                <iconify-icon icon="lucide:search" class="text-primary-600 text-lg"></iconify-icon>
                <h6 class="mb-0 fw-bold">Search Engine Optimization (SEO) & Social Sharing</h6>
            </div>
            <button type="button" id="btn_autofill_seo" class="btn btn-sm btn-outline-primary-600 d-inline-flex align-items-center gap-1">
                <iconify-icon icon="lucide:sparkles"></iconify-icon> Auto-fill from Title & Content
            </button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold mb-0">Meta Title</label>
                        <small id="meta_title_counter" class="text-secondary-light">0 / 60</small>
                    </div>
                    <input type="text" name="meta_title" id="meta_title" class="form-control" placeholder="Catchy title for Google search..." value="{{ old('meta_title') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Meta Keywords</label>
                    <input type="text" name="meta_keywords" id="meta_keywords" class="form-control" placeholder="kids nutrition, baby food, healthy gut" value="{{ old('meta_keywords') }}">
                </div>
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold mb-0">Meta Description</label>
                        <small id="meta_desc_counter" class="text-secondary-light">0 / 160</small>
                    </div>
                    <textarea name="meta_description" id="meta_description" class="form-control" rows="3" placeholder="Compelling 150-160 character summary for search engine results...">{{ old('meta_description') }}</textarea>
                </div>
                {{-- Live Google Search Snippet Preview --}}
                <div class="col-12 mt-3">
                    <label class="form-label fw-semibold text-dark">Google Search Result Preview</label>
                    <div class="serp-preview-box">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 13px;">🥗</span>
                            <div>
                                <div class="serp-url fw-medium">NutriBuddy Kids</div>
                                <div class="serp-url text-muted">{{ url('/blog') }}/<span id="serp_slug">post-slug</span></div>
                            </div>
                        </div>
                        <div class="serp-title" id="serp_title">Blog Title — NutriBuddy Kids</div>
                        <div class="serp-desc" id="serp_desc">Write a meta description or blog content to preview how this article will look on Google search results.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Standard Action Buttons --}}
<div class="col-12 mt-4 pt-3 border-top">
    <div class="d-flex align-items-center justify-content-end gap-3">
        <a href="{{ route('admin.ecommerce.blog-posts.index') }}" class="btn btn-light px-32 fw-bold">Cancel</a>
        <button type="submit" class="btn btn-primary-600 px-40 fw-bold d-inline-flex align-items-center gap-2">
            <iconify-icon icon="lucide:check-circle" style="font-size: 18px; line-height: 1;"></iconify-icon>
            <span>Create & Publish Post</span>
        </button>
    </div>
</div>
```

- **Removed "Sync Slug" button:** Cleaned up the URL Slug label to only show `URL Slug` with auto-generation from Title, removing the redundant manual sync button.

---

## 6. Admin View: `resources/views/admin/content/blog-posts/edit.blade.php`
**Purpose:** Mirrors all improvements from `create.blade.php` (author input, live slug, tags chips, SEO card with live Google preview), populated with existing `$post` data, plus added a **"View on Storefront"** header link directly to `route('blog.show', $post->id)`.

### Header Additions:
```html
<a href="{{ route('blog.show', $post->id) }}" target="_blank" class="btn btn-sm btn-outline-info-600 d-inline-flex align-items-center gap-1">
    <iconify-icon icon="lucide:external-link"></iconify-icon> View on Storefront
</a>
```

---

## 7. Admin View: `resources/views/admin/content/blog-posts/index.blade.php`
**Purpose:** Uses `$post->display_author` in table instead of hardcoded author relationship, and adds a "View" action button linking directly to the public blog article.

### Old Code:
```blade
<td><span class="text-sm text-secondary-light fw-medium">{{ $post->author?->name ?? 'Admin' }}</span></td>
...
<td class="text-end">
    <div class="d-flex align-items-center justify-content-end gap-2">
        <a href="{{ route('admin.ecommerce.blog-posts.edit', $post) }}" class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1">
            <iconify-icon icon="lucide:edit"></iconify-icon> Edit
        </a>
```

### Updated Code:
```blade
<td><span class="text-sm text-secondary-light fw-medium">{{ $post->display_author }}</span></td>
...
<td class="text-end">
    <div class="d-flex align-items-center justify-content-end gap-2">
        <a href="{{ route('blog.show', $post->id) }}" target="_blank" class="btn btn-sm btn-outline-info-600 radius-8 d-inline-flex align-items-center gap-1" title="View on Storefront">
            <iconify-icon icon="lucide:external-link"></iconify-icon> View
        </a>
        <a href="{{ route('admin.ecommerce.blog-posts.edit', $post) }}" class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1">
            <iconify-icon icon="lucide:edit"></iconify-icon> Edit
        </a>
```

---

## 8. Layout: `resources/views/layouts/main.blade.php`
**Purpose:** Added `@stack('meta')` in `<head>` so individual pages can inject SEO and Social Sharing meta tags.

### Old Code:
```blade
    <title>@yield('title', 'NutriBuddy')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
```

### Updated Code:
```blade
    <title>@yield('title', 'NutriBuddy')</title>
    @stack('meta')

    <link rel="preconnect" href="https://fonts.googleapis.com">
```

---

## 9. Frontend View: `resources/views/frontend/storefront/blog/show.blade.php`
**Purpose:** 
- Injects dynamic SEO Meta tags (`meta description`, `meta keywords`, canonical link, OpenGraph tags, Twitter Cards).
- Relocated Author, Date, and Read Time from the hero/breadcrumb header to a sleek meta bar placed **right after the featured image** (above the article content).
- Renders tags badges (`#tag`) below article content.
- Expanded container width (`.blog-content-wrapper`, `.blog-detail-header`, and `.blog-footer` to `width: 100%; padding: 0 5%;`) to align with the edge-to-edge width of the parent video reviews section below.

### Author & Meta Bar (Moved After Blog Image):
```blade
<!-- Hero Header (Clean without author/date bar) -->
<section class="blog-detail-hero">
    <div class="blog-detail-header">
        <div class="blog-detail-breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span>/</span>
            <a href="{{ route('blog') }}">Blog</a>
            <span>/</span>
            <span>{{ html_entity_decode($blogPost->title) }}</span>
        </div>
        <span class="blog-detail-category">{{ $categoryName }}</span>
        <h1 class="blog-detail-title">{{ html_entity_decode($blogPost->title) }}</h1>
        @if(!empty($blogPost->excerpt))
            <p class="blog-detail-subtitle">{{ $blogPost->excerpt }}</p>
        @endif
    </div>
</section>

<!-- Content Area: Meta bar placed after the featured image -->
<article class="blog-article">
    @if($imageUrl)
        <div class="blog-featured-image">
            <img src="{{ $imageUrl }}" alt="{{ html_entity_decode($blogPost->title) }}">
        </div>
    @endif

    <!-- Author & Article Meta Info (Placed After Blog Image) -->
    <div class="blog-article-meta">
        <div class="blog-article-meta-left">
            <div class="blog-article-author-avatar">👤</div>
            <div class="blog-article-author-info">
                <span class="blog-article-author-name">{{ $authorName }}</span>
                <span class="blog-article-author-title">Author</span>
            </div>
        </div>
        <div class="blog-article-meta-right">
            <div class="blog-article-meta-item">📅 {{ $date }}</div>
            <div class="blog-article-meta-item">⏱️ {{ $readTime }}</div>
        </div>
    </div>

    {!! $blogPost->content !!}
```

### Tags Display Added Under Article Content:
```blade
@if(!empty($blogPost->tags_list))
    <div class="blog-tags-wrapper" style="margin-top: 36px; padding-top: 20px; border-top: 1px solid #f1e5ef;">
        <span style="font-family: 'Fredoka One', cursive; font-size: 1.05rem; color: var(--dk); margin-right: 10px;">🏷️ Article Tags:</span>
        <div style="display: inline-flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
            @foreach($blogPost->tags_list as $tag)
                <span class="blog-tag-badge" style="background: var(--pkl); color: var(--pk); padding: 6px 16px; border-radius: 999px; font-family: 'Nunito', sans-serif; font-size: 0.82rem; font-weight: 800; border: 1px solid rgba(255, 77, 143, 0.2);">
                    #{{ $tag }}
                </span>
            @endforeach
        </div>
    </div>
@endif
```

### Recent 4 Posts in Right Sidebar:
```blade
<aside class="blog-sidebar">
    <div class="blog-sidebar-card">
        <h4 class="blog-sidebar-card-title">🕒 Recent Posts</h4>
        <div class="blog-recent-list">
            @forelse($recentBlogs as $recent)
                <a href="{{ route('blog.show', $recent->slug ?: $recent->id) }}" class="blog-recent-item">
                    <div class="blog-recent-thumb">
                        @if($recent->featured_image)
                            <img src="{{ asset('storage/' . ltrim($recent->featured_image, '/')) }}" alt="{{ $recent->title }}">
                        @else
                            <span class="blog-recent-thumb-emoji">📚</span>
                        @endif
                    </div>
                    <div class="blog-recent-info">
                        <span class="blog-recent-cat">{{ $recent->category?->name ?? 'Wellness' }}</span>
                        <h5 class="blog-recent-title">{{ \Illuminate\Support\Str::limit(html_entity_decode($recent->title), 48) }}</h5>
                        <span class="blog-recent-date">📅 {{ optional($recent->published_at ?? $recent->created_at)->format('M j, Y') }}</span>
                    </div>
                </a>
            @empty
                <p style="color: #999; font-size: 0.88rem;">No other recent posts yet.</p>
            @endforelse
        </div>
    </div>
</aside>
```

### Upgraded Related Articles Cards (Matching Blog Listing Cards):
**Purpose:** Replaced plain related blocks with full `.blog-card` elements featuring the post's featured image (or book badge fallback), category pill, linked title, clean excerpt, and bottom-pinned "Read Article →" button.

```blade
@if($relatedBlogs->count() > 0)
    <h3 style="font-family: 'Fredoka One', cursive; font-size: 1.6rem; color: var(--dk); margin-bottom: 24px;">
        Related Articles
    </h3>
    <div class="blog-related">
        @foreach($relatedBlogs as $relatedBlog)
            @php
                $relImage = trim((string) $relatedBlog->featured_image);
                $relImageUrl = null;
                if ($relImage !== '') {
                    $relImageUrl = \Illuminate\Support\Str::startsWith($relImage, ['http://', 'https://'])
                        ? $relImage
                        : asset(\Illuminate\Support\Str::startsWith($relImage, ['storage/', '/storage/']) ? ltrim($relImage, '/') : 'storage/' . ltrim($relImage, '/'));
                }
            @endphp
            <div class="blog-card">
                <div class="blog-card-image {{ $relImageUrl ? 'has-image' : '' }}">
                    @if($relImageUrl)
                        <img src="{{ $relImageUrl }}" alt="{{ html_entity_decode($relatedBlog->title) }}" loading="lazy">
                    @else
                        <span class="blog-card-emoji">📚</span>
                    @endif
                </div>
                <div class="blog-card-content">
                    <span class="blog-card-category">{{ $relatedBlog->category?->name ?? 'Wellness' }}</span>
                    <h3 class="blog-card-title">
                        <a href="{{ route('blog.show', $relatedBlog->slug ?: $relatedBlog->id) }}">{{ html_entity_decode($relatedBlog->title) }}</a>
                    </h3>
                    <p class="blog-card-excerpt">{{ $relatedBlog->excerpt }}</p>
                    <a href="{{ route('blog.show', $relatedBlog->slug ?: $relatedBlog->id) }}" class="blog-card-link">
                        Read Article →
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endif
```

---

## 10. Frontend View: `resources/views/frontend/storefront/blog/index.blade.php` & Partial `posts-grid.blade.php`
**Purpose:** 
- Converted the blog listing into a responsive 2-column layout:
  - **Left Area:** Contains the blog posts grid and pagination.
  - **Right Sidebar:**
    1. **Categories Card:** Displays Category Tabs **one-by-one, row-wise** with live article counts.
    2. **Blog Tags Card:** Positioned directly below the Categories card, displays all distinct blog post tags across all published articles as interactive pill chips (`#tag`).
- **Backend-Driven AJAX Filtering (No Page Reload):**
  - Clicking any category tab OR tag chip sends an asynchronous `fetch()` request with `X-Requested-With: XMLHttpRequest`.
  - The backend `BlogController@index` queries the database (filtering by category or `tags LIKE '%tag%'`) and returns JSON `{ html, total, activeCategory, activeTag }`.
  - Smoothly updates the post grid and count without reloading the full page.
  - Switches active highlight between category tabs and tag chips dynamically.
  - Uses `history.pushState()` so URLs remain bookmarkable (e.g. `/blog?category=nutrition` or `/blog?tag=Immunity`) and browser Back/Forward navigation functions naturally.
- Added partial view: `resources/views/frontend/storefront/blog/partials/posts-grid.blade.php`.
- **Card Content:** Streamlined to focus on Category badge, Title, Excerpt, and bottom-pinned "Read Article →" link (author, date, and reading time removed from list cards for a cleaner, modern look).

### Sidebar Tags Card Markup in `index.blade.php`:
```blade
<!-- Tags Card -->
@if(!empty($allTags) && count($allTags) > 0)
    <div class="blog-tags-card">
        <div class="blog-tags-card-header">
            <span class="blog-tags-card-title">Blog Tags</span>
            <span class="blog-tags-card-subtitle">Filter articles by tag</span>
        </div>
        <div class="blog-tags-cloud" id="tagsCloudList">
            @foreach($allTags as $tag)
                @php
                    $isTagActive = ($activeTag === $tag || request('tag') === $tag);
                @endphp
                <a href="{{ route('blog', ['tag' => $tag]) }}" 
                   class="blog-tag-chip {{ $isTagActive ? 'active' : '' }}" 
                   data-tag="{{ $tag }}" 
                   data-url="{{ route('blog', ['tag' => $tag]) }}">
                    #{{ $tag }}
                </a>
            @endforeach
        </div>
    </div>
@endif
```

---

## 11. Frontend Controller: `app/Http/Controllers/Frontend/Storefront/BlogController.php`
**Purpose:** 
- Supports resolving blog posts by both **slug** and **ID** seamlessly, and restricts unpublished/draft posts to authenticated admins only.
- In `index()`, collects all unique tags from published posts (`$allTags`) and filters posts by `$request->query('tag')` using `WHERE tags LIKE '%tag%'`.
- Responds with JSON for AJAX requests including `html`, `total`, `activeCategory`, and `activeTag`.

### Code Snippet:
```php
// Collect all distinct tags from published posts
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

// Filter query by tag when selected
if (!empty($activeTag)) {
    $query->where(function ($q) use ($activeTag) {
        $q->where('tags', 'LIKE', '%' . $activeTag . '%');
    });
}

// In show(): Fetch strictly up to 3 latest published posts matching category
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
```

---

## 12. Bugfix: `app/Http/Controllers/Frontend/Account/UserInvoiceController.php`
**Purpose:** Removed duplicate closing code on line 48 that was throwing a PHP parse error: `Unclosed '{' on line 37 does not match ']'`.

### Old Code:
```php
        return Pdf::loadView('pdf.invoices.order', [
            'order' => $order,
            'invoiceNumber' => $invoiceNumber,
        ])->setPaper('a4')->download($invoiceNumber . '.pdf');
        ])->setPaper('a4')->download($filename);
    }
```

### Updated Code:
```php
        return Pdf::loadView('pdf.invoices.order', [
            'order' => $order,
            'invoiceNumber' => $invoiceNumber,
        ])->setPaper('a4')->download($filename);
    }
```
