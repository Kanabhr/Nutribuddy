@extends('layouts.main')
@section('title', 'All Products - NutriBuddy')

@section('content')
    @php
        $productsList = $products->values();
        $catalogCategoryCounts = collect($categoryCounts ?? [])
            ->map(fn($category) => [
                'slug' => $category['slug'] ?? \Illuminate\Support\Str::slug($category['name'] ?? 'Uncategorized'),
                'name' => $category['name'] ?? 'Uncategorized',
                'count' => $category['count'] ?? 0,
            ])
            ->sortBy('name')
            ->values();
        $prices = $productsList->map(fn($p) => (float) $p->display_price);
        $minPrice = (int) floor($prices->min() ?? 0);
        $maxPrice = (int) ceil($prices->max() ?? 0);
    @endphp

    {{-- Hero --}}
    <section class="product-listing-hero">
        <div class="product-listing-hero-inner">
            <div class="product-listing-breadcrumb">
                <a href="{{ url('/') }}">Home</a><span>/</span><span>Products</span>
            </div>
            <span class="product-listing-hero-badge">Shop NutriBuddy</span>
            <h1 class="product-listing-hero-title">Find the Right Nutrition for Growing Kids</h1>
            <p class="product-listing-hero-sub">Browse every NutriBuddy product with filters for category, price and rating.
            </p>
        </div>
    </section>

    {{-- Main layout: sidebar + grid --}}
    <section class="plp-page" id="products">
        <div class="plp-layout">

            {{-- ── SIDEBAR ── --}}
            <div class="plp-filter-backdrop" id="plpFilterBackdrop" hidden></div>
            <aside class="plp-sidebar" id="plpFilterDrawer" aria-label="Product filters">
                <div class="plp-mobile-drawer-head">
                    <div>
                        <span>Filters</span>
                        <strong>Refine products</strong>
                    </div>
                    <button type="button" id="plpFilterClose" aria-label="Close filters">&times;</button>
                </div>

                <div class="plp-sidebar-head">
                    <h2>Filters</h2>
                    <button type="button" id="plpClear">Clear</button>
                </div>

                <div class="plp-filter-block">
                    <h3>Search</h3>
                    <input type="search" id="plpSearch" placeholder="Search products" autocomplete="off">
                </div>

                <div class="plp-filter-block">
                    <h3>Categories</h3>
                    <button type="button" class="plp-cat-btn active" data-cat="all">
                        <span>All Products</span><strong>{{ $totalProducts ?? $products->total() }}</strong>
                    </button>
                    @foreach ($catalogCategoryCounts as $category)
                        <button type="button" class="plp-cat-btn" data-cat="{{ $category['slug'] }}">
                            <span>{{ $category['name'] }}</span><strong>{{ $category['count'] }}</strong>
                        </button>
                    @endforeach
                </div>

                <div class="plp-filter-block">
                    <h3>Price Range</h3>
                    <div class="plp-price-row">
                        <input type="number" id="plpMinPrice" value="{{ $minPrice }}" min="{{ $minPrice }}"
                            max="{{ $maxPrice }}">
                        <span>to</span>
                        <input type="number" id="plpMaxPrice" value="{{ $maxPrice }}" min="{{ $minPrice }}"
                            max="{{ $maxPrice }}">
                    </div>
                    <input type="range" id="plpPriceRange" min="{{ $minPrice }}" max="{{ $maxPrice }}"
                        value="{{ $maxPrice }}">
                    <p>Up to Rs. <span id="plpPriceLabel">{{ number_format($maxPrice) }}</span></p>
                </div>

                <button type="button" class="plp-mobile-apply" id="plpFilterApply">Apply Filters</button>
            </aside>

            {{-- ── RESULTS AREA ── --}}
            <div class="plp-results">

                {{-- Toolbar --}}
                <div class="plp-toolbar">
                    <div>
                        <strong><span id="plpVisible">{{ $productsList->count() }}</span> products found</strong>
                        <p>Showing <span
                                id="plpRange">{{ $productsList->isNotEmpty() ? '1-' . $productsList->count() : '0-0' }}</span>
                            of {{ $productsList->count() }}</p>
                    </div>
                    <div class="plp-toolbar-actions">
                        <select id="plpSort" aria-label="Sort products">
                            <option value="default">Default</option>
                            <option value="featured">Featured first</option>
                            <option value="price-low">Price low to high</option>
                            <option value="price-high">Price high to low</option>
                            <option value="name">Name A to Z</option>
                        </select>
                        <button type="button" class="plp-mobile-filter-btn" id="plpFilterOpen"
                            aria-controls="plpFilterDrawer" aria-expanded="false">
                            <span></span> Filters
                        </button>
                    </div>
                </div>

                {{-- 3-column product grid --}}
                <div class="plp-grid" id="plpGrid">
                    @foreach ($productsList as $index => $product)
                        @php
                            // Map category slug to CSS theme class for card styling
                            // TODO: Consider moving to a config file or category metadata if more themes are added
                            $catSlug = match($product->category->slug ?? null) {
                                'whey-protein' => 'sk',
                                'pre-workout' => 'pu',
                                default => 'pk', // Default theme for multivitamins and all other categories
                            };

                            $categoryName = $product->category->name ?? 'Uncategorized';
                            $categoryKey = $product->category->slug ?? \Illuminate\Support\Str::slug($categoryName);

                            $activeVariants = $product->variants
                                ->filter(fn($v) => $v->is_active && !empty($v->attributes))
                                ->values();

                            $selectedVariant =
                                $activeVariants->firstWhere('is_default', true) ?: $activeVariants->first();

                            $stockQty = (int) ($selectedVariant?->inventory?->stock_qty ?? 0);
                            $trackStock = (bool) ($selectedVariant?->inventory?->track_stock ?? false);
                            $isAvailable =
                                !$trackStock || (($selectedVariant?->inventory?->is_in_stock ?? true) && $stockQty > 0);

                            $frontendVariants = $activeVariants
                                ->map(function ($v) {
                                    $sq = (int) ($v->inventory?->stock_qty ?? 0);
                                    $ts = (bool) ($v->inventory?->track_stock ?? false);
                                    return [
                                        'id' => $v->id,
                                        'name' => $v->name,
                                        'attributes' => $v->attributes ?? [],
                                        'price' => (float) $v->display_price,
                                        'compare_price' => (float) ($v->display_compare_price ?? 0),
                                        'stock_qty' => $sq,
                                        'track_stock' => $ts,
                                        'available' => !$ts || (($v->inventory?->is_in_stock ?? true) && $sq > 0),
                                    ];
                                })
                                ->values()
                                ->all();

                            $cardPrice = (float) ($selectedVariant?->display_price ?? $product->display_price);
                            $cardComparePrice =
                                (float) ($selectedVariant?->display_compare_price ??
                                    ($product->display_compare_price ?? 0));
                            $reviewCount = $product->reviews_count ?? 0;
                            $rating = (float) ($product->reviews_avg_rating ?? 0);
                            $fallbackDefaultImage = $product->primaryImage ?: $product->images->first();
                            $fallbackHoverImage =
                                $product->images->where('id', '!=', $fallbackDefaultImage?->id)->first() ?: $fallbackDefaultImage;
                            $defaultImagePath = $product->card_image_path ?: $fallbackDefaultImage?->image_path;
                            $hoverImagePath = $product->card_hover_image_path ?: ($fallbackHoverImage?->image_path ?: $defaultImagePath);
                        @endphp

                        <div class="pc pc-{{ $catSlug }} plp-card {{ $selectedVariant ? 'has-variants' : 'no-variants' }}"
                            data-order="{{ $index }}" data-cat="{{ $categoryKey }}"
                            data-search="{{ strtolower($product->name . ' ' . $categoryName) }}"
                            data-price="{{ $cardPrice }}" data-featured="{{ $product->is_featured ? 1 : 0 }}"
                            data-rating="{{ $rating }}" data-name="{{ strtolower($product->name) }}"
                            data-selected-variant-id="{{ $selectedVariant?->id }}"
                            data-variants='@json($frontendVariants)'>

                            <div class="pc-head pc-head-{{ $catSlug }}">
                                <a href="{{ route('product.show', $product->slug) }}" class="pc-emoji p-image">
                                    @if ($defaultImagePath)
                                        <img src="{{ asset('storage/' . $defaultImagePath) }}"
                                            alt="{{ $product->name }}" class="default-img" loading="lazy" decoding="async">
                                        <img src="{{ asset('storage/' . $hoverImagePath) }}"
                                            alt="{{ $product->name }}" class="hover-img" loading="lazy" decoding="async">
                                    @else
                                        <img src="{{ asset('img/product2.png') }}"
                                            alt="{{ $product->name }}" class="default-img" loading="lazy" decoding="async">
                                        <img src="{{ asset('img/product2.png') }}"
                                            alt="{{ $product->name }}" class="hover-img" loading="lazy" decoding="async">
                                    @endif
                                </a>
                                @if ($cardComparePrice > $cardPrice || $product->is_featured)
                                    <div class="pc-badge">{{ $cardComparePrice > $cardPrice ? 'Offer' : 'Best Seller' }}
                                    </div>
                                @endif
                            </div>

                            <div class="pc-body">
                                <a href="{{ route('product.show', $product->slug) }}#reviews" class="pc-stars" style="text-decoration: none;">
                                    @for ($i = 0; $i < 5; $i++)
                                        {!! $i < round($rating) ? '&#9733;' : '&#9734;' !!}
                                    @endfor
                                    <span
                                        style="color:#aaa;font-size:.75rem;font-family:'DM Sans',sans-serif">({{ $reviewCount }}
                                        reviews)</span>
                                </a>
                                <div class="pc-cat cat-{{ $catSlug }}">{{ $categoryName }}</div>
                                <div class="pc-name">
                                    <a href="{{ route('product.show', $product->slug) }}"
                                        style="color:inherit;text-decoration:none">{{ $product->name }}</a>
                                </div>

                                <div class="pc-foot">
                                    <div class="pc-price" data-price-label>
                                        &#8377;{{ number_format($cardPrice, 0) }}
                                        @if ($cardComparePrice > $cardPrice)
                                            <s>&#8377;{{ number_format($cardComparePrice, 0) }}</s>
                                        @endif
                                    </div>
                                    <button class="btn-add badd-{{ $catSlug }}" data-id="{{ $product->id }}"
                                        data-variant-id="{{ $selectedVariant?->id }}" @disabled(!$isAvailable)>
                                        {{ $isAvailable ? 'Add to Cart +' : 'Out of Stock' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="plp-empty" id="plpEmpty" hidden>
                    <h3>No products found</h3>
                    <p>Please clear filters or search another product.</p>
                </div>
            </div>{{-- /plp-results --}}
        </div>{{-- /plp-layout --}}
    </section>
@endsection

@push('styles')
    <style>
        /* ── Product Listing Page ── */
        .plp-page {
            background: var(--cr);
            padding: 56px 3% 80px;
        }

        .plp-layout {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 28px;
            max-width: 1500px;
            margin: 0 auto;
        }

        /* ── Sidebar ── */
        .plp-sidebar {
            align-self: start;
            background: #fff;
            border: 2.5px solid var(--pkl);
            border-radius: 26px;
            box-shadow: 0 18px 54px rgba(255, 77, 143, .08);
            padding: 24px 22px;
            position: sticky;
            top: 110px;
        }

        .plp-filter-backdrop,
        .plp-mobile-drawer-head,
        .plp-mobile-apply,
        .plp-mobile-filter-btn {
            display: none;
        }

        .plp-sidebar-head {
            align-items: center;
            display: flex;
            justify-content: space-between;
            padding-bottom: 20px;
        }

        .plp-sidebar-head h2 {
            color: var(--dk);
            font-family: 'Nunito', sans-serif;
            font-size: 1.15rem;
            font-weight: 900;
        }

        .plp-sidebar-head button {
            background: var(--pkl);
            border: 0;
            border-radius: 999px;
            color: var(--pk);
            cursor: pointer;
            font-family: 'Nunito', sans-serif;
            font-size: .78rem;
            font-weight: 900;
            padding: 8px 16px;
            transition: background .2s;
        }

        .plp-sidebar-head button:hover {
            background: var(--pk);
            color: #fff;
        }

        .plp-filter-block {
            border-top: 1px solid rgba(13, 0, 32, .08);
            padding: 18px 0;
        }

        .plp-filter-block:last-child {
            padding-bottom: 0;
        }

        .plp-filter-block h3 {
            color: var(--dk);
            font-family: 'Nunito', sans-serif;
            font-size: .8rem;
            font-weight: 900;
            letter-spacing: 2px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        #plpSearch {
            background: #fff;
            border: 2px solid rgba(255, 214, 232, .95);
            border-radius: 14px;
            color: var(--dk);
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            height: 44px;
            outline: none;
            padding: 0 14px;
            width: 100%;
        }

        #plpSearch:focus {
            border-color: var(--pk);
            box-shadow: 0 0 0 4px rgba(255, 77, 143, .08);
        }

        .plp-cat-btn {
            align-items: center;
            background: #fff;
            border: 2px solid rgba(255, 214, 232, .95);
            border-radius: 16px;
            color: var(--dk);
            cursor: pointer;
            display: flex;
            font-family: 'Nunito', sans-serif;
            font-size: .88rem;
            font-weight: 900;
            justify-content: space-between;
            margin-bottom: 9px;
            min-height: 46px;
            padding: 8px 12px;
            transition: all .2s;
            width: 100%;
        }

        .plp-cat-btn strong {
            align-items: center;
            background: #fff1f7;
            border-radius: 50%;
            color: var(--pk);
            display: inline-flex;
            flex-shrink: 0;
            height: 28px;
            justify-content: center;
            width: 28px;
            font-size: .8rem;
        }

        .plp-cat-btn.active,
        .plp-cat-btn:hover {
            background: var(--pkl);
            border-color: var(--pk);
            color: var(--pk);
        }

        .plp-price-row {
            align-items: center;
            display: grid;
            gap: 8px;
            grid-template-columns: 1fr auto 1fr;
            margin-bottom: 8px;
        }

        .plp-price-row input,
        .plp-filter-block p {
            color: #5d5a70;
            font-family: 'Nunito', sans-serif;
            font-weight: 700;
            font-size: .85rem;
        }

        .plp-price-row input {
            background: #fff;
            border: 2px solid rgba(255, 214, 232, .95);
            border-radius: 12px;
            height: 40px;
            outline: none;
            padding: 0 10px;
            width: 100%;
        }

        .plp-price-row input:focus {
            border-color: var(--pk);
        }

        #plpPriceRange {
            accent-color: var(--pk);
            margin-top: 6px;
            width: 100%;
        }

        /* ── Results ── */
        .plp-results {
            min-width: 0;
        }

        .plp-toolbar {
            align-items: center;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(13, 0, 32, .06);
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 24px;
            padding: 16px 20px;
        }

        .plp-toolbar strong {
            color: var(--dk);
            font-family: 'Nunito', sans-serif;
            font-size: .92rem;
            font-weight: 900;
        }

        .plp-toolbar p {
            color: #626278;
            font-family: 'Nunito', sans-serif;
            font-size: .82rem;
            font-weight: 700;
            margin-top: 2px;
        }

        #plpSort {
            appearance: auto;
            background: #fff;
            border: 2px solid rgba(255, 214, 232, .95);
            border-radius: 14px;
            color: var(--dk);
            font-family: 'DM Sans', sans-serif;
            font-size: .88rem;
            min-width: 155px;
            outline: none;
            padding: 9px 12px;
        }

        .plp-toolbar-actions {
            align-items: center;
            display: flex;
            gap: 10px;
        }

        /* ── 3-column grid ── */
        .plp-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
            align-items: start;
        }

        /* Image fills card head — same as homepage */
        .plp-grid .pc-head {
            aspect-ratio: 3 / 2;
            height: 360px;
            padding: 0;
            overflow: hidden;
            position: relative;
        }

        .plp-grid .pc-head .pc-emoji.p-image {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }

        .plp-grid .pc-head .pc-emoji.p-image img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            
            padding: 0;
        }

        .plp-grid .pc {
            box-shadow: 0 4px 20px rgba(13, 0, 32, .06);
        }

        .plp-empty {
            margin-top: 24px;
            padding: 36px 24px;
            text-align: center;
            background: #fff;
            border: 1px dashed rgba(36, 45, 105, .18);
            border-radius: 22px;
        }

        .plp-empty h3 {
            font-family: 'Nunito', sans-serif;
            font-size: 1.1rem;
            color: var(--dk);
            margin-bottom: 8px;
        }

        .plp-empty p {
            color: #6e6a7b;
        }

        .plp-card[hidden] {
            display: none !important;
        }

        /* ── Responsive ── */
        @media (max-width: 1200px) {
            .plp-layout {
                grid-template-columns: 230px minmax(0, 1fr);
                gap: 20px;
            }

            .plp-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 16px;
            }
        }

        @media (max-width: 992px) {
            .plp-layout {
                grid-template-columns: 1fr;
            }

            .plp-sidebar {
                position: static;
                margin-bottom: 20px;
            }

            .plp-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 16px;
            }
        }

        @media (max-width: 700px) {
            .plp-page {
                padding-top: 28px;
            }

            .plp-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .plp-grid .pc-head {
                aspect-ratio: 3 / 2;
                height: 360px;
            }

            .plp-filter-backdrop {
                background: rgba(13, 0, 32, .48);
                display: block;
                inset: 0;
                opacity: 0;
                pointer-events: none;
                position: fixed;
                transition: opacity .24s ease;
                z-index: 9990;
            }

            .plp-filter-backdrop.is-open {
                opacity: 1;
                pointer-events: auto;
            }

            .plp-sidebar {
                border: 0;
                border-radius: 24px 24px 0 0;
                bottom: 0;
                box-shadow: 0 -18px 46px rgba(13, 0, 32, .18);
                left: 0;
                margin: 0;
                max-height: 86vh;
                overflow-y: auto;
                padding: 18px 16px 16px;
                position: fixed;
                right: 0;
                top: auto;
                transform: translateY(105%);
                transition: transform .28s ease;
                z-index: 9991;
            }

            .plp-sidebar.is-open {
                transform: translateY(0);
            }

            .plp-mobile-drawer-head {
                align-items: center;
                border-bottom: 1px solid rgba(13, 0, 32, .08);
                display: flex;
                justify-content: space-between;
                margin: -2px 0 14px;
                padding-bottom: 14px;
            }

            .plp-mobile-drawer-head span {
                color: var(--pk);
                display: block;
                font-family: 'Nunito', sans-serif;
                font-size: .76rem;
                font-weight: 900;
                letter-spacing: 1.6px;
                text-transform: uppercase;
            }

            .plp-mobile-drawer-head strong {
                color: var(--dk);
                display: block;
                font-family: 'Nunito', sans-serif;
                font-size: 1.12rem;
                font-weight: 900;
                margin-top: 2px;
            }

            #plpFilterClose {
                align-items: center;
                background: #f7f3f7;
                border: 0;
                border-radius: 50%;
                color: var(--dk);
                cursor: pointer;
                display: inline-flex;
                font-size: 1.35rem;
                font-weight: 800;
                height: 38px;
                justify-content: center;
                line-height: 1;
                width: 38px;
            }

            .plp-sidebar-head {
                padding-bottom: 12px;
            }

            .plp-sidebar-head h2 {
                display: none;
            }

            .plp-sidebar-head button {
                background: transparent;
                color: var(--pk);
                margin-left: auto;
                padding: 6px 2px;
            }

            .plp-filter-block {
                padding: 16px 0;
            }

            .plp-filter-block h3 {
                color: #1f1638;
                font-size: .78rem;
                letter-spacing: 1.4px;
                margin-bottom: 10px;
            }

            #plpSearch,
            .plp-price-row input {
                border: 1.5px solid #eee4ee;
                border-radius: 12px;
                height: 44px;
            }

            .plp-cat-btn {
                border: 0;
                border-radius: 12px;
                margin-bottom: 6px;
                min-height: 42px;
                padding: 7px 10px;
            }

            .plp-cat-btn.active,
            .plp-cat-btn:hover {
                background: #fff1f7;
                color: var(--pk);
            }

            .plp-cat-btn strong {
                height: 26px;
                width: 26px;
            }

            .plp-mobile-apply {
                background: var(--pk);
                border: 0;
                border-radius: 14px;
                box-shadow: 0 12px 24px rgba(255, 77, 143, .24);
                color: #fff;
                cursor: pointer;
                display: block;
                font-family: 'Nunito', sans-serif;
                font-size: .98rem;
                font-weight: 900;
                min-height: 48px;
                position: sticky;
                bottom: 0;
                width: 100%;
            }

            .plp-toolbar {
                align-items: stretch;
                border-radius: 16px;
                gap: 12px;
                padding: 14px;
            }

            .plp-toolbar-actions {
                display: grid;
                gap: 10px;
                grid-template-columns: 1fr 1fr;
            }

            #plpSort {
                min-width: 0;
                width: 100%;
            }

            .plp-mobile-filter-btn {
                align-items: center;
                background: var(--dk);
                border: 0;
                border-radius: 14px;
                color: #fff;
                cursor: pointer;
                display: inline-flex;
                font-family: 'Nunito', sans-serif;
                font-size: .9rem;
                font-weight: 900;
                justify-content: center;
                min-height: 43px;
                padding: 0 12px;
            }

            .plp-mobile-filter-btn span {
                border: 2px solid currentColor;
                border-left: 0;
                border-right: 0;
                display: inline-block;
                height: 12px;
                margin-right: 8px;
                position: relative;
                width: 15px;
            }

            .plp-mobile-filter-btn span::before {
                background: currentColor;
                content: '';
                height: 2px;
                left: 3px;
                position: absolute;
                right: 3px;
                top: 3px;
            }
        }

        @media (max-width: 480px) {
            .plp-page {
                padding: 36px 4% 60px;
            }

            .plp-grid {
                grid-template-columns: 1fr;
            }

            .plp-grid .pc-head {
                aspect-ratio: 3 / 2;
                height: auto;
            }

            .plp-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const cards = Array.from(document.querySelectorAll('.plp-card'));
            const grid = document.getElementById('plpGrid');
            const catBtns = Array.from(document.querySelectorAll('.plp-cat-btn'));
            const search = document.getElementById('plpSearch');
            const minInput = document.getElementById('plpMinPrice');
            const maxInput = document.getElementById('plpMaxPrice');
            const range = document.getElementById('plpPriceRange');
            const label = document.getElementById('plpPriceLabel');
            const sort = document.getElementById('plpSort');
            const clearBtn = document.getElementById('plpClear');
            const visible = document.getElementById('plpVisible');
            const rangeEl = document.getElementById('plpRange');
            const empty = document.getElementById('plpEmpty');
            const filterDrawer = document.getElementById('plpFilterDrawer');
            const filterBackdrop = document.getElementById('plpFilterBackdrop');
            const filterOpen = document.getElementById('plpFilterOpen');
            const filterClose = document.getElementById('plpFilterClose');
            const filterApply = document.getElementById('plpFilterApply');
            const initMax = range ? Number(range.max) : 0;
            const initMin = range ? Number(range.min) : 0;
            let activeCat = 'all';

            const setFilterDrawer = open => {
                if (!filterDrawer || !filterBackdrop) return;
                filterBackdrop.hidden = false;
                requestAnimationFrame(() => {
                    filterDrawer.classList.toggle('is-open', open);
                    filterBackdrop.classList.toggle('is-open', open);
                });
                filterOpen?.setAttribute('aria-expanded', open ? 'true' : 'false');
                document.body.style.overflow = open ? 'hidden' : '';
                if (!open) {
                    setTimeout(() => {
                        if (!filterBackdrop.classList.contains('is-open')) filterBackdrop.hidden = true;
                    }, 260);
                }
            };

            const sortCards = () => {
                if (!grid || !sort) return;
                [...cards].sort((a, b) => {
                    if (sort.value === 'price-low') return +a.dataset.price - +b.dataset.price;
                    if (sort.value === 'price-high') return +b.dataset.price - +a.dataset.price;
                    if (sort.value === 'name') return a.dataset.name.localeCompare(b.dataset.name);
                    if (sort.value === 'featured') return +b.dataset.featured - +a.dataset.featured;
                    return +a.dataset.order - +b.dataset.order;
                }).forEach(c => grid.appendChild(c));
            };

            const filter = () => {
                const q = (search?.value || '').trim().toLowerCase();
                const minP = Number(minInput?.value || initMin);
                const maxP = Number(maxInput?.value || initMax);
                let count = 0;

                catBtns.forEach(b => b.classList.toggle('active', b.dataset.cat === activeCat));

                cards.forEach(card => {
                    const price = Number(card.dataset.price);
                    const show = (activeCat === 'all' || card.dataset.cat === activeCat) &&
                        (!q || card.dataset.search.includes(q)) &&
                        price >= minP && price <= maxP;
                    card.hidden = !show;
                    if (show) count++;
                });

                if (label) label.textContent = Number(maxInput?.value || initMax).toLocaleString('en-IN');
                if (visible) visible.textContent = count;
                if (rangeEl) rangeEl.textContent = count ? `1-${count}` : '0-0';
                if (empty) empty.hidden = count > 0;
                sortCards();
            };

            catBtns.forEach(b => b.addEventListener('click', () => {
                activeCat = b.dataset.cat;
                filter();
            }));
            search?.addEventListener('input', filter);
            sort?.addEventListener('change', filter);
            minInput?.addEventListener('input', filter);
            maxInput?.addEventListener('input', () => {
                if (range) range.value = maxInput.value;
                filter();
            });
            range?.addEventListener('input', () => {
                if (maxInput) maxInput.value = range.value;
                filter();
            });
            filterOpen?.addEventListener('click', () => setFilterDrawer(true));
            filterClose?.addEventListener('click', () => setFilterDrawer(false));
            filterBackdrop?.addEventListener('click', () => setFilterDrawer(false));
            filterApply?.addEventListener('click', () => setFilterDrawer(false));
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && filterDrawer?.classList.contains('is-open')) {
                    setFilterDrawer(false);
                }
            });

            clearBtn?.addEventListener('click', () => {
                activeCat = 'all';
                if (search) search.value = '';
                if (minInput) minInput.value = initMin;
                if (maxInput) maxInput.value = initMax;
                if (range) range.value = initMax;
                if (sort) sort.value = 'default';
                filter();
            });

            filter();

            /* ── variant panel interaction ── */
            document.querySelectorAll('.pc-variant-panel').forEach(panel => {
                const card = panel.closest('.pc');
                const addBtn = card?.querySelector('.btn-add');
                const variants = (() => {
                    try {
                        return JSON.parse(card?.dataset.variants || '[]');
                    } catch {
                        return [];
                    }
                })();

                const findVariant = () => {
                    const sel = Object.fromEntries(
                        Array.from(panel.querySelectorAll('.pc-option-btn.active[data-attribute]'))
                        .map(b => [b.dataset.attribute, b.dataset.value])
                    );
                    return variants.find(v => Object.entries(sel).every(([k, val]) => String(v
                        .attributes?.[k] ?? '') === String(val))) || null;
                };

                const apply = () => {
                    const v = findVariant();
                    const sel = Array.from(panel.querySelectorAll(
                            '.pc-option-btn.active[data-attribute]'))
                        .map(b => `${b.dataset.attribute}: ${b.dataset.value}`).join(' / ');
                    const sp = panel.querySelector('.pc-selected-pill');
                    const sk = panel.querySelector('.pc-stock-pill');
                    const pl = card?.querySelector('[data-price-label]');

                    if (sp && sel) {
                        sp.textContent = sel;
                        sp.title = sel;
                    }
                    if (card) card.dataset.selectedVariantId = v?.id || '';
                    if (addBtn) addBtn.dataset.variantId = v?.id || '';

                    if (v && pl) {
                        const p = Number(v.price || 0),
                            cp = Number(v.compare_price || 0);
                        pl.innerHTML = `&#8377;${p.toLocaleString('en-IN', {maximumFractionDigits:0})}`;
                        if (cp > p) pl.insertAdjacentHTML('beforeend',
                            ` <s>&#8377;${cp.toLocaleString('en-IN', {maximumFractionDigits:0})}</s>`
                            );
                    }
                    if (sk && v) {
                        sk.classList.toggle('out', !v.available);
                        sk.textContent = v.available ? (v.track_stock ? `${v.stock_qty} pcs` :
                            'Available') : 'Out of stock';
                    }
                };

                apply();
                panel.querySelectorAll('.pc-option-btn[data-attribute]').forEach(btn => {
                    btn.addEventListener('click', e => {
                        e.preventDefault();
                        e.stopPropagation();
                        const attr = btn.dataset.attribute;
                        panel.querySelectorAll(
                                `.pc-option-btn[data-attribute="${CSS.escape(attr)}"]`)
                            .forEach(b => b.classList.toggle('active', b === btn));
                        apply();
                    });
                });
            });
        });
    </script>
@endpush
