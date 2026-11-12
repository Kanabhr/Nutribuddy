@extends('layouts.main')
@section('title', "NutriBuddy – India's #1 Kids Wellness Gummy")

@push('styles')
    <link rel="stylesheet"
        href="{{ asset('assets/css/product.css') }}?v={{ filemtime(public_path('assets/css/product.css')) }}">
@endpush

@section('content')
    @php
        $isKadhaProduct = str_contains(strtolower($product->name ?? ''), 'kadha') || str_contains(strtolower($product->slug ?? ''), 'kadha');

        $variantProducts = $product->variants
            ->filter(fn($variant) => $variant->is_active && !empty($variant->attributes))
            ->map(function ($variant) use ($isKadhaProduct) {
                if ($isKadhaProduct && is_array($variant->attributes)) {
                    $variant->attributes = array_filter(
                        $variant->attributes,
                        fn($key) => strtolower(trim((string) $key)) !== 'color',
                        ARRAY_FILTER_USE_KEY
                    );
                }
                return $variant;
            })
            ->values();
        $defVariant =
            $variantProducts->firstWhere('is_default', true) ?:
            $variantProducts->first() ?:
            null;

        $initialPrice = $defVariant ? $defVariant->display_price : $product->display_price;
        $initialComparePrice = $defVariant
            ? $defVariant->display_compare_price ?? 0
            : $product->display_compare_price ?? 0;

        $defVariantAttributes = $defVariant?->attributes ?? [];
        if ($isKadhaProduct && is_array($defVariantAttributes)) {
            $defVariantAttributes = array_filter(
                $defVariantAttributes,
                fn($key) => strtolower(trim((string) $key)) !== 'color',
                ARRAY_FILTER_USE_KEY
            );
        }
        $defAge = $product->age_group ?: $defVariantAttributes['Age Group'] ?? '';
        $defPack = $product->pack_size ?: $defVariantAttributes['Pack Size'] ?? '';
        $defFlavour = $product->flavor ?: $defVariantAttributes['Flavour'] ?? '';
        $fallbackProductImage = '';
        $productImageUrl = fn($imagePath = null) => $imagePath && \Illuminate\Support\Facades\Storage::disk('public')->exists($imagePath)
            ? route('storage.public', ['path' => $imagePath])
            : $fallbackProductImage;
        $mainGalleryImage = $product->images->first();
        $variantAttributeGroups = [];
        if (!empty($product->variant_types)) {
            $variantAttributeGroups = $product->variant_types;
        } else {
            foreach ($variantProducts as $variant) {
                foreach ($variant->attributes ?? [] as $name => $value) {
                    $variantAttributeGroups[$name] ??= [];
                    if ($value !== '' && !in_array($value, $variantAttributeGroups[$name], true)) {
                        $variantAttributeGroups[$name][] = $value;
                    }
                }
            }
        }
        if ($isKadhaProduct && is_array($variantAttributeGroups)) {
            $variantAttributeGroups = array_filter(
                $variantAttributeGroups,
                fn($key) => strtolower(trim((string) $key)) !== 'color',
                ARRAY_FILTER_USE_KEY
            );
        }
        $initialSelectedAttributes = $defVariantAttributes;
        foreach ($variantAttributeGroups as $attrName => $values) {
            $hasGroupSelection = false;
            foreach ($initialSelectedAttributes as $k => $v) {
                if (strcasecmp(trim((string) $k), trim((string) $attrName)) === 0) {
                    $hasGroupSelection = true;
                    break;
                }
            }
            if (!$hasGroupSelection && !empty($values)) {
                $initialSelectedAttributes[$attrName] = $values[0];
            }
        }
        $initialSelectedLabel = collect($initialSelectedAttributes)
            ->filter(fn($value) => trim((string) $value) !== '')
            ->map(fn($value, $key) => $key . ': ' . $value)
            ->implode(' / ');
        $frontendVariants = $variantProducts
            ->map(function ($variant) use ($product, $isKadhaProduct) {
                $attrs = $variant->attributes ?? [];
                if ($isKadhaProduct && is_array($attrs)) {
                    $attrs = array_filter(
                        $attrs,
                        fn($key) => strtolower(trim((string) $key)) !== 'color',
                        ARRAY_FILTER_USE_KEY
                    );
                }
                $price = (float) $variant->display_price;
                $comparePrice = (float) ($variant->display_compare_price ?? 0);
                $stockQty = (int) ($variant->inventory?->stock_qty ?? 0);
                return [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'attributes' => $attrs,
                    'price' => $price,
                    'compare_price' => $comparePrice,
                    'save_amount' => max(0, $comparePrice - $price),
                    'discount_percent' =>
                        $comparePrice > $price ? round((($comparePrice - $price) / $comparePrice) * 100) : 0,
                    'coins' => !empty($product->coins_reward)
                        ? (int) $product->coins_reward
                        : (int) round($price * 0.05),
                    'stock_qty' => $stockQty,
                    'track_stock' => (bool) ($variant->inventory?->track_stock ?? false),
                    'is_in_stock' => (bool) ($variant->inventory?->is_in_stock ?? true),
                    'available' =>
                        !$variant->inventory?->track_stock ||
                        (($variant->inventory?->is_in_stock ?? true) && $stockQty > 0),
                ];
            })
            ->values();
        $problemSolutionAsset = function (?string $path) {
            $path = trim((string) $path);
            if ($path === '') {
                return '';
            }

            if (\Illuminate\Support\Str::startsWith($path, ['img/', 'assets/'])) {
                return asset($path);
            }

            return \Illuminate\Support\Facades\Storage::disk('public')->exists($path)
                ? route('storage.public', ['path' => $path])
                : '';
        };
        $psBrandTitle = $product->ps_brand_title;
        $psProductTitle = $product->ps_product_title;
        $psTaglineItems = !empty($product->ps_tagline_items) ? $product->ps_tagline_items : [];
        $psLeftLabel = $product->ps_left_label;
        $psLeftCards = !empty($product->ps_left_cards) ? $product->ps_left_cards : [];
        $psCenterImage = $problemSolutionAsset($product->ps_center_image);
        $psRightLabel = $product->ps_right_label;
        $psRightCards = !empty($product->ps_right_cards) ? $product->ps_right_cards : [];
        $psShelfLeftImage = $problemSolutionAsset($product->ps_shelf_left_image);
        $psShelfRightImage = $problemSolutionAsset($product->ps_shelf_right_image);

        $transformDescription = $product->transform_description;
        $transformMainImage = $problemSolutionAsset($product->transform_main_image);
        $transformResults = is_array($product->transform_results) ? $product->transform_results : [];
        $transformColors = ['rgba(255,77,143,.12)', 'rgba(0,191,255,.12)', 'rgba(0,214,143,.12)', 'rgba(255,214,0,.15)'];
    @endphp


    <div class="pdp-hero">
        <!-- LEFT: Gallery -->
        <div class="pdp-gallery">
            <div class="main-img-wrap">
                @if ($product->is_featured)
                    <div class="badge-bestseller">Best Seller</div>
                @endif
                @if ($initialComparePrice > $initialPrice)
                    @php
                        $discount = round((($initialComparePrice - $initialPrice) / $initialComparePrice) * 100);
                    @endphp
                    <div class="badge-discount" id="pdpDiscountBadge">{{ $discount }}% OFF</div>
                @else
                    <div class="badge-discount d-none" id="pdpDiscountBadge"></div>
                @endif

                <div class="p-image pdp-zoom-source" style="display:block;line-height:1" data-zoom="2.25">
                    @if ($mainGalleryImage)
                        <img src="{{ $productImageUrl($mainGalleryImage->image_path) }}" alt="{{ $product->name }}"
                            id="mainPdpImage">
                    @endif
                    <div class="pdp-zoom-lens" aria-hidden="true"></div>
                </div>
            </div>
            <div class="pdp-zoom-result" aria-hidden="true"></div>
            <div class="thumb-row">
                @foreach ($product->images as $image)
                    @php
                        $thumbImageUrl = $productImageUrl($image->image_path);
                    @endphp
                    <div class="thumb {{ $loop->first ? 'active' : '' }}"
                        onclick="changePdpImage(this, '{{ $thumbImageUrl }}')">
                        <img src="{{ $thumbImageUrl }}" alt="{{ $product->name }}">
                    </div>
                @endforeach
            </div>
        </div>

        <!-- RIGHT: Info -->
        <div class="pdp-info">
            <h1 class="pdp-name">{{ $product->name }}</h1>
            <a href="#reviews" class="pdp-rating" style="text-decoration: none;">
                <div class="stars">
                    @php
                        $activeReviewsCount = $product->reviews->where('is_active', true)->count();
                        $rating =
                            $activeReviewsCount > 0 ? $product->reviews->where('is_active', true)->avg('rating') : 0;
                        $fullStars = floor($rating);
                    @endphp
                    @for ($i = 0; $i < 5; $i++)
                        @if ($i < $fullStars)
                            ★
                        @elseif ($i == $fullStars && ($rating - $fullStars) >= 0.5)
                            ★
                        @else
                            ☆
                        @endif
                    @endfor
                </div>
                <div class="rating-val">{{ number_format($rating, 1) }}</div>
                <div class="rating-divider"></div>
                <div class="rating-count">{{ number_format($activeReviewsCount) }}
                    Verified Reviews</div>
            </a>

            <!-- Price -->
            <div class="price-box">
                <div class="price-row">
                    <div class="price-now" id="pdpPriceNow">₹{{ number_format($initialPrice, 0) }}</div>
                    @if ($initialComparePrice > $initialPrice)
                        <div class="price-old" id="pdpPriceOld">₹{{ number_format($initialComparePrice, 0) }}</div>
                        @php $initialDiscount = round((($initialComparePrice - $initialPrice) / $initialComparePrice) * 100); @endphp
                        <div class="price-save" id="pdpPriceSave">Save
                            ₹{{ number_format($initialComparePrice - $initialPrice, 0) }} ({{ $initialDiscount }}% Off)
                        </div>
                    @else
                        <div class="price-old d-none" id="pdpPriceOld"></div>
                        <div class="price-save d-none" id="pdpPriceSave"></div>
                    @endif
                </div>
                <div class="price-note">Inclusive of all taxes · Free shipping on this order</div>
                <div class="cashback-row">
                    <span>🪙</span>
                    <span id="pdpCashback">Get
                        {{ !empty($product->coins_reward) ? $product->coins_reward : round($initialPrice * 0.05) }} NB
                        Coins on this purchase!</span>
                </div>
            </div>

            @if (!empty($variantAttributeGroups))
                <div class="pdp-variant-panel" id="pdpVariantPanel">
                    <div class="pdp-variant-head">
                        <div>
                            <h3 class="pdp-variant-title">Choose Your Option</h3>
                            <div class="pdp-variant-sub">Pick the exact flavour, pack, or size before adding to cart.</div>
                        </div>
                    </div>

                    <div class="pdp-variant-groups">
                        @foreach ($variantAttributeGroups as $attributeName => $values)
                            <div class="variant-block">
                                <div class="variant-label">{{ $attributeName }}</div>
                                <div class="pdp-option-row" data-attribute-group="{{ $attributeName }}">
                                    @foreach ($values as $value)
                                        @php
                                            $isSelected = false;
                                            foreach ($initialSelectedAttributes as $k => $v) {
                                                if (strcasecmp(trim((string) $k), trim((string) $attributeName)) === 0 && strcasecmp(trim((string) $v), trim((string) $value)) === 0) {
                                                    $isSelected = true;
                                                    break;
                                                }
                                            }
                                            if (!$isSelected && $loop->first) {
                                                $hasGroupSelection = false;
                                                foreach ($initialSelectedAttributes as $k => $v) {
                                                    if (strcasecmp(trim((string) $k), trim((string) $attributeName)) === 0) {
                                                        $hasGroupSelection = true;
                                                        break;
                                                    }
                                                }
                                                if (!$hasGroupSelection) {
                                                    $isSelected = true;
                                                }
                                            }
                                        @endphp
                                        <button type="button" class="pdp-option-btn {{ $isSelected ? 'active' : '' }}"
                                            data-attribute="{{ $attributeName }}" data-value="{{ $value }}">
                                            {{ $value }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="variant-container">
                    @if ($defFlavour)
                        <div class="variant-block">
                            <div class="variant-label">Flavour:</div>
                            <div class="variant-row">
                                <div class="vopt active">{{ $defFlavour }}</div>
                            </div>
                        </div>
                    @endif

                    @if ($defPack)
                        <div class="variant-block">
                            <div class="variant-label">Pack Size:</div>
                            <div class="variant-row">
                                <div class="vopt active">{{ $defPack }}</div>
                            </div>
                        </div>
                    @endif

                    @if ($defAge)
                        <div class="variant-block">
                            <div class="variant-label">Age Group:</div>
                            <div class="variant-row">
                                <div class="vopt active">{{ $defAge }}</div>
                            </div>
                        </div>
                    @endif

                    @if ($product->dosage)
                        <div class="variant-block">
                            <div class="variant-label">Dosage:</div>
                            <div class="variant-row">
                                <div class="vopt active">{{ $product->dosage }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="variant-block">
                <div class="variant-label">{{ $product->name }} Features </div>
                <div class="feature-slider-shell">
                    <button type="button" class="feature-slider-btn feature-slider-prev"
                        aria-label="Previous feature">‹</button>
                    <div class="variant-row" id="flavorRow">
                        @php
                            $tags = $product->tags ?? [];
                            // Backward compatibility for old string tags
                            if (is_string($tags)) {
                                $tags = array_map(function ($t) {
                                    preg_match(
                                        '/^([\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}])?\s*(.*)$/u',
                                        $t,
                                        $m,
                                    );
                                    return ['icon' => $m[1] ?? '', 'text' => $m[2] ?? $t];
                                }, array_filter(array_map('trim', explode(',', $tags))));
                            }
                        @endphp

                        @if (is_array($tags) && count($tags) > 0)
                            @foreach ($tags as $tag)
                                <div class="flavor-opt active">
                                    <div class="flavor-emoji">
                                        @if (!empty($tag['icon']))
                                            @php
                                                $isFilePath = str_contains($tag['icon'], 'tags/');
                                            @endphp
                                            @if ($isFilePath)
                                                <img src="{{ asset('storage/' . $tag['icon']) }}" alt=""
                                                    style="width: 28px; height: 28px; object-fit: contain;">
                                            @else
                                                <span style="font-size: 28px; display: inline-block;">{{ $tag['icon'] }}</span>
                                            @endif
                                        @else
                                            <span style="font-size: 28px; display: inline-block;">✨</span>
                                        @endif
                                    </div>
                                    <div class="flavor-name">{!! nl2br(e($tag['text'] ?? '')) !!}</div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <button type="button" class="feature-slider-btn feature-slider-next"
                        aria-label="Next feature">›</button>
                </div>
            </div>

            <!-- Quick Specs: Pack Size & Age -->
            <!-- <div class="pdp-specs-row" style="display:flex;gap:20px;margin: 20px 0;padding:15px;background:#f9f9f9;border-radius:12px;border:1px solid #eee;">
                                                    <div class="spec-item">
                                                        <div style="font-size:.72rem;color:#888;text-transform:uppercase;font-weight:800;margin-bottom:4px;letter-spacing:0.5px;">Pack Size</div>
                                                        <div id="pdpPackSize" style="font-size:1.05rem;color:var(--dk);font-weight:800">{{ $defPack }}</div>
                                                    </div>
                                                    <div style="width:1px;background:#ddd"></div>
                                                    <div class="spec-item">
                                                        <div style="font-size:.72rem;color:#888;text-transform:uppercase;font-weight:800;margin-bottom:4px;letter-spacing:0.5px;">Age Group</div>
                                                        <div id="pdpAgeGroup" style="font-size:1.05rem;color:var(--dk);font-weight:800">{{ $defAge }}</div>
                                                    </div>
                                                </div> -->


            <!-- Action Row (Quantity, Add to Cart, Buy Now) -->
            <div class="pdp-action-row">
                <!-- Quantity Selector -->
                <div class="pdp-qty-wrap">
                    <div class="variant-label">QUANTITY:</div>
                    <div class="pdp-qty-row">
                        <button type="button" class="qty-btn" id="pdpQtyMinus">−</button>
                        <input type="number" id="pdpQtyVal" value="1" min="1" readonly>
                        <button type="button" class="qty-btn" id="pdpQtyPlus">+</button>
                    </div>
                </div>

                <!-- CTAs -->
                <button type="button" class="btn-cart" id="pdpAddToCartBtn" onclick="handleAddToCart('{{ $product->id }}', this)">Add to Cart</button>
                <button type="button" class="btn-buy" id="pdpBuyNowBtn" onclick="handleBuyNow('{{ $product->id }}', this)">Buy Now</button>
            </div>

            <!-- Guarantees -->
            <div class="guarantees">
                <div class="guarantee">
                    <div class="g-icon">🚚</div>
                    <div class="g-title">Free Shipping</div>
                    <div class="g-sub">On orders ₹200+</div>
                </div>
                <div class="guarantee">
                    <div class="g-icon">🔄</div>
                    <div class="g-title">7-Day Return</div>
                    <div class="g-sub">No questions asked</div>
                </div>
                <div class="guarantee">
                    <div class="g-icon">🔒</div>
                    <div class="g-title">Secure Payment</div>
                    <div class="g-sub">UPI · Cards · COD</div>
                </div>
            </div>

            <!-- Product Highlights -->
            <div class="highlights">
                <h4>Why Parents Love {{ $product->name }}</h4>
                <ul class="highlight-list">
                    @php
                        $features = $product->short_description ? explode("\n", $product->short_description) : [];
                        $features = array_filter(array_map('trim', $features));
                    @endphp
                    @if (count($features) > 0)
                        @foreach (array_slice($features, 0, 6) as $feature)
                            <li>
                                <div class="hl-dot"></div>{{ preg_replace('/^[•\-\*]\s*/', '', $feature) }}
                            </li>
                        @endforeach
                    @else
                        {{-- Fallback --}}
                        <li>
                            <div class="hl-dot"></div>Ashwagandha (KSM-66), Vitamin D3, and Zinc support daily wellness.
                        </li>
                        <li>
                            <div class="hl-dot"></div>Tastes so good kids ask for it every morning.
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
    <!-- ════════════════════════════════════════════════
                                                 PRODUCT DESCRIPTION SECTION
                                            ════════════════════════════════════════════════ -->
    <!-- Product Description Section -->
    @if (!empty(trim((string) ($product->description ?? ''))))
        <section class="pdp-description-section">
            <div class="pdp-description-wrap">
                <div class="pdp-description-label">Product Details</div>
                <div class="pdp-description-copy">
                    {!! $product->description !!}
                </div>
            </div>
        </section>
    @endif
    <!-- ════════════════════════════════════════════════
                                                 NUTRIBUDDY INGREDIENT SECTION
                                            ════════════════════════════════════════════════ -->
    @if ($product->ingredients->isNotEmpty())
        <section id="nb-ingredients">

            <!-- Mesh BG -->
            <div class="nb-mesh">
                <div class="nb-blob nb-blob-1"></div>
                <div class="nb-blob nb-blob-2"></div>
                <div class="nb-blob nb-blob-3"></div>
                <div class="nb-blob nb-blob-4"></div>
                <!-- Stars -->
                <div class="nb-star" style="width:3px;height:3px;top:12%;left:8%;--dur:5s;--del:0s"></div>
                <div class="nb-star" style="width:4px;height:4px;top:28%;left:22%;--dur:7s;--del:1s"></div>
                <div class="nb-star" style="width:2px;height:2px;top:55%;left:75%;--dur:4s;--del:.5s"></div>
                <div class="nb-star" style="width:5px;height:5px;top:78%;left:90%;--dur:8s;--del:2s"></div>
                <div class="nb-star" style="width:3px;height:3px;top:40%;left:5%;--dur:6s;--del:1.5s"></div>
                <div class="nb-star" style="width:4px;height:4px;top:90%;left:40%;--dur:5s;--del:3s"></div>
                <div class="nb-star" style="width:2px;height:2px;top:18%;left:88%;--dur:9s;--del:.8s"></div>
                <div class="nb-star" style="width:3px;height:3px;top:65%;left:52%;--dur:6s;--del:2.5s"></div>
            </div>

            <!-- ── Header ── -->
            <div class="nb-ing-header">
                <div class="nb-eyebrow">🔬 Ingredient Transparency</div>
                <h2 class="nb-ing-title">
                    What Goes Into Every<br>
                    <span class="nb-acc-ye">{{ $product->name }}</span> <span class="nb-acc-pk">Gummy?</span>
                </h2>
                <p class="nb-ing-sub">Every single ingredient explained — from ancient Ayurvedic herbs to essential
                    vitamins
                    and
                    minerals. Click any ingredient to learn its full story.</p>
            </div>

            <!-- ── Category Filter (desktop) ── -->
            @php
                $productIngredients = $product->ingredients ?? collect();

                // Build category filters
                $categoryFilters = $productIngredients
                    ->groupBy(function ($ing) {
                        return $ing->category->name ?? 'General';
                    })
                    ->map(function ($group, $name) {
                        return [
                            'key' => \Illuminate\Support\Str::slug($name),
                            'name' => $name,
                            'count' => $group->count(),
                            'dot_color' => 'rgba(0,214,143,.6)',
                        ];
                    })
                    ->values();

                $totalIngredientCount = $productIngredients->count();

                // Build ingredient items for JS
                $ingredientItems = $productIngredients
                    ->map(function ($ing) {
                        return [
                            'id' => $ing->id,
                            'name' => $ing->main_heading,
                            'shortName' => $ing->short_heading,
                            'cat' => \Illuminate\Support\Str::slug($ing->category->name ?? 'general'),
                            'catLabel' => $ing->category->name ?? 'General',
                            'image' => $ing->icon_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($ing->icon_path)
                                ? asset('storage/' . $ing->icon_path)
                                : asset('img/gradient1.webp'),
                            'latin' => $ing->dosage_heading_one ?? '',
                            'dosage' => $ing->dosage_heading_two ?? '',
                            'desc' => $ing->description ?? '',
                            'benefits' => $ing->benefits->pluck('heading')->toArray(),
                        ];
                    })
                    ->values();

                // Summary stats
                $ingredientSummaryStats = [
                    ['value' => $totalIngredientCount, 'label' => 'Active Ingredients', 'color' => '#00d68f'],
                    [
                        'value' => $productIngredients
                            ->filter(fn($ing) => $ing->category || $ing->ingredient_category_id)
                            ->groupBy(fn($ing) => $ing->ingredient_category_id ?: $ing->category?->name ?? 'general')
                            ->count(),
                        'label' => 'Ingredient Categories',
                        'color' => '#ff8c00',
                    ],
                    ['value' => '100%', 'label' => 'Natural Sources', 'color' => '#00bfff'],
                    ['value' => '3rd Party', 'label' => 'Lab Tested', 'color' => '#ff4d8f'],
                ];
            @endphp
            <div class="nb-cat-row">
                <button class="nb-cat-pill nb-active" onclick="nbFilter('all',this)">
                    <span class="nb-cat-dot" style="background:rgba(255,255,255,.5)"></span>All
                    ({{ $totalIngredientCount }})
                </button>
                @foreach ($categoryFilters as $filter)
                    <button class="nb-cat-pill" onclick="nbFilter('{{ $filter['key'] }}',this)">
                        <span class="nb-cat-dot" style="background:{{ $filter['dot_color'] }}"></span>{{ $filter['name'] }}
                        ({{ $filter['count'] }})
                    </button>
                @endforeach
            </div>

            <!-- ── Mobile Tabs ── -->
            <div class="nb-mobile-tabs" id="nbMobTabs">
                <button class="nb-mob-tab nb-sel-mob" onclick="nbMobFilter('all',this)">All
                    ({{ $totalIngredientCount }})</button>
                @foreach ($categoryFilters as $filter)
                    <button class="nb-mob-tab" onclick="nbMobFilter('{{ $filter['key'] }}',this)">{{ $filter['name'] }}
                        ({{ $filter['count'] }})
                    </button>
                @endforeach
            </div>

            <!-- ── Mobile Accordion Cards ── -->
            <div class="nb-mob-cards" id="nbMobCards">
                <!-- Generated by JS -->
            </div>

            <!-- ── Desktop: Two-column layout ── -->
            <div class="nb-ing-body">

                <!-- LEFT LIST -->
                <div class="nb-list-panel">
                    <div class="nb-list-head">
                        <div class="nb-list-head-icon">📋</div>
                        <div>
                            <h4>Full Ingredient List</h4>
                            <p>{{ $totalIngredientCount }} ingredients · click to explore</p>
                        </div>
                    </div>
                    <div class="nb-list-scroll" id="nbList">
                        <!-- Rendered by JS -->
                    </div>
                </div>

                <!-- RIGHT DETAIL -->
                <div class="nb-detail-wrap">
                    <div class="nb-detail-empty" id="nbDetailEmpty">
                        <div class="nb-empty-ico">🔬</div>
                        <h3>Select an Ingredient</h3>
                        <p>Click any ingredient from the list on the left to discover its story, benefits, and why we chose
                            it
                            for
                            your child.</p>
                    </div>
                    <div id="nbDetailCards">
                        <!-- Rendered by JS -->
                    </div>
                </div>
            </div><!-- /nb-ing-body -->

            <!-- ── Summary Bar ── -->
            <div class="nb-summary-bar">
                <div class="nb-summary-inner">
                    @foreach ($ingredientSummaryStats as $stat)
                        <div class="nb-stat">
                            <div class="nb-stat-n" style="color:{{ $stat['color'] }}">{{ $stat['value'] }}</div>
                            <div class="nb-stat-l">{{ $stat['label'] }}</div>
                        </div>
                        @if (!$loop->last)
                            <div class="nb-sdiv"></div>
                        @endif
                    @endforeach
                </div>
            </div>

            <script id="nbIngredientsData" type="application/json">@json($ingredientItems)</script>

        </section>
    @endif
    <!-- end ingredients -->
    <!-- ══ HOW IT TRANSFORMS ══ -->
    <section class="section-wrap transform-section reveal">
        <div style="max-width:1200px;margin:0 auto;">
            <span class="sec-eye">Real Results</span>
            <h2 class="sec-title">Watch Your Child <span class="acc">Transform</span></h2>
            <p class="sec-sub">{{ $transformDescription }}</p>
            <div class="transform-grid">
                <div class="transform-visual">
                    <img src="{{ $transformMainImage }}" alt="{{ $product->name }} transformation results" loading="lazy"
                        decoding="async">
                </div>
                <div class="transform-list">
                    @foreach ($transformResults as $index => $result)
                        @php
                            $resultImage = $problemSolutionAsset($result['image'] ?? null, $transformDefaults[$index]['image'] ?? 'img/immune.png');
                            $resultColor = $transformColors[$index % count($transformColors)];
                        @endphp
                        <div class="tr-item">
                            <div class="tr-icon" style="background:{{ $resultColor }}">
                                <img src="{{ $resultImage }}" alt="{{ $result['title'] ?? '' }}" loading="lazy"
                                    decoding="async">
                            </div>
                            <div class="tr-body">
                                <div class="tr-title">{{ $result['title'] ?? '' }}</div>
                                <div class="tr-desc">{{ $result['description'] ?? '' }}</div>
                                @if (!empty($result['week']))
                                    <div class="tr-week">{{ $result['week'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!--  -->
    <!-- ══════════════════════════════
                                                 FEATURES — NO GELATIN etc.
                                            ══════════════════════════════ -->
    <section class="features-section reveal" id="features">
        <div class="feat-inner">
            <div class="feat-layout">
                <div>
                    <span class="sec-eye"> What's NOT in it</span>
                    <h2 class="feat-title">Pure as<br><span class="acc">Nature Intended</span> 🍃</h2>
                    <p class="feat-sub">We obsessed over every ingredient that goes in — and even more over what we keep
                        OUT.
                        Because your child's body deserves only the best.</p>
                    <div class="feat-list">
                        <div class="feat-item">
                            <div class="feat-item-icon" style="background:var(--mnl)"><img src="/img/vegan-1.png" alt="">
                            </div>
                            <div>
                                <div class="feat-item-title">Zero Gelatin — 100% Vegetarian</div>
                                <div class="feat-item-desc">Most international gummies use animal gelatin (pig or bovine).
                                    All
                                    NutriBuddy gummies use plant-based pectin. Completely safe for every Indian family
                                    regardless of
                                    dietary beliefs.</div>
                            </div>
                        </div>
                        <div class="feat-item">
                            <div class="feat-item-icon" style="background:var(--pkl)"><img src="/img/sug-1.png" alt="">
                            </div>
                            <div>
                                <div class="feat-item-title">No Refined Sugar</div>
                                <div class="feat-item-desc">We sweeten with Fructooligosaccharides (FOS), a prebiotic dietary fibre derived from chicory root. FOS feeds beneficial gut bacteria, supports digestive health, provides mild sweetness with fewer calories than regular sugar, and is not considered refined sugar.</div>
                            </div>
                        </div>

                        <div class="feat-item">
                            <div class="feat-item-icon" style="background:var(--yel)"><img src="/img/pro-1.png" alt="">
                            </div>
                            <div>
                                <div class="feat-item-title">No Artificial Colors or Flavors</div>
                                <div class="feat-item-desc">Our vibrant colors come from beetroot, turmeric, and spirulina.
                                    Our fruity
                                    burst flavors come from real fruit concentrates — not synthetic flavor chemicals tied to
                                    hyperactivity
                                    in children.</div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Comparison Table -->
                <div class="comparison-box">
                    <div class="comp-title">NutriBuddy vs. Other Brands</div>
                    <table class="comp-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th class="comp-us-head">NutriBuddy</th>
                                <th style="color:#aaa">Others</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="comp-us">
                                <td>Ayurvedic herbs</td>
                                <td><span class="check">✓</span></td>
                                <td><span class="cross">✗</span></td>
                            </tr>
                            <tr>
                                <td>Zero Gelatin</td>
                                <td class="comp-us"><span class="check">✓</span></td>
                                <td><span class="cross">✗</span></td>
                            </tr>
                            <tr class="comp-us">
                                <td>No refined sugar</td>
                                <td><span class="check">✓</span></td>
                                <td><span class="cross">✗</span></td>
                            </tr>
                            <tr>
                                <td>Third-party lab tested</td>
                                <td class="comp-us"><span class="check">✓</span></td>
                                <td><span class="cross">✗</span></td>
                            </tr>
                            <tr class="comp-us">
                                <td>Transparent batch results</td>
                                <td><span class="check">✓</span></td>
                                <td><span class="cross">✗</span></td>
                            </tr>
                            <tr>
                                <td>Pediatrician approved</td>
                                <td class="comp-us"><span class="check">✓</span></td>
                                <td><span class="cross">✗</span></td>
                            </tr>
                            <tr class="comp-us">
                                <td>Age 2+ safe</td>
                                <td><span class="check">✓</span></td>
                                <td><span class="cross">✗</span></td>
                            </tr>
                            <tr>
                                <td>Price per day</td>
                                <td class="comp-us" style="color:var(--mn);font-weight:800">~₹20</td>
                                <td>Varies</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
    <!-- ══ PEDIATRICIAN VIDEO ══ -->
    <section class="doc-section reveal">
        <div style="max-width:1100px;margin:0 auto;">
            <span class="sec-eye">Expert Endorsement</span>
            <h2 class="sec-title">What <span class="acc">Pediatricians</span> Say</h2>
            <p class="sec-sub" style="color:rgba(255,255,255,.5)">50+ certified pediatricians and nutritionists recommend
                NutriBuddy to their own patients and families.</p>
            <div class="doc-grid">
                <div>
                    <div class="doc-video-wrap">
                        <div class="doc-play">▶</div>
                        <div class="doc-video-label">Dr. Anita Nair — Pediatrician, Bangalore<br>Watch her recommendation
                            (2 min)
                        </div>
                    </div>
                    <div style="margin-top:16px;display:flex;gap:20px;justify-content:center;">
                        <div style="text-align:center;">
                            <div style="font-family:'Fredoka One',cursive;font-size:1.8rem;color:var(--pk)">50+</div>
                            <div style="color:rgba(255,255,255,.5);font-size:.78rem">Pediatricians</div>
                        </div>
                        <div style="text-align:center;">
                            <div style="font-family:'Fredoka One',cursive;font-size:1.8rem;color:var(--ye)">3 Yrs</div>
                            <div style="color:rgba(255,255,255,.5);font-size:.78rem">R&D Per Product</div>
                        </div>
                        <div style="text-align:center;">
                            <div style="font-family:'Fredoka One',cursive;font-size:1.8rem;color:var(--mn)">10K+</div>
                            <div style="color:rgba(255,255,255,.5);font-size:.78rem">Happy Families</div>
                        </div>
                    </div>
                </div>
                <div class="doc-info">
                    <div class="doc-card">
                        <div class="doc-name">Dr. Anita Nair</div>
                        <div class="doc-cred">MBBS, DCH · Pediatrician, Bangalore · 18 yrs experience</div>
                        <div class="doc-quote">As a pediatrician, I'm very selective about what I recommend. NutriBuddy's
                            completely
                            transparent formulas and third-party testing give me total confidence to recommend it to my
                            patients.
                        </div>
                    </div>
                    <div class="doc-card">
                        <div class="doc-name">Dr. Rajesh Kapoor</div>
                        <div class="doc-cred">MD Pediatrics · AIIMS Alumni · Delhi</div>
                        <div class="doc-quote">The KSM-66® Ashwagandha dosage is clinically appropriate and the
                            bioavailability of
                            their Zinc Bisglycinate is genuinely impressive. This is science-backed, not just marketing.
                        </div>
                    </div>
                    <div class="doc-card">
                        <div class="doc-name">Dt. Meena Iyer</div>
                        <div class="doc-cred">Certified Pediatric Nutritionist · Chennai</div>
                        <div class="doc-quote">I give it to my own children. The natural fruit extracts, zero artificial
                            additives,
                            and the Ayurvedic formulation align perfectly with what I recommend to every family I counsel.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>




    <!-- SECTION -->
    <section class="ps-section ps-problems-section">
        <div class="ps-inner">

            @if(!empty($product->problem_cards) && count($product->problem_cards) > 0)
            <!-- HEADER -->
            <div class="ps-header reveal">
                <div class="eyebrow">{{ $product->problem_heading ?? 'The Real Picture' }}</div>
                <h2 class="ps-title">{!! $product->problem_title ?? 'Kids Face <span class="acc">Real Problems</span> <br>We Built a <span class="acc2">Real Solution</span>' !!}</h2>
                <p class="ps-sub">{{ $product->problem_subtitle ?? "Today's kids miss out on essential nutrition every day. We see the gap — and we've closed it." }}</p>
            </div>
            <div class="problem-grid">
                @php
                    // CSS classes mapped exactly to what the original design had
                    $pcClasses = ['pc1', 'pc2', 'pc3', 'pc1'];
                    $dClasses = ['d1', 'd2', 'd3', 'd1'];
                @endphp
                @foreach($product->problem_cards as $index => $card)
                    @if(!empty($card['title']) || !empty($card['description']) || !empty($card['icon']))
                    @php
                        $iconUrl = !empty($card['icon']) ? (Str::startsWith($card['icon'], ['img/', 'assets/']) ? asset($card['icon']) : asset('storage/' . $card['icon'])) : '';
                    @endphp
                    <div class="prob-card {{ $pcClasses[$index % 4] }} reveal {{ $dClasses[$index % 4] }}">
                        <div class="prob-icon pi{{ $index + 1 }}">
                            @if($iconUrl)
                                <img src="{{ $iconUrl }}" alt="{{ $card['title'] }}" loading="lazy" decoding="async">
                            @endif
                        </div>
                        <div class="prob-name">{{ $card['title'] }}</div>
                        <p class="prob-text">{{ $card['description'] }}</p>
                    </div>
                    @endif
                @endforeach
            </div>
            @endif

            <!-- DIVIDER -->
            <div class="ps-divider reveal">
                <div class="div-arrow">↓</div>
                <div class="div-badge"> Here's Our Answer</div>
                <div class="div-arrow">↓</div>
            </div>

            <section class="nb__section reveal">
                <div class="nb__leaf-cluster nb__leaf-cluster--tl" aria-hidden="true"></div>
                <div class="nb__leaf-cluster nb__leaf-cluster--tr" aria-hidden="true"></div>
                <div class="nb__leaf-cluster nb__leaf-cluster--bl" aria-hidden="true"></div>
                <div class="nb__leaf-cluster nb__leaf-cluster--br" aria-hidden="true"></div>

                <div class="nb__wrap">
                    <header class="nb__headline">
                        <h2 class="nb__brand-title">{{ $psBrandTitle }}</h2>
                        <h3 class="nb__product-title">{{ $psProductTitle }}</h3>
                        <p class="nb__tagline">
                            @foreach ($psTaglineItems as $taglineIndex => $taglineItem)
                                @if ($taglineIndex > 0)
                                    <span>&bull;</span>
                                @endif
                                {{ $taglineItem }}
                            @endforeach
                        </p>
                    </header>

                    <div class="nb__main-grid">
                        <aside class="nb__ingredients-col">
                            <div class="nb__corner-label">{!! nl2br(e($psLeftLabel)) !!}</div>

                            @foreach ($psLeftCards as $ingredientIndex => $ingredientCard)
                                @php
                                    $ingredientIcon = $problemSolutionAsset($ingredientCard['icon'] ?? '');
                                    $ingredientTitle = $ingredientCard['title'] ?? '';
                                    $ingredientText = $ingredientCard['text'] ?? '';
                                @endphp
                                <article class="nb__ingredient-card">
                                    <div class="nb__ingredient-img">
                                        <img src="{{ $ingredientIcon }}" alt="{{ $ingredientTitle }}">
                                    </div>
                                    <div class="nb__ingredient-text">
                                        <h4>{{ $ingredientTitle }}</h4>
                                        <p>{!! nl2br(e($ingredientText)) !!}</p>
                                    </div>
                                </article>
                            @endforeach
                        </aside>

                        <div class="nb__product-center">
                            @if ($psCenterImage)
                                <img class="nb__product-image" src="{{ $psCenterImage }}"
                                    alt="{{ $psBrandTitle }} {{ $psProductTitle }} product">
                            @endif
                        </div>

                        <aside class="nb__features-col">
                            <div class="nb__side-label">{!! nl2br(e($psRightLabel)) !!}</div>

                            <div class="nb__feature-panel">
                                @foreach ($psRightCards as $featureIndex => $featureCard)
                                    @php
                                        $featureIcon = $problemSolutionAsset($featureCard['icon'] ?? '');
                                        $featureTitle = $featureCard['title'] ?? '';
                                        $featureText = $featureCard['text'] ?? '';
                                    @endphp
                                    <article class="nb__feature-card">
                                        <div class="nb__feature-icon">
                                            <img src="{{ $featureIcon }}" alt="{{ $featureTitle }}">
                                        </div>
                                        <div class="nb__feature-text">
                                            <h4>{{ $featureTitle }}</h4>
                                            <p>{{ $featureText }}</p>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </aside>
                    </div>

                    <div class="nb__ingredient-shelf" aria-hidden="true">
                        <img class="nb__shelf-img nb__shelf-img--amla" src="{{ $psShelfLeftImage }}" alt="">
                        <img class="nb__shelf-img nb__shelf-img--turmeric" src="{{ $psShelfRightImage }}" alt="">
                    </div>

                    <footer class="nb__trust-strip">
                        <div class="nb__trust-badge"><span class="nb__trust-badge-icon"><img
                                    src="{{ asset('img/vegan-1.png') }}" alt="Ayurvedic wisdom"></span> Ayurvedic<br>Wisdom
                        </div>
                        <div class="nb__trust-badge"><span class="nb__trust-badge-icon"><img
                                    src="{{ asset('img/new-btn-2.png') }}" alt="Safe and trusted formula"></span> Safe &amp;
                            Trusted<br>Formula</div>
                        <div class="nb__trust-badge"><span class="nb__trust-badge-icon"><img
                                    src="{{ asset('img/pro-1.png') }}" alt="No artificial colors"></span> No
                            Artificial<br>Colors</div>
                        <div class="nb__trust-badge"><span class="nb__trust-badge-icon"><img
                                    src="{{ asset('img/new-btn-4.png') }}" alt="Made with love"></span> Made With<br>Love
                        </div>
                    </footer>
                </div>
            </section>

            @if (!empty($product->benefits_cards))
            <!-- DIVIDER -->
            <div class="ps-divider reveal">
                <div class="div-arrow">↓</div>
                <div class="div-badge"> The Nutribuddy Benefits</div>
                <div class="div-arrow">↓</div>
            </div>

            <div class="problem-grid">
                @php
                    $nbClasses = [
                        ['pc' => 'pc1', 'd' => 'd1', 'pi' => 'pi1'],
                        ['pc' => 'pc2', 'd' => 'd2', 'pi' => 'pi2'],
                        ['pc' => 'pc3', 'd' => 'd3', 'pi' => 'pi3'],
                        ['pc' => 'pc1', 'd' => 'd1', 'pi' => 'pi4'],
                    ];
                @endphp
                @foreach($product->benefits_cards as $index => $card)
                    @php
                        $c = $nbClasses[$index % 4];
                        $iconUrl = !empty($card['icon']) ? asset('storage/' . $card['icon']) : '';
                    @endphp
                    <div class="prob-card {{ $c['pc'] }} reveal {{ $c['d'] }}">
                        @if($iconUrl)
                        <div class="prob-icon {{ $c['pi'] }}"><img src="{{ $iconUrl }}" alt="{{ $card['title'] ?? '' }}" loading="lazy" decoding="async"></div>
                        @endif
                        <div class="prob-name">{{ $card['title'] ?? '' }}</div>
                        <p class="prob-text">{{ $card['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
            @endif
            <!-- SOLUTION -->


            <!-- <div class="block-label reveal">
                                                    <div class="blabel bl-sol">✅ NutriBuddy Solution</div>
                                                    <div class="bline g"></div>
                                                </div> -->
    </section>





    <!-- ════════════════════════════════════════════════   ══════════════════════════════════════════
                                                     PARENT REVIEWS
                                                ══════════════════════════════════════════ -->
       @include('partials.parent-reviews')

    @include('frontend.storefront.products.partials.leave-review')

    <!-- ══════════════════════════════════════════
                                                     FAQ
                                                ══════════════════════════════════════════ -->
    @include('partials.faq-section')

    <div class="newsletter reveal">
        <span class="sec-eye">Stay in the Loop</span>
        <h2 class="sec-title">Wellness Tips for Your Little Ones</h2>
        <p class="nl-sub">Join 25,000+ parents getting Ayurvedic parenting tips, exclusive discounts & early product access
            every week.</p>
        <form class="nl-form newsletterSubscribeForm" action="{{ route('newsletter.subscribe') }}" method="POST">
            @csrf
            <input type="hidden" name="source" value="newsletter_block">
            <input class="nl-input" type="email" name="email" maxlength="50" placeholder="Enter your email address"
                required>
            <button class="hbtn hbtn-main" type="submit" style="padding:13px 28px;font-size:.9rem">Subscribe</button>
            <div class="newsletterSubscribeMessage"
                style="display:none;width:100%;margin-top:8px;font-size:.82rem;font-weight:800;text-align:center;"></div>
        </form>
    </div>

    <!-- Mobile Sticky Bottom Action Bar -->
    <div class="pdp-mobile-sticky-bar">
        <button type="button" class="pdp-sticky-btn pdp-sticky-cart" onclick="handleAddToCart('{{ $product->id }}', this)">
            Add to Cart
        </button>
        <button type="button" class="pdp-sticky-btn pdp-sticky-buy" onclick="handleBuyNow('{{ $product->id }}', this)">
            Buy Now
        </button>
    </div>
@endsection
@push('scripts')

    @php

        $nbPdpConfig = [

            'enabled' => true,

            'selectedVariantId' => (string) ($defVariant?->id ?? ''),

            'variants' => $frontendVariants,

            'selectedAttributes' => $initialSelectedAttributes,

            'isLoggedIn' => auth()->check(),

            'checkoutUrl' => route('checkout.index'),

            'fallbackCartMeta' => [

                'product_name' => $product->name,

                'variant_name' => '',

                'image' => $productImageUrl($mainGalleryImage?->image_path),

                'unit_price' => (float) $initialPrice,

                'product_url' => request()->path(),

            ],

        ];

    @endphp

    <script id="nbPdpConfig" type="application/json">@json($nbPdpConfig)</script>

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            document.querySelectorAll('.ps-problems-section .problem-grid').forEach(function (grid, gridIndex) {

                const cards = Array.from(grid.querySelectorAll('.prob-card'));



                if (cards.length < 2 || grid.dataset.sliderReady === 'true') {

                    return;

                }



                grid.dataset.sliderReady = 'true';

                grid.classList.add('problem-slider-track');

                grid.setAttribute('aria-label', gridIndex === 0 ? 'Kids real problems slider' : 'Nutribuddy benefits slider');



                const controls = document.createElement('div');

                controls.className = 'problem-slider-controls';



                const prevBtn = document.createElement('button');

                prevBtn.type = 'button';

                prevBtn.className = 'problem-slider-btn problem-slider-prev';

                prevBtn.setAttribute('aria-label', 'Previous slide');

                prevBtn.textContent = '\u2039';



                const dots = document.createElement('div');

                dots.className = 'problem-slider-dots';

                dots.setAttribute('aria-hidden', 'true');



                const dotBtns = cards.map(function (_, index) {

                    const dot = document.createElement('button');

                    dot.type = 'button';

                    dot.className = 'problem-slider-dot';

                    dot.tabIndex = -1;

                    dot.addEventListener('click', function () {

                        scrollToCard(index);

                    });

                    dots.appendChild(dot);

                    return dot;

                });



                const nextBtn = document.createElement('button');

                nextBtn.type = 'button';

                nextBtn.className = 'problem-slider-btn problem-slider-next';

                nextBtn.setAttribute('aria-label', 'Next slide');

                nextBtn.textContent = '\u203A';



                controls.append(prevBtn, dots, nextBtn);

                grid.insertAdjacentElement('afterend', controls);



                function getActiveIndex() {

                    const gridLeft = grid.getBoundingClientRect().left;

                    return cards.reduce(function (closestIndex, card, index) {

                        const currentDistance = Math.abs(card.getBoundingClientRect().left - gridLeft);

                        const closestDistance = Math.abs(cards[closestIndex].getBoundingClientRect().left - gridLeft);

                        return currentDistance < closestDistance ? index : closestIndex;

                    }, 0);

                }



                function scrollToCard(index) {

                    const card = cards[Math.max(0, Math.min(index, cards.length - 1))];

                    grid.scrollTo({

                        left: card.offsetLeft - cards[0].offsetLeft,

                        behavior: 'smooth'

                    });

                }



                function updateControls() {

                    const activeIndex = getActiveIndex();

                    prevBtn.disabled = activeIndex === 0;

                    nextBtn.disabled = activeIndex === cards.length - 1;

                    dotBtns.forEach(function (dot, index) {

                        dot.classList.toggle('is-active', index === activeIndex);

                    });

                }



                prevBtn.addEventListener('click', function () {

                    scrollToCard(getActiveIndex() - 1);

                });



                nextBtn.addEventListener('click', function () {

                    scrollToCard(getActiveIndex() + 1);

                });



                grid.addEventListener('scroll', function () {

                    window.requestAnimationFrame(updateControls);

                }, { passive: true });



                window.addEventListener('resize', updateControls);

                updateControls();

            });



            document.querySelectorAll('.transform-list').forEach(function (track) {

                const cards = Array.from(track.querySelectorAll('.tr-item'));



                if (cards.length < 2 || track.dataset.sliderReady === 'true') {

                    return;

                }



                track.dataset.sliderReady = 'true';

                track.setAttribute('aria-label', 'Transformation results slider');



                const controls = document.createElement('div');

                controls.className = 'transform-slider-controls';



                const prevBtn = document.createElement('button');

                prevBtn.type = 'button';

                prevBtn.className = 'transform-slider-btn transform-slider-prev';

                prevBtn.setAttribute('aria-label', 'Previous result');

                prevBtn.textContent = '\u2039';



                const dots = document.createElement('div');

                dots.className = 'transform-slider-dots';

                dots.setAttribute('aria-hidden', 'true');



                const dotBtns = cards.map(function (_, index) {

                    const dot = document.createElement('button');

                    dot.type = 'button';

                    dot.className = 'transform-slider-dot';

                    dot.tabIndex = -1;

                    dot.addEventListener('click', function () {

                        scrollToCard(index);

                    });

                    dots.appendChild(dot);

                    return dot;

                });



                const nextBtn = document.createElement('button');

                nextBtn.type = 'button';

                nextBtn.className = 'transform-slider-btn transform-slider-next';

                nextBtn.setAttribute('aria-label', 'Next result');

                nextBtn.textContent = '\u203A';



                controls.append(prevBtn, dots, nextBtn);

                track.insertAdjacentElement('afterend', controls);



                function getActiveIndex() {

                    const trackLeft = track.getBoundingClientRect().left;

                    return cards.reduce(function (closestIndex, card, index) {

                        const currentDistance = Math.abs(card.getBoundingClientRect().left - trackLeft);

                        const closestDistance = Math.abs(cards[closestIndex].getBoundingClientRect().left - trackLeft);

                        return currentDistance < closestDistance ? index : closestIndex;

                    }, 0);

                }



                function scrollToCard(index) {

                    const card = cards[Math.max(0, Math.min(index, cards.length - 1))];

                    track.scrollTo({

                        left: card.offsetLeft - cards[0].offsetLeft,

                        behavior: 'smooth'

                    });

                }



                function updateControls() {

                    const activeIndex = getActiveIndex();

                    prevBtn.disabled = activeIndex === 0;

                    nextBtn.disabled = activeIndex === cards.length - 1;

                    dotBtns.forEach(function (dot, index) {

                        dot.classList.toggle('is-active', index === activeIndex);

                    });

                }



                prevBtn.addEventListener('click', function () {

                    scrollToCard(getActiveIndex() - 1);

                });



                nextBtn.addEventListener('click', function () {

                    scrollToCard(getActiveIndex() + 1);

                });



                track.addEventListener('scroll', function () {

                    window.requestAnimationFrame(updateControls);

                }, { passive: true });



                window.addEventListener('resize', updateControls);

                updateControls();

            });





        });

    </script>

@endpush
