@php
    $blogPosts = $backendBlogPosts->count()
        ? collect($backendBlogPosts->items())->map(function ($post) {
            $words = str_word_count(strip_tags($post->content ?? ''));
            $image = trim((string) $post->featured_image);
            $imageUrl = null;

            if ($image !== '') {
                $imageUrl = \Illuminate\Support\Str::startsWith($image, ['http://', 'https://'])
                    ? $image
                    : asset(\Illuminate\Support\Str::startsWith($image, ['storage/', '/storage/']) ? ltrim($image, '/') : 'storage/' . ltrim($image, '/'));
            }

            return [
                'id' => $post->id,
                'slug' => $post->slug,
                'title' => html_entity_decode($post->title),
                'excerpt' => $post->excerpt,
                'category' => $post->category?->name ?? 'Wellness',
                'author' => $post->display_author,
                'date' => optional($post->published_at ?? $post->created_at)->format('M j, Y'),
                'readTime' => max(1, (int) ceil($words / 200)) . ' min read',
                'emoji' => '📚',
                'image' => $imageUrl,
            ];
        })->values()->all()
        : [];
@endphp

<!-- Blog Grid -->
<div class="blog-grid">
    @foreach($blogPosts as $post)
        <div class="blog-card" data-category="{{ strtolower($post['category']) }}">
            @php $postImage = $post['image'] ?? null; @endphp
            <div class="blog-card-image {{ $postImage ? 'has-image' : '' }}">
                @if($postImage)
                    <img src="{{ $postImage }}" alt="{{ $post['title'] }}" loading="lazy">
                @else
                    <span class="blog-card-emoji">{{ $post['emoji'] }}</span>
                @endif
            </div>
            <div class="blog-card-content">
                <span class="blog-card-category">{{ $post['category'] }}</span>
                <h3 class="blog-card-title">
                    <a href="{{ route('blog.show', $post['slug'] ?: $post['id']) }}">{{ $post['title'] }}</a>
                </h3>
                <p class="blog-card-excerpt">{{ $post['excerpt'] }}</p>
                <a href="{{ route('blog.show', $post['slug'] ?: $post['id']) }}" class="blog-card-link">
                    Read Article →
                </a>
            </div>
        </div>
    @endforeach

    @if(count($blogPosts) == 0)
        <div class="blog-empty" style="display: block; grid-column: 1 / -1;">
            <div style="font-size: 3rem; margin-bottom: 12px;">🔍</div>
            <h4 style="font-family: 'Fredoka One', cursive; color: var(--dk); margin-bottom: 8px;">No Articles Found</h4>
            <p style="color: #777; margin-bottom: 16px;">We haven't published any articles in this category yet. Check back soon!</p>
            <button type="button" class="blog-reset-filter-btn" style="background: var(--pk); color: #fff; border: none; padding: 10px 24px; border-radius: 999px; font-family: 'Nunito', sans-serif; font-weight: 800; cursor: pointer;">
                Browse All Articles
            </button>
        </div>
    @endif
</div>

<!-- Pagination -->
@if($backendBlogPosts->hasPages())
    <div class="blog-pagination" style="margin-top: 40px;">
        {{-- Previous Page Link --}}
        @if ($backendBlogPosts->onFirstPage())
            <span class="pagination-btn disabled" style="opacity: 0.5; cursor: not-allowed; display:flex;align-items:center;justify-content:center;">&laquo;</span>
        @else
            <a href="{{ $backendBlogPosts->appends(request()->query())->previousPageUrl() }}" class="pagination-btn ajax-page-link" style="text-decoration:none;display:flex;align-items:center;justify-content:center;">&laquo;</a>
        @endif

        {{-- Array Of Links --}}
        @foreach ($backendBlogPosts->getUrlRange(1, $backendBlogPosts->lastPage()) as $page => $url)
            @if ($page == $backendBlogPosts->currentPage())
                <span class="pagination-btn active" style="display:flex;align-items:center;justify-content:center;">{{ $page }}</span>
            @else
                <a href="{{ $backendBlogPosts->appends(request()->query())->url($page) }}" class="pagination-btn ajax-page-link" style="text-decoration:none;display:flex;align-items:center;justify-content:center;">{{ $page }}</a>
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($backendBlogPosts->hasMorePages())
            <a href="{{ $backendBlogPosts->appends(request()->query())->nextPageUrl() }}" class="pagination-btn ajax-page-link" style="text-decoration:none;display:flex;align-items:center;justify-content:center;">&raquo;</a>
        @else
            <span class="pagination-btn disabled" style="opacity: 0.5; cursor: not-allowed; display:flex;align-items:center;justify-content:center;">&raquo;</span>
        @endif
    </div>
@endif
