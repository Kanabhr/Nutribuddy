# Blog System Rules (Admin Backend to Frontend Storefront)

This rule governs all development, refactoring, and features for the NutriBuddy Blog System.
It ensures seamless integration between the Admin Panel backend (`admin.ecommerce.blog-posts.*`, `admin.ecommerce.blog-categories.*`) and the Public Storefront frontend (`route('blog')`, `route('blog.show')`), guaranteeing zero regressions and unbreakable stability.

---

## 1. Zero-Breakage Invariant (Cardinal Rule)
1. **Never break existing URLs or routes**: Storefront routes `route('blog')` and `route('blog.show', $identifier)` must always work. If transitioning from IDs (`/blog/{id}`) to SEO-friendly slugs (`/blog/{slug}`), resolution must support both slug and ID seamlessly:
   ```php
   $blogPost = BlogPost::where('slug', $identifier)
       ->orWhere('id', is_numeric($identifier) ? $identifier : 0)
       ->firstOrFail();
   ```
2. **Never break admin routes or permissions**:
   - Route name prefix: `admin.ecommerce.blog-posts.*` and `admin.ecommerce.blog-categories.*`.
   - Guarded by middleware: `permission:view content|manage content,admin`.
   - All controller redirects must point to valid route names (e.g. `route('admin.ecommerce.blog-posts.index')`).
3. **No database column assumptions without schema check**:
   - The `excerpt` column was dropped from `blog_posts` (`2026_08_11_122832_drop_excerpt_from_blog_posts_table.php`).
   - NEVER query or insert a physical `excerpt` database column.
   - Excerpts must be generated dynamically from `content` (e.g., `Str::limit(strip_tags($post->content), 130)` or via Eloquent accessor).

---

## 2. Eloquent Model & Data Integrity
1. **Model Fillable & Casts**:
   - Fillable fields: `blog_category_id`, `author_id`, `title`, `slug`, `content`, `featured_image`, `status`, `published_at`, `meta_title`, `meta_description`, `meta_keywords`.
   - `published_at` must always be cast to `'datetime'`.
2. **Null-Safe Relationships**:
   - Authors (`author_id`) or Categories (`blog_category_id`) can be null or deleted.
   - **Frontend & Admin views must ALWAYS use null-safe accessors**:
     - `$post->category?->name ?? 'Wellness'`
     - `$post->author?->name ?? 'NutriBuddy Team'`
     - Never do direct property access `$post->category->name` without safe-guards.
3. **N+1 Prevention**:
   - Always eager load relationships in queries:
     ```php
     BlogPost::with(['category', 'author'])
     ```
4. **Automatic Slugs & Dates**:
   - Slugs must auto-generate via `Str::slug($title)` if not provided.
   - When status transitions to `'published'` and `published_at` is empty, automatically set `published_at = now()`.

---

## 3. Public Visibility Boundary
1. **Storefront Query Scope**:
   - Public frontend must ONLY display posts that satisfy ALL conditions:
     ```php
     $query->where('status', 'published')
           ->whereNotNull('published_at')
           ->where('published_at', '<=', now());
     ```
2. **Draft / Archived / Future Posts Security**:
   - `draft`, `archived`, and scheduled future posts must NEVER leak onto the storefront listing, search, related posts, recent posts, or RSS feeds.
   - If an unauthenticated user visits `/blog/{draft-id}`, abort with `404 Not Found`. Only authenticated admin users with `view content` permission may preview drafts.

---

## 4. Image & Media Asset Lifecycle
1. **Use `BlogPostManagementService`**:
   - All file uploads must pass through `BlogPostManagementService`.
   - Files are stored on the `'public'` disk under `blog-posts/`.
   - The database stores the relative path (e.g. `blog-posts/xyz.webp`).
2. **Resilient Frontend Image Rendering**:
   - Frontend must handle both local storage paths and external URLs:
     ```php
     $image = trim((string) $blogPost->featured_image);
     $imageUrl = null;
     if ($image !== '') {
         $imageUrl = Str::startsWith($image, ['http://', 'https://'])
             ? $image
             : asset(Str::startsWith($image, ['storage/', '/storage/']) ? ltrim($image, '/') : 'storage/' . ltrim($image, '/'));
     }
     ```
   - If no image is uploaded, display a graceful fallback (brand emoji or placeholder card). NEVER show broken `<img>` tags.
3. **Storage Cleanup**:
   - On image replacement or post force-delete, delete old images from `Storage::disk('public')`.
   - NEVER delete images on soft delete.

---

## 5. Frontend View & Responsive Layout Standards
1. **Design System & Aesthetics**:
   - Retain NutriBuddy's playful, high-trust kids wellness theme:
     - Fonts: `Fredoka One` for headings, `Nunito` for UI/badges/buttons, `DM Sans` for body text.
     - Brand Colors: `--pk` (`#ff4d8f`), `--pu` (`#845ec2`), `--dk` (`#0d0028`), `--pkl` (`#ffe6f0`).
     - Soft pill tags, subtle gradients, and smooth hover micro-interactions.
2. **Rich Text & Content Containment**:
   - Admin WYSIWYG HTML rendered with `{!! $blogPost->content !!}`.
   - Enforce CSS responsive containment on `.blog-article`:
     ```css
     .blog-article img,
     .blog-article iframe,
     .blog-article video,
     .blog-article table {
         max-width: 100%;
         height: auto;
     }
     .blog-article {
         min-width: 0;
         word-break: break-word;
     }
     ```
3. **Category Filtering & Pagination**:
   - Active filters must highlight clearly.
   - If a category has 0 posts, show a friendly empty state ("No articles found in this category") with a "View All Articles" button.
   - Always retain query parameters during pagination:
     ```blade
     {{ $backendBlogPosts->appends(request()->query())->links() }}
     ```

---

## 6. SEO, Social & OpenGraph Metadata
1. **Dynamic Page Titles**:
   ```blade
   @section('title', ($blogPost->meta_title ?: html_entity_decode($blogPost->title)) . ' — NutriBuddy Kids')
   ```
2. **Meta & OpenGraph Tags on Blog Detail**:
   - `<meta name="description" content="{{ $blogPost->meta_description ?: Str::limit(strip_tags($blogPost->content), 160) }}">`
   - Open Graph tags (`og:title`, `og:description`, `og:image`, `og:url`).
   - Twitter Card tags (`twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`).
3. **Social Sharing Links**:
   - WhatsApp, Twitter/X, LinkedIn, Facebook share buttons must pass URL-encoded values (`urlencode(request()->fullUrl())` and `urlencode($blogPost->title)`).

---

## 7. Admin-to-Frontend Synchronicity
1. **Live Preview Links in Admin**:
   - Admin post listing and edit view must include a "View Live" button linking to `route('blog.show', $post->id)` with `target="_blank"`.
2. **Trash, Restore & Bulk Operations**:
   - Soft deletes move posts to `admin.ecommerce.blog-posts.trash`.
   - Restore restores posts without data corruption.
   - Force delete permanently cleans up database rows and files.

---

## 8. Pre-Commit / Pre-Deploy Checklist
Before declaring any blog change complete:
- [ ] Admin CRUD: Can create, edit, save, trash, and restore blog posts without 500 errors.
- [ ] Image upload: Images upload to `storage/app/public/blog-posts/` and render on the frontend correctly.
- [ ] Frontend list (`/blog`): Displays published posts, category filter works, pagination works without dropping query params.
- [ ] Frontend detail (`/blog/{id}`): Header, author, date, read time, responsive article body, sidebar, related posts, and social share links work.
- [ ] Mobile test: No horizontal scrolling or overflowing media in `.blog-article`.
- [ ] Empty state: Filter by an empty category shows a friendly notice, not a broken page.
