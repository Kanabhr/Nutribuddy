@extends('layouts.main')
@section('title', 'Blog & Tips — NutriBuddy Kids')

@push('meta')
    <meta name="description" content="NutriBuddy Kids Journal: Expert advice, pediatric nutrition tips, healthy recipes, and holistic parenting hacks.">
    <meta name="keywords" content="kids nutrition, baby wellness, healthy child habits, pediatrician advice, ayurvedic kids diet">
    <link rel="canonical" href="{{ route('blog') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Blog & Tips — NutriBuddy Kids">
    <meta property="og:description" content="Expert advice, nutrition tips, and parenting hacks from certified Ayurvedic nutritionists and pediatricians.">
    <meta property="og:url" content="{{ route('blog') }}">
@endpush

@push('styles')
    <style>
        .blog-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 64px 5% 76px;
        }

        .blog-section-head {
            align-items: end;
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            justify-content: space-between;
            margin-bottom: 34px;
        }

        .blog-section-kicker {
            color: var(--pk);
            display: block;
            font-family: 'Nunito', sans-serif;
            font-size: .75rem;
            font-weight: 900;
            letter-spacing: .12em;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .blog-section-title {
            color: var(--dk);
            font-family: 'Fredoka One', cursive;
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            line-height: 1.15;
            margin: 0;
        }

        .blog-section-sub {
            color: #777;
            font-family: 'DM Sans', sans-serif;
            line-height: 1.7;
            margin: 10px 0 0;
            max-width: 600px;
        }

        .blog-count-pill {
            align-items: center;
            background: var(--pkl);
            border: 1px solid rgba(255, 77, 143, .18);
            border-radius: 999px;
            color: var(--pk);
            display: inline-flex;
            font-family: 'Nunito', sans-serif;
            font-size: .82rem;
            font-weight: 900;
            min-height: 40px;
            padding: 0 16px;
        }

        /* 2-Column Layout (Left Grid, Right Row-Wise Tabs) */
        .blog-main-layout {
            display: grid;
            grid-template-columns: 1fr 310px;
            gap: 36px;
            align-items: start;
        }

        .blog-main-column {
            min-width: 0;
            position: relative;
        }

        .blog-grid-wrapper {
            transition: opacity 0.22s ease;
        }

        .blog-grid-wrapper.is-loading {
            opacity: 0.4;
            pointer-events: none;
        }

        .blog-grid {
            display: grid;
            gap: 26px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-bottom: 44px;
        }

        .blog-card {
            background: #fff;
            border: 1px solid rgba(255, 214, 232, .9);
            border-radius: 26px;
            box-shadow: 0 18px 46px rgba(13, 0, 32, .07);
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            position: relative;
            transition: transform .28s ease, box-shadow .28s ease, border-color .28s ease;
        }

        .blog-card:hover {
            border-color: rgba(255, 77, 143, .34);
            box-shadow: 0 24px 58px rgba(255, 77, 143, .13);
            transform: translateY(-8px);
        }

        .blog-card-image {
            align-items: center;
            background:
                radial-gradient(circle at 18% 16%, rgba(255, 255, 255, .38), transparent 28%),
                linear-gradient(135deg, var(--pk), var(--pu));
            display: flex;
            height: 210px;
            justify-content: center;
            overflow: hidden;
            position: relative;
            width: 100%;
        }

        .blog-card-image::after {
            background: rgba(13, 0, 32, .1);
            content: '';
            inset: 0;
            position: absolute;
        }

        .blog-card-image img {
            height: 100%;
            inset: 0;
            object-fit: cover;
            position: absolute;
            width: 100%;
            z-index: 1;
        }

        .blog-card-emoji {
            align-items: center;
            background: rgba(255, 255, 255, .9);
            border: 1px solid rgba(255, 255, 255, .6);
            border-radius: 28px;
            box-shadow: 0 14px 32px rgba(13, 0, 32, .12);
            color: var(--dk);
            display: inline-flex;
            font-family: 'Fredoka One', cursive;
            font-size: 3.2rem;
            height: 112px;
            justify-content: center;
            position: relative;
            width: 112px;
            z-index: 2;
        }

        .blog-card-image.has-image::after {
            background: linear-gradient(180deg, rgba(13, 0, 32, .02), rgba(13, 0, 32, .24));
            z-index: 2;
        }

        .blog-card-content {
            display: flex;
            flex-direction: column;
            flex: 1;
            padding: 24px;
        }

        .blog-card-category {
            background: var(--pkl);
            border-radius: 999px;
            color: var(--pk);
            display: inline-flex;
            font-family: 'Nunito', sans-serif;
            font-size: .72rem;
            font-weight: 900;
            margin-bottom: 14px;
            padding: 5px 12px;
            text-transform: uppercase;
            width: fit-content;
        }

        .blog-card-title {
            color: var(--dk);
            font-family: 'Fredoka One', cursive;
            font-size: 1.24rem;
            line-height: 1.32;
            margin: 0 0 12px;
        }

        .blog-card-title a {
            color: inherit;
            text-decoration: none;
        }

        .blog-card-title a:hover {
            color: var(--pk);
        }

        .blog-card-excerpt {
            color: #777;
            font-size: .94rem;
            line-height: 1.6;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        .blog-card-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #f4e9f0;
            color: #aaa;
            font-family: 'Nunito', sans-serif;
            font-size: .82rem;
            font-weight: 800;
            gap: 14px;
            padding-top: 16px;
        }

        .blog-card-date {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-left: auto;
        }

        .blog-card-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--pk);
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            margin-top: auto;
            padding-top: 14px;
            text-decoration: none;
            transition: gap .2s ease, color .2s ease;
        }

        .blog-card-link:hover {
            color: var(--pkd);
            gap: 12px;
        }

        .blog-pagination {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .pagination-btn {
            background: #fff;
            border: 2px solid #f1e5ef;
            border-radius: 14px;
            color: var(--dk);
            cursor: pointer;
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            min-height: 42px;
            min-width: 42px;
            transition: all .22s ease;
        }

        .pagination-btn:hover,
        .pagination-btn.active {
            background: var(--pk);
            color: #fff;
            border-color: var(--pk);
        }

        .blog-empty {
            background: #fff;
            border: 2px dashed rgba(255, 77, 143, .28);
            border-radius: 24px;
            color: #777;
            font-family: 'Nunito', sans-serif;
            padding: 44px 24px;
            text-align: center;
        }

        /* Right Sidebar: Categories & Tags Cards */
        .blog-sidebar-column {
            position: sticky;
            top: 90px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .blog-categories-card,
        .blog-tags-card {
            background: #ffffff;
            border: 1px solid rgba(255, 214, 232, 0.9);
            border-radius: 26px;
            box-shadow: 0 16px 42px rgba(13, 0, 32, 0.06);
            padding: 24px 20px;
        }

        .blog-categories-header,
        .blog-tags-card-header {
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f4e7ef;
        }

        .blog-categories-title,
        .blog-tags-card-title {
            display: block;
            font-family: 'Fredoka One', cursive;
            font-size: 1.25rem;
            color: var(--dk);
            line-height: 1.2;
        }

        .blog-categories-subtitle,
        .blog-tags-card-subtitle {
            display: block;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.84rem;
            color: #888;
            margin-top: 4px;
        }

        .blog-categories-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .blog-cat-tab-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 18px;
            background: #fdf8fb;
            border: 1.5px solid #f4e7ef;
            border-radius: 16px;
            color: var(--dk);
            text-decoration: none;
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: 0.92rem;
            transition: all 0.22s ease;
            cursor: pointer;
        }

        .blog-cat-tab-row:hover {
            background: var(--pkl);
            border-color: rgba(255, 77, 143, 0.35);
            color: var(--pk);
            transform: translateX(4px);
        }

        .blog-cat-tab-row.active {
            background: var(--pk);
            border-color: var(--pk);
            color: #ffffff !important;
            box-shadow: 0 8px 20px rgba(255, 77, 143, 0.28);
            transform: translateX(4px);
        }

        .cat-tab-name {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .cat-tab-count {
            background: rgba(255, 77, 143, 0.12);
            color: var(--pk);
            font-size: 0.76rem;
            font-weight: 900;
            padding: 3px 10px;
            border-radius: 999px;
            transition: all 0.22s ease;
        }

        .blog-cat-tab-row.active .cat-tab-count {
            background: #ffffff;
            color: var(--pk);
        }

        /* Tags Cloud in Sidebar */
        .blog-tags-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .blog-tag-chip {
            display: inline-flex;
            align-items: center;
            background: #fdf8fb;
            border: 1.5px solid #f2e2ec;
            color: var(--dk);
            padding: 7px 14px;
            border-radius: 999px;
            font-family: 'Nunito', sans-serif;
            font-size: 0.82rem;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.22s ease;
            cursor: pointer;
        }

        .blog-tag-chip:hover {
            background: var(--pkl);
            border-color: rgba(255, 77, 143, 0.4);
            color: var(--pk);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 77, 143, 0.15);
        }

        .blog-tag-chip.active {
            background: var(--pk);
            border-color: var(--pk);
            color: #ffffff !important;
            box-shadow: 0 6px 18px rgba(255, 77, 143, 0.28);
            transform: translateY(-2px);
        }

        @media (max-width: 992px) {
            .blog-main-layout {
                grid-template-columns: 1fr;
            }
            .blog-sidebar-column {
                order: -1;
                margin-bottom: 28px;
                position: static;
            }
            .blog-categories-list {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 10px;
            }
        }

        @media (max-width: 640px) {
            .blog-grid {
                grid-template-columns: 1fr;
            }
            .blog-categories-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <!-- Blog Hero -->
    <section class="product-listing-hero reveal">
        <div class="product-listing-hero-inner">
            <div class="product-listing-breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span>/</span>
                <span>Blog</span>
            </div>
            <span class="product-listing-hero-badge">Wellness Wisdom</span>
            <h1 class="product-listing-hero-title">Blog & Tips</h1>
            <p class="product-listing-hero-sub">Expert advice, nutrition tips, and parenting hacks from certified Ayurvedic nutritionists and pediatricians.</p>
        </div>
    </section>

    <!-- Blog Container -->
    <section class="blog-container">
        <div class="blog-section-head">
            <div>
                <span class="blog-section-kicker">NutriBuddy Journal</span>
                <h2 class="blog-section-title">Practical Wellness Reads</h2>
                <p class="blog-section-sub">Helpful, parent-friendly articles on nutrition, habits, recipes and everyday child wellness.</p>
            </div>
            <div class="blog-count-pill"><span id="blogVisibleCount">{{ $backendBlogPosts->total() }}</span>&nbsp;articles</div>
        </div>

        <!-- 2-Column Main Layout: Blog Grid (Left) & Row-wise Category Tabs (Right) -->
        <div class="blog-main-layout">
            
            <!-- Left Column: Blog Grid -->
            <div class="blog-main-column">
                <div id="blogGridWrapper" class="blog-grid-wrapper">
                    @include('frontend.storefront.blog.partials.posts-grid')
                </div>
            </div>

            <!-- Right Column: Category Tabs & Tags Cards -->
            <aside class="blog-sidebar-column">
                <!-- Categories Card -->
                <div class="blog-categories-card">
                    <div class="blog-categories-header">
                        <span class="blog-categories-title">Categories</span>
                        <span class="blog-categories-subtitle">Filter articles row by row</span>
                    </div>
                    <div class="blog-categories-list" id="categoryTabsList">
                        <a href="{{ route('blog') }}" 
                           class="blog-cat-tab-row {{ ((!$activeCategory || $activeCategory === 'all') && empty($activeTag)) ? 'active' : '' }}" 
                           data-category="all" 
                           data-url="{{ route('blog') }}">
                            <span class="cat-tab-name">All Articles</span>
                            <span class="cat-tab-count">{{ $totalPublishedPosts }}</span>
                        </a>
                        @foreach($categories as $category)
                            @php
                                $catSlug = $category->slug ?: strtolower($category->name);
                                $isActive = (empty($activeTag) && ($activeCategory === $catSlug || strtolower($activeCategory) === strtolower($category->name)));
                            @endphp
                            <a href="{{ route('blog', ['category' => $catSlug]) }}" 
                               class="blog-cat-tab-row {{ $isActive ? 'active' : '' }}" 
                               data-category="{{ $catSlug }}" 
                               data-url="{{ route('blog', ['category' => $catSlug]) }}">
                                <span class="cat-tab-name">{{ $category->name }}</span>
                                <span class="cat-tab-count">{{ $category->posts_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

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
            </aside>

        </div>

    </section>

    <!-- Parent Reviews & FAQ -->
    @include('partials.parent-reviews')
    @include('partials.faq-section')

    <!-- AJAX Filtering & Pagination Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const gridWrapper = document.getElementById('blogGridWrapper');
            const countSpan = document.getElementById('blogVisibleCount');

            // Backend AJAX filtering function
            function loadFilteredBlogs(url, clickedElement = null, pushHistory = true) {
                if (!gridWrapper) return;

                gridWrapper.classList.add('is-loading');

                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error('Network error');
                    return res.json();
                })
                .then(data => {
                    gridWrapper.innerHTML = data.html;

                    if (countSpan && typeof data.total !== 'undefined') {
                        countSpan.textContent = data.total;
                    }

                    const categoryTabs = document.querySelectorAll('.blog-cat-tab-row');
                    const tagChips = document.querySelectorAll('.blog-tag-chip');

                    // If a tag is active, highlight tag chip and remove category highlight
                    if (data.activeTag) {
                        categoryTabs.forEach(tab => tab.classList.remove('active'));
                        tagChips.forEach(chip => {
                            if (chip.getAttribute('data-tag') === data.activeTag) {
                                chip.classList.add('active');
                            } else {
                                chip.classList.remove('active');
                            }
                        });
                    } else {
                        // Tag is not active: clear tag chips and highlight matching category
                        tagChips.forEach(chip => chip.classList.remove('active'));

                        categoryTabs.forEach(tab => {
                            const tabCat = tab.getAttribute('data-category');
                            if (tabCat === data.activeCategory || ((!data.activeCategory || data.activeCategory === 'all') && tabCat === 'all')) {
                                tab.classList.add('active');
                            } else {
                                tab.classList.remove('active');
                            }
                        });
                    }

                    // Update browser address bar without full page reload
                    if (pushHistory) {
                        history.pushState(null, '', url);
                    }

                    gridWrapper.classList.remove('is-loading');

                    // If pagination was clicked, scroll smoothly up to grid
                    if (clickedElement && clickedElement.classList.contains('ajax-page-link')) {
                        gridWrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                })
                .catch(err => {
                    console.error('Blog filter error:', err);
                    gridWrapper.classList.remove('is-loading');
                    // Fallback to normal navigation if fetch fails
                    if (url) window.location.href = url;
                });
            }

            // Click delegation for category tabs, tag chips, pagination links, and reset button
            document.addEventListener('click', function(e) {
                const tab = e.target.closest('.blog-cat-tab-row');
                const tagChip = e.target.closest('.blog-tag-chip');
                const pageLink = e.target.closest('.ajax-page-link');
                const resetBtn = e.target.closest('.blog-reset-filter-btn');

                if (tab) {
                    e.preventDefault();
                    const targetUrl = tab.getAttribute('data-url') || tab.href;
                    loadFilteredBlogs(targetUrl, tab);
                } else if (tagChip) {
                    e.preventDefault();
                    const targetUrl = tagChip.getAttribute('data-url') || tagChip.href;
                    loadFilteredBlogs(targetUrl, tagChip);
                } else if (pageLink) {
                    e.preventDefault();
                    loadFilteredBlogs(pageLink.href, pageLink);
                } else if (resetBtn) {
                    e.preventDefault();
                    const allTab = document.querySelector('.blog-cat-tab-row[data-category="all"]');
                    const targetUrl = allTab ? (allTab.getAttribute('data-url') || allTab.href) : '{{ route('blog') }}';
                    loadFilteredBlogs(targetUrl, allTab);
                }
            });

            // Handle browser Back / Forward buttons
            window.addEventListener('popstate', function() {
                loadFilteredBlogs(window.location.href, null, false);
            });
        });
    </script>
@endsection
