@php
    $words = str_word_count(strip_tags($blogPost->content ?? ''));
    $readTime = max(1, (int) ceil($words / 200)) . ' min read';
    $date = optional($blogPost->published_at ?? $blogPost->created_at)->format('M j, Y');
    $authorName = $blogPost->display_author;
    $categoryName = $blogPost->category?->name ?? 'Wellness';

    $image = trim((string) $blogPost->featured_image);
    $imageUrl = null;
    if ($image !== '') {
        $imageUrl = \Illuminate\Support\Str::startsWith($image, ['http://', 'https://'])
            ? $image
            : asset(\Illuminate\Support\Str::startsWith($image, ['storage/', '/storage/']) ? ltrim($image, '/') : 'storage/' . ltrim($image, '/'));
    }

    $pageTitle = ($blogPost->meta_title ?: html_entity_decode($blogPost->title)) . ' — NutriBuddy Kids';
    $metaDescription = $blogPost->meta_description ?: \Illuminate\Support\Str::limit(html_entity_decode(strip_tags($blogPost->content ?? '')), 160);
    $metaKeywords = $blogPost->meta_keywords ?: (!empty($blogPost->tags) ? $blogPost->tags : 'kids nutrition, child wellness, healthy habits');
    $currentUrl = request()->fullUrl();
@endphp

@extends('layouts.main')
@section('title', $pageTitle)

@push('meta')
    <meta name="description" content="{{ $metaDescription }}">
    @if(!empty($metaKeywords))
        <meta name="keywords" content="{{ $metaKeywords }}">
    @endif
    <link rel="canonical" href="{{ route('blog.show', $blogPost->id) }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ $currentUrl }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    @if($imageUrl)
        <meta property="og:image" content="{{ $imageUrl }}">
    @endif
    <meta property="og:site_name" content="NutriBuddy Kids">

    <!-- Twitter -->
    <meta name="twitter:card" content="{{ $imageUrl ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:url" content="{{ $currentUrl }}">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    @if($imageUrl)
        <meta name="twitter:image" content="{{ $imageUrl }}">
    @endif
@endpush

@push('styles')
    <style>
        .blog-detail-hero {
            background: #0d0028;
            padding: 100px 5% 10px;
            position: relative;
            overflow: hidden;
        }

        .blog-detail-hero::before {
            content: '';
            position: absolute;
            width: 560px;
            height: 560px;
            border-radius: 62% 38% 56% 44%/48% 62% 38% 52%;
            background: radial-gradient(circle, rgba(255, 77, 143, .12), transparent 70%);
            top: -160px;
            right: -120px;
            animation: blobMorph 10s ease-in-out infinite;
            pointer-events: none;
        }

        .blog-detail-header {
            padding-top: 23px;
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .blog-detail-breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.85);
            flex-wrap: wrap;
            line-height: 1.5;
        }

        .blog-detail-breadcrumb a,
        .blog-detail-breadcrumb span {
            display: inline-flex;
            align-items: center;
            vertical-align: middle;
        }

        .blog-detail-breadcrumb a {
            color: var(--pk);
            text-decoration: none;
            font-weight: 700;
            transition: color 0.2s ease;
        }

        .blog-detail-breadcrumb a:hover {
            color: #ffffff;
            text-decoration: underline;
        }

        .blog-detail-breadcrumb .breadcrumb-separator {
            color: rgba(255, 255, 255, 0.45);
            font-size: 0.85rem;
            user-select: none;
        }

        .blog-detail-category {
            display: inline-block;
            background: var(--pkl);
            color: var(--pk);
            padding: 8px 16px;
            border-radius: 50px;
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 0.75rem;
            margin-bottom: 16px;
        }

        .blog-detail-title {
            font-family: 'Fredoka One', cursive;
            font-size: clamp(1.8rem, 5vw, 2.8rem);
            color: var(--wh);
            line-height: 1.3;
            margin-bottom: 12px;
        }

        .blog-detail-subtitle {
            font-family: 'DM Sans', sans-serif;
            font-size: clamp(0.98rem, 2vw, 1.15rem);
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.88);
            margin: 0 0 20px;
            max-width: 820px;
        }

        /* Article Meta Bar (placed right after blog image) */
        .blog-article-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            padding: 14px 20px;
            background: #ffffff;
            border: 1px solid #f2e2ec;
            border-radius: 16px;
            margin-bottom: 28px;
            box-shadow: 0 4px 16px rgba(13, 0, 32, 0.04);
            font-family: 'Nunito', sans-serif;
        }

        .blog-article-meta-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .blog-article-author-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--pk), var(--pu));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(255, 77, 143, 0.22);
            flex-shrink: 0;
        }

        .blog-article-author-info {
            display: flex;
            flex-direction: column;
        }

        .blog-article-author-name {
            font-weight: 800;
            color: var(--dk);
            font-size: 0.96rem;
            line-height: 1.2;
        }

        .blog-article-author-title {
            font-size: 0.74rem;
            color: #888;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .blog-article-meta-right {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
            color: #666;
            font-size: 0.86rem;
            font-weight: 800;
        }

        .blog-article-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Article Content */
        .blog-content-wrapper {
            max-width: 1100px;
            margin: 60px auto;
            padding: 0 3%;
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 40px;
        }

        .blog-featured-image {
            width: 100%;
            height: 400px;
            background: linear-gradient(135deg, var(--pk), var(--pu));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6rem;
            margin-bottom: 50px;
            overflow: hidden;
        }

        .blog-featured-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .blog-article {
            line-height: 2;
            font-size: 1.05rem;
            color: #444;
            min-width: 0;
            /* Prevents flex/grid blowout */
        }

        .blog-sidebar {
            min-width: 0;
        }

        .blog-article img,
        .blog-article iframe,
        .blog-article video {
            max-width: 100%;
            height: auto;
        }

        .blog-article h2 {
            font-family: 'Fredoka One', cursive;
            font-size: 1.8rem;
            color: var(--dk);
            margin: 40px 0 20px 0;
        }

        .blog-article h3 {
            font-family: 'Fredoka One', cursive;
            font-size: 1.4rem;
            color: var(--pk);
            margin: 30px 0 15px 0;
        }

        .blog-article p {
            margin-bottom: 20px;
        }

        .blog-article ul,
        .blog-article ol {
            margin: 20px 0 20px 30px;
        }

        .blog-article li {
            margin-bottom: 12px;
        }

        .blog-highlight {
            background: var(--pkl);
            padding: 24px;
            border-left: 4px solid var(--pk);
            border-radius: 8px;
            margin: 30px 0;
        }

        .blog-highlight p {
            margin: 0;
            font-weight: 700;
            color: var(--pk);
        }

        /* Sidebar */
        .blog-sidebar {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .blog-sidebar-card {
            background: #ffffff;
            border: 1px solid rgba(255, 214, 232, 0.9);
            border-radius: 24px;
            box-shadow: 0 14px 36px rgba(13, 0, 32, 0.05);
            padding: 24px 20px;
            position: sticky;
            top: 135px;
        }

        .blog-sidebar-card-title {
            font-family: 'Fredoka One', cursive;
            font-size: 1.22rem;
            color: var(--dk);
            margin: 0 0 18px;
            padding-bottom: 12px;
            border-bottom: 1.5px solid #f6edf3;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .blog-recent-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .blog-recent-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 16px;
            text-decoration: none;
            background: #fdf8fb;
            border: 1px solid #f6ebf2;
            transition: all 0.22s ease;
        }

        .blog-recent-item:hover {
            background: var(--pkl);
            border-color: rgba(255, 77, 143, 0.35);
            transform: translateX(4px);
        }

        .blog-recent-thumb {
            width: 58px;
            height: 58px;
            border-radius: 12px;
            overflow: hidden;
            flex-shrink: 0;
            background: linear-gradient(135deg, var(--pk), var(--pu));
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .blog-recent-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .blog-recent-thumb-emoji {
            font-size: 1.5rem;
        }

        .blog-recent-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
            flex: 1;
        }

        .blog-recent-cat {
            font-size: 0.68rem;
            font-weight: 900;
            color: var(--pk);
            text-transform: uppercase;
            font-family: 'Nunito', sans-serif;
            letter-spacing: 0.4px;
        }

        .blog-recent-title {
            font-family: 'Fredoka One', cursive;
            font-size: 0.92rem;
            color: var(--dk);
            line-height: 1.28;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .blog-recent-item:hover .blog-recent-title {
            color: var(--pk);
        }

        .blog-recent-date {
            font-size: 0.74rem;
            color: #888;
            font-family: 'Nunito', sans-serif;
            font-weight: 700;
            margin-top: 2px;
        }

        /* Article Footer */
        .blog-footer {
            max-width: 1100px;
            margin: 80px auto 60px;
            padding: 0 3%;
            border-top: 2px solid #f0f0f0;
            padding-top: 40px;
        }

        .blog-tags {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }

        .blog-tag {
            background: #f0f0f0;
            color: #666;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .blog-tag:hover {
            background: var(--pkl);
            color: var(--pk);
        }

        .blog-related {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 26px;
            margin-top: 30px;
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
            height: 200px;
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
            font-size: 3rem;
            height: 100px;
            justify-content: center;
            position: relative;
            width: 100px;
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
            margin-bottom: 12px;
            padding: 5px 12px;
            text-transform: uppercase;
            width: fit-content;
        }

        .blog-card-title {
            color: var(--dk);
            font-family: 'Fredoka One', cursive;
            font-size: 1.2rem;
            line-height: 1.32;
            margin: 0 0 10px;
        }

        .blog-card-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .blog-card-title a:hover {
            color: var(--pk);
        }

        .blog-card-excerpt {
            color: #777;
            font-size: .92rem;
            line-height: 1.6;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        .blog-card-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--pk);
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            margin-top: auto;
            padding-top: 12px;
            text-decoration: none;
            transition: gap .2s ease, color .2s ease;
        }

        .blog-card-link:hover {
            color: var(--pkd);
            gap: 12px;
        }

        @media (max-width: 992px) {
            .blog-related {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .blog-related {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .blog-detail-hero {
                padding: 60px 3% 40px;
            }

            .blog-content-wrapper {
                grid-template-columns: 1fr;
            }

            .blog-featured-image {
                height: 250px;
                font-size: 3rem;
            }

            .blog-toc {
                position: static;
            }
        }
    </style>
@endpush

@section('content')
    <!-- Hero -->
    <section class="blog-detail-hero">
        <div class="blog-detail-header">
            <div class="blog-detail-breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span class="breadcrumb-separator">/</span>
                <a href="{{ route('blog') }}">Blog</a>
                <span class="breadcrumb-separator">/</span>
                <span>{{ html_entity_decode($blogPost->title) }}</span>
            </div>
            <span class="blog-detail-category">{{ $categoryName }}</span>
            <h1 class="blog-detail-title">{{ html_entity_decode($blogPost->title) }}</h1>
            @if(!empty($blogPost->excerpt))
                <p class="blog-detail-subtitle">{{ $blogPost->excerpt }}</p>
            @endif
        </div>
    </section>

    <!-- Content -->
    <div class="blog-content-wrapper">
        <article class="blog-article">
            @if($imageUrl)
                <div class="blog-featured-image" style="background: none; font-size: initial;">
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
                </div>
            </div>

            {!! $blogPost->content !!}

            @if(!empty($blogPost->tags_list))
                <div class="blog-tags-wrapper" style="margin-top: 36px; padding-top: 20px; border-top: 1px solid #f1e5ef;">
                    <span
                        style="font-family: 'Fredoka One', cursive; font-size: 1.05rem; color: var(--dk); margin-right: 10px;">🏷️
                        Article Tags:</span>
                    <div style="display: inline-flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
                        @foreach($blogPost->tags_list as $tag)
                            <span class="blog-tag-badge"
                                style="background: var(--pkl); color: var(--pk); padding: 6px 16px; border-radius: 999px; font-family: 'Nunito', sans-serif; font-size: 0.82rem; font-weight: 800; border: 1px solid rgba(255, 77, 143, 0.2);">
                                #{{ $tag }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="blog-share-icons" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;">
                <h4 style="font-family: 'Fredoka One', cursive; margin-bottom: 16px; font-size: 1.2rem; color: var(--dk);">
                    Share this article</h4>
                <div style="display: flex; gap: 12px;">
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->fullUrl()) }}"
                        target="_blank"
                        style="width: 44px; height: 44px; border-radius: 50%; background: #1877F2; color: #fff; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; font-family: sans-serif; font-size: 1.2rem; transition: transform 0.2s;"
                        onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">f</a>
                    <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->fullUrl()) }}&text={{ urlencode($blogPost->title) }}"
                        target="_blank"
                        style="width: 44px; height: 44px; border-radius: 50%; background: #000000; color: #fff; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; font-family: sans-serif; font-size: 1.2rem; transition: transform 0.2s;"
                        onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">𝕏</a>
                    <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(request()->fullUrl()) }}&title={{ urlencode($blogPost->title) }}"
                        target="_blank"
                        style="width: 44px; height: 44px; border-radius: 50%; background: #0A66C2; color: #fff; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; font-family: sans-serif; font-size: 1.1rem; transition: transform 0.2s;"
                        onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">in</a>
                    <a href="https://api.whatsapp.com/send?text={{ urlencode($blogPost->title . ' ' . request()->fullUrl()) }}"
                        target="_blank"
                        style="width: 44px; height: 44px; border-radius: 50%; background: #25D366; color: #fff; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; font-family: sans-serif; font-size: 1.2rem; transition: transform 0.2s;"
                        onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">W</a>
                </div>
            </div>
        </article>

        <aside class="blog-sidebar">
            <div class="blog-sidebar-card">
                <h4 class="blog-sidebar-card-title">🕒 Recent Posts</h4>
                <div class="blog-recent-list">
                    @forelse($recentBlogs as $recent)
                        @php
                            $recentImg = trim((string) $recent->featured_image);
                            $recentImgUrl = null;
                            if ($recentImg !== '') {
                                $recentImgUrl = \Illuminate\Support\Str::startsWith($recentImg, ['http://', 'https://'])
                                    ? $recentImg
                                    : asset(\Illuminate\Support\Str::startsWith($recentImg, ['storage/', '/storage/']) ? ltrim($recentImg, '/') : 'storage/' . ltrim($recentImg, '/'));
                            }
                        @endphp
                        <a href="{{ route('blog.show', $recent->slug ?: $recent->id) }}" class="blog-recent-item">
                            <div class="blog-recent-thumb">
                                @if($recentImgUrl)
                                    <img src="{{ $recentImgUrl }}" alt="{{ html_entity_decode($recent->title) }}">
                                @else
                                    <span class="blog-recent-thumb-emoji">📚</span>
                                @endif
                            </div>
                            <div class="blog-recent-info">
                                <span class="blog-recent-cat">{{ $recent->category?->name ?? 'Wellness' }}</span>
                                <h5 class="blog-recent-title">
                                    {{ \Illuminate\Support\Str::limit(html_entity_decode($recent->title), 48) }}
                                </h5>
                                <span class="blog-recent-date">📅
                                    {{ optional($recent->published_at ?? $recent->created_at)->format('M j, Y') }}</span>
                            </div>
                        </a>
                    @empty
                        <p style="color: #999; font-size: 0.88rem; font-family: 'Nunito', sans-serif;">No other recent posts
                            yet.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>

    <!-- Footer -->
    <section class="blog-footer">
        @if($relatedBlogs->count() > 0)
            <h3 style="font-family: 'Fredoka One', cursive; font-size: 1.6rem; color: var(--dk); margin-bottom: 24px;">
                Related Articles
            </h3>
            <div class="blog-related">
                @foreach($relatedBlogs->take(3) as $relatedBlog)
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
                                <a
                                    href="{{ route('blog.show', $relatedBlog->slug ?: $relatedBlog->id) }}">{{ html_entity_decode($relatedBlog->title) }}</a>
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
    </section>

    <!-- Parent Reviews & FAQ -->
    @include('partials.parent-reviews')
    @include('partials.faq-section')
@endsection