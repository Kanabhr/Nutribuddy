@extends('layouts.main')
@section('title', "NutriBuddy – India's #1 Kids Wellness Gummy")

@push('styles')
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&display=swap">
    <link rel="stylesheet"
        href="{{ asset('assets/css/product.css') }}?v={{ filemtime(public_path('assets/css/product.css')) }}">
@endpush

@section('content')
    @php
        $isKadhaProduct = str_contains(strtolower($product->name ?? ''), 'kadha') || str_contains(strtolower($product->slug ?? ''), 'kadha');
        $isBrainBoosterProduct = str_contains(strtolower($product->name ?? ''), 'brain booster') || str_contains(strtolower($product->slug ?? ''), 'brain-booster');

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
        $purchaseUnitLabel = '1 box (30 gummies)';
        $productKey = strtolower((string) ($product->slug ?: $product->name));
        $fallbackProductImage = str_contains($productKey, 'kadha')
            ? asset('img/newwkadha.png')
            : ($isBrainBoosterProduct
                ? asset('assets/brain-gallery/01-meet-your-brain-buddy-640.webp')
                : asset('img/product2.png'));
        $productImageUrl = function ($imagePath = null) use ($fallbackProductImage) {
            $imagePath = trim((string) $imagePath);

            if ($imagePath === '') {
                return $fallbackProductImage;
            }

            if (\Illuminate\Support\Str::startsWith($imagePath, ['img/', 'assets/'])) {
                return asset($imagePath);
            }

            return \Illuminate\Support\Facades\Storage::disk('public')->exists($imagePath)
                ? route('storage.public', ['path' => $imagePath])
                : $fallbackProductImage;
        };
        $mainGalleryImage = $product->images->first();
        $familyCatalog = collect([$product])->concat($relatedProducts ?? collect())->unique('id')->values();
        $familyProducts = $familyCatalog->take(3);
        $familyProductImage = function ($familyProduct) use ($product, $productImageUrl) {
            $image = $familyProduct->id === $product->id
                ? ($familyProduct->images->firstWhere('is_primary', true) ?: $familyProduct->images->first())
                : ($familyProduct->primaryImage ?: $familyProduct->images->first());
            $imagePath = $familyProduct->card_image_path ?: $image?->image_path;

            return $productImageUrl($imagePath) ?: asset('img/product2.png');
        };
        $familyImageFor = function (string $productKey) use ($familyCatalog, $familyProductImage, $product) {
            $familyProduct = $familyCatalog->first(fn($candidate) => str_contains(
                strtolower($candidate->name . ' ' . $candidate->slug),
                $productKey
            ));

            return $familyProductImage($familyProduct ?: $product);
        };
        $familyComboPacks = [
            [
                'name' => 'Complete Care',
                'tag' => 'Everyday essentials',
                'description' => 'Brain Booster + Multivitamin Immunity',
                'price' => 1199,
                'mrp' => 1348,
                'renewal' => 1089,
                'products' => ['brain booster', 'immunity'],
            ],
            [
                'name' => 'Exam Ready',
                'tag' => 'Study-season routine',
                'description' => 'Brain Booster + Tasty Kadha',
                'price' => 949,
                'mrp' => 1048,
                'renewal' => 869,
                'products' => ['brain booster', 'kadha'],
            ],
            [
                'name' => 'Sibling Pack',
                'tag' => 'For 2 kids',
                'description' => '2 × Brain Booster Gummies',
                'price' => 1249,
                'mrp' => 1398,
                'renewal' => 1129,
                'products' => ['brain booster', 'brain booster'],
            ],
            [
                'name' => 'Total Nutrition Kit',
                'tag' => 'The full family',
                'description' => 'Brain Booster + Immunity + Tasty Kadha',
                'price' => 1499,
                'mrp' => 1697,
                'renewal' => 1389,
                'products' => ['brain booster', 'immunity', 'kadha'],
            ],
        ];
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
                        onclick="changePdpImage(this, '{{ $thumbImageUrl }}', {{ $loop->iteration }})"
                        aria-label="View product image {{ $loop->iteration }}" role="button" tabindex="0">
                        <img src="{{ $thumbImageUrl }}" alt="{{ $product->name }}">
                    </div>
                @endforeach
            </div>
            <div class="pdp-gallery-caption" data-gallery-caption>
                <span><b data-gallery-count>1 / {{ max(1, $product->images->count()) }}</b><small>Meet your buddy</small></span>
                <div class="pdp-gallery-controls">
                    <button type="button" data-gallery-prev aria-label="Previous image">‹</button>
                    <button type="button" data-gallery-next aria-label="Next image">›</button>
                    <button type="button" class="pdp-gallery-zoom" data-gallery-zoom aria-label="Zoom image">⛶ <span>Zoom</span></button>
                </div>
            </div>
            <dialog class="pdp-gallery-dialog" data-gallery-dialog aria-label="Product image zoom">
                <button type="button" data-gallery-close aria-label="Close zoom">×</button>
                <img src="{{ $mainGalleryImage ? $productImageUrl($mainGalleryImage->image_path) : '' }}" alt="{{ $product->name }}">
            </dialog>
        </div>

        <!-- RIGHT: Info -->
        <div class="pdp-info">
            <div class="pdp-title-line">
                <h1 class="pdp-name">{{ $isBrainBoosterProduct ? 'Brain Booster Gummies' : $product->name }}</h1>
                <a href="#reviews" class="pdp-rating" style="text-decoration: none;">
                    @if ($isBrainBoosterProduct)
                        <span class="pdp-rating-summary">
                            <span class="stars" aria-label="5 out of 5 stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                            <b class="rating-val">5.0</b>
                        </span>
                        <small class="rating-count">1 review · design sample</small>
                    @else
                        @php
                            $activeReviews = $product->reviews->where('is_active', true);
                            $activeReviewsCount = $activeReviews->count();
                            $rating = $activeReviewsCount > 0 ? $activeReviews->avg('rating') : 0;
                            $fullStars = floor($rating);
                        @endphp
                        <span class="pdp-rating-summary">
                            <span class="stars" aria-label="{{ number_format($rating, 1) }} out of 5 stars">
                                @for ($i = 0; $i < 5; $i++)
                                    @if ($i < $fullStars || ($i == $fullStars && ($rating - $fullStars) >= 0.5))&#9733;@else&#9734;@endif
                                @endfor
                            </span>
                            <b class="rating-val">{{ number_format($rating, 1) }}</b>
                        </span>
                        <small class="rating-count">{{ number_format($activeReviewsCount) }}
                            {{ \Illuminate\Support\Str::plural('review', $activeReviewsCount) }}</small>
                    @endif
                </a>
            </div>
            @if ($isBrainBoosterProduct)
                <p class="pdp-subtitle">Algal DHA, Brahmi &amp; Shankhpushpi, with essential nutrients.</p>
            @elseif (filled($product->problem_subtitle))
                <p class="pdp-subtitle">{{ $product->problem_subtitle }}</p>
            @endif

            <!-- Price -->
            <div class="price-box">
                <div class="price-row pdp-price-line">
                    <div class="price-now" id="pdpPriceNow">₹{{ number_format($initialPrice, 0) }}</div>
                    @if ($initialComparePrice > $initialPrice)
                        <div class="pdp-mrp"><span>MRP</span><s class="price-old" id="pdpPriceOld">₹{{ number_format($initialComparePrice, 0) }}</s></div>
                        @php $initialDiscount = round((($initialComparePrice - $initialPrice) / $initialComparePrice) * 100); @endphp
                        <div class="price-save" id="pdpPriceSave">Save
                            ₹{{ number_format($initialComparePrice - $initialPrice, 0) }} ({{ $initialDiscount }}% Off)
                        </div>
                    @else
                        <div class="price-old d-none" id="pdpPriceOld"></div>
                        <div class="price-save d-none" id="pdpPriceSave"></div>
                    @endif
                    <div class="price-note">Inclusive of all taxes</div>
                </div>
                <button type="button" class="cashback-row pdp-coins-strip" id="pdpCoinsTrigger" aria-haspopup="dialog" aria-controls="pdpCoinsModal">
                    <svg class="pdp-coins-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="8" cy="15" r="6" stroke="currentColor" stroke-width="1.8" />
                        <path d="M8 11.8v6.4M10 13.2c-.4-.7-1-1-2-1s-1.8.5-1.8 1.3c0 2 3.8.8 3.8 2.8 0 .8-.8 1.4-2 1.4-.9 0-1.7-.4-2.1-1.1M14 3.5a7 7 0 0 1 6.5 6.5M17 3.5h-3.5V7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <b id="pdpCashback">Earn
                        {{ !empty($product->coins_reward) ? $product->coins_reward : round($initialPrice * 0.05) }} NB
                        Coins on this purchase</b>
                    <svg class="pdp-coins-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>

            <div class="pdp-coins-modal" id="pdpCoinsModal" hidden aria-hidden="true">
                <div class="pdp-coins-backdrop" data-close-coins-modal></div>
                <section class="pdp-coins-dialog" role="dialog" aria-modal="true" aria-labelledby="pdpCoinsTitle">
                    <div class="pdp-coins-dialog-head">
                        <h2 id="pdpCoinsTitle">A little thank-you from NutriBuddy</h2>
                        <button type="button" class="pdp-coins-close" aria-label="Close rewards details" data-close-coins-modal>×</button>
                    </div>
                    <div class="pdp-coins-reward-card">
                        <svg width="46" height="46" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M13.744 17.736a6 6 0 1 1-7.48-7.48M15 6h1v4m-9.866 4.768.866-.5 2 3.464" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            <circle cx="16" cy="8" r="6" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                        <strong><span id="pdpCoinsModalAmount">{{ !empty($product->coins_reward) ? $product->coins_reward : round($initialPrice * 0.05) }}</span> NB Coins</strong>
                        <p>Shown on this <span id="pdpCoinsModalPrice">₹{{ number_format($initialPrice, 0) }}</span> purchase.</p>
                    </div>
                    <p>Earn rewards when you shop. Eligible coins and redemption terms will be shown in your account once the loyalty programme is connected.</p>
                    <p class="pdp-coins-disclaimer">Rewards are illustrative in this preview; no coins are credited to a live account.</p>
                    <button type="button" class="pdp-coins-got-it" data-close-coins-modal>Got it</button>
                </section>
            </div>

            <fieldset class="pdp-purchase-options" id="pdp-purchase-options">
                <legend>How do you want to buy?</legend>
                <label class="pdp-purchase-option selected" data-pdp-purchase-option>
                    <input type="radio" name="pdpPurchaseOption" value="once" checked>
                    <span class="pdp-purchase-copy">
                        <b>One-time purchase</b>
                        <small>{{ $purchaseUnitLabel }}</small>
                    </span>
                    <strong class="pdp-purchase-price" id="pdpOneTimePrice">₹{{ number_format($initialPrice, 0) }}</strong>
                </label>
                <label class="pdp-purchase-option pdp-subscription-option" data-pdp-purchase-option>
                    <input type="radio" name="pdpPurchaseOption" value="subscribe">
                    <span class="pdp-purchase-copy">
                        <b>Subscribe &amp; Save</b>
                        <small>2, 3 or 4 months · Monthly delivery</small>
                    </span>
                    <span class="pdp-subscription-from">
                        <strong>From ₹569<small>/month</small></strong>
                        <small>Lower price from month 2</small>
                    </span>
                    <svg class="pdp-subscription-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </label>
                <div class="pdp-subscription-panel" id="pdpSubscriptionPanel" hidden>
                    <span class="pdp-subscription-chip">Save from month 2 · Cancel anytime</span>
                    <div class="pdp-subscription-plans">
                        <label class="pdp-subscription-plan" data-pdp-subscription-plan>
                            <input type="radio" name="pdpSubscriptionPlan" value="2">
                            <span class="pdp-plan-copy">
                                <span class="pdp-plan-heading"><b>2 months</b><strong>₹599<small>/month</small></strong></span>
                                <small>₹649 today, then ₹599/month</small>
                                <span class="pdp-plan-badges"><span class="pdp-plan-save">Save ₹150 vs. MRP</span></span>
                            </span>
                        </label>
                        <label class="pdp-subscription-plan" data-pdp-subscription-plan>
                            <input type="radio" name="pdpSubscriptionPlan" value="3">
                            <span class="pdp-plan-copy">
                                <span class="pdp-plan-heading"><b>3 months</b><span class="pdp-plan-tag pdp-plan-recommended">Recommended</span><strong>₹579<small>/month</small></strong></span>
                                <small>₹649 today, then ₹579/month</small>
                                <span class="pdp-plan-badges"><span class="pdp-plan-save">Save ₹290 vs. MRP</span></span>
                            </span>
                        </label>
                        <label class="pdp-subscription-plan" data-pdp-subscription-plan>
                            <input type="radio" name="pdpSubscriptionPlan" value="4">
                            <span class="pdp-plan-copy">
                                <span class="pdp-plan-heading"><b>4 months</b><span class="pdp-plan-tag pdp-plan-best">Best value</span><strong>₹569<small>/month</small></strong></span>
                                <small>₹649 today, then ₹569/month</small>
                                <span class="pdp-plan-badges">
                                    <span class="pdp-plan-save">Save ₹440 vs. MRP</span>
                                    <span class="pdp-plan-gift">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M3 9h18v12H3zM2 5h20v4H2zM12 5v16m0-16H7.5a2.5 2.5 0 1 1 2.5-2.5C10 4 12 5 12 5Zm0 0h4.5A2.5 2.5 0 1 0 14 2.5C14 4 12 5 12 5Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        Free Tasty Kadha
                                    </span>
                                </span>
                            </span>
                        </label>
                    </div>
                </div>
            </fieldset>

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

            <section class="pdp-reference-features" aria-label="Product features">
                <div class="pdp-reference-feature">
                    <span class="pdp-reference-feature-icon"><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M9 38 38 9M17 13l18 18M13 18l17 17M19 11c4 4 4 8 0 12l-7 7a7 7 0 0 0 10 10l7-7c4-4 8-4 12 0M32 8l8 8"/></svg></span>
                    <span>No Added<br>Colours</span>
                </div>
                <div class="pdp-reference-feature">
                    <span class="pdp-reference-feature-icon"><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M9 39 39 9M17 15l16 16M14 24l10 10M23 12l13 13M11 30l8 8M30 17l7-7M8 39h30M18 11v17a7 7 0 0 1-7 7M28 9v8a7 7 0 0 0 7 7"/></svg></span>
                    <span>No Refined<br>Sugar</span>
                </div>
                <div class="pdp-reference-feature">
                    <span class="pdp-reference-feature-icon"><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M9 40h30L27 20V9M21 9h12M18 29h17M12 36l25-27M23 20v-4"/></svg></span>
                    <span>No<br>Preservatives</span>
                </div>
                <div class="pdp-reference-feature">
                    <span class="pdp-reference-feature-icon"><svg viewBox="0 0 48 48" aria-hidden="true"><path d="m10 17 14-8 14 8v16l-14 8-14-8V17ZM10 17l14 8 14-8M24 25v16M7 41 41 7M16 13l18 10"/></svg></span>
                    <span>No<br>Gelatin</span>
                </div>
                <div class="pdp-reference-feature">
                    <span class="pdp-reference-feature-icon"><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M39 8c-17 0-28 8-28 22 0 6 4 10 10 10 13 0 21-13 18-32ZM10 40c4-9 10-15 20-21"/></svg></span>
                    <span>100%<br>Vegetarian</span>
                </div>
            </section>
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

            <div class="pdp-offers-delivery">
                <div class="pdp-multi-buy-offer">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M20.59 13.41 11 3.83V3H4v7h.83l9.58 9.59a2 2 0 0 0 2.83 0l3.35-3.35a2 2 0 0 0 0-2.83Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="7.5" cy="6.5" r=".8" fill="currentColor" />
                    </svg>
                    <span>
                        <b>Buy 2: save 5% · Buy 3+: save 10%</b>
                        <small>Individual items · best eligible offer applied in cart</small>
                    </span>
                </div>

                <button type="button" class="pdp-coupon-offer" id="pdpCouponTrigger" aria-haspopup="dialog" aria-controls="pdpCouponModal">
                    <svg width="25" height="25" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M20.59 13.41 11 3.83V3H4v7h.83l9.58 9.59a2 2 0 0 0 2.83 0l3.35-3.35a2 2 0 0 0 0-2.83Z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="7.5" cy="6.5" r=".8" fill="currentColor" />
                    </svg>
                    <span>
                        <b>Available Coupons</b>
                        <small>Check eligible offers at checkout</small>
                    </span>
                    <svg class="pdp-coupon-chevron" width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>

                <div class="pdp-shipping-info">
                    <span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 7h11v11H3zM14 10h4l3 3v5h-7z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                            <circle cx="7.5" cy="18" r="2" fill="#fff" stroke="currentColor" stroke-width="1.8" />
                            <circle cx="17.5" cy="18" r="2" fill="#fff" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                        Free shipping ₹1000+
                    </span>
                    <span>COD Available</span>
                    <span>Extra 5% OFF on Prepaid orders</span>
                </div>

                <p class="pdp-delivery-estimate">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.8" />
                        <path d="M16 3v4M8 3v4M3 10h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                    Estimated delivery between <b>17 November – 19 November.</b>
                </p>
            </div>

            <section class="pdp-accordion-section" aria-label="Product information">
                <div class="pdp-accordions">
                    <details class="pdp-accordion">
                        <summary>Product description</summary>
                        <div class="pdp-accordion-content">
                            @if (!empty(trim((string) ($product->description ?? ''))))
                                {!! $product->description !!}
                            @else
                                <p>{{ $product->short_description ?: $product->name }}</p>
                            @endif
                        </div>
                    </details>
                    <details class="pdp-accordion">
                        <summary>What’s inside?</summary>
                        <div class="pdp-accordion-content">
                            @if ($product->ingredients->isNotEmpty())
                                <ul>
                                    @foreach ($product->ingredients as $ingredient)
                                        <li>{{ $ingredient->main_heading ?: $ingredient->short_heading }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p>Made with carefully selected ingredients for your daily wellness routine.</p>
                            @endif
                        </div>
                    </details>
                    <details class="pdp-accordion">
                        <summary>How do I use it?</summary>
                        <div class="pdp-accordion-content">
                            <p>{{ $product->dosage ?: 'Follow the serving instructions printed on the product pack. For children, use under adult supervision.' }}</p>
                            @if ($product->routine)
                                <p>{{ $product->routine }}</p>
                            @endif
                        </div>
                    </details>
                    <details class="pdp-accordion">
                        <summary>How does Subscribe &amp; Save work?</summary>
                        <div class="pdp-accordion-content">
                            <p>Choose a 2, 3, or 4 month plan for monthly delivery. Your first box is charged at the one-time price; the lower plan price applies from month 2. You can cancel anytime.</p>
                        </div>
                    </details>
                    <details class="pdp-accordion">
                        <summary>What are the shipping and return policies?</summary>
                        <div class="pdp-accordion-content">
                            <p>Free shipping is available on orders of ₹1,000 or more. Cash on delivery is available where serviceable. Delivery dates depend on your location.</p>
                            <p>For return or damage assistance, contact NutriBuddy support with your order details and product photos.</p>
                        </div>
                    </details>
                </div>
            </section>

            <div class="pdp-coins-modal" id="pdpCouponModal" hidden aria-hidden="true">
                <div class="pdp-coins-backdrop" data-close-coupon-modal></div>
                <section class="pdp-coins-dialog pdp-coupon-dialog" role="dialog" aria-modal="true" aria-labelledby="pdpCouponTitle">
                    <div class="pdp-coins-dialog-head">
                        <h2 id="pdpCouponTitle">A little extra, on us</h2>
                        <button type="button" class="pdp-coins-close" aria-label="Close coupon details" data-close-coupon-modal>×</button>
                    </div>
                    <div class="pdp-coupon-modal-card">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M20.59 13.41 11 3.83V3H4v7h.83l9.58 9.59a2 2 0 0 0 2.83 0l3.35-3.35a2 2 0 0 0 0-2.83Z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" />
                            <circle cx="7.5" cy="6.5" r=".8" fill="currentColor" />
                        </svg>
                        <div class="pdp-coupon-modal-copy">
                            <h3>Extra 5% off prepaid orders</h3>
                            <p>Select prepaid payment in checkout to use this offer.</p>
                            <b>PREPAID5</b>
                        </div>
                        <button type="button" class="pdp-coupon-apply" data-close-coupon-modal>Apply</button>
                    </div>
                    <div class="pdp-coupon-modal-card">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 7h11v11H3zM14 10h4l3 3v5h-7z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                            <circle cx="7.5" cy="18" r="2" fill="#fff" stroke="currentColor" stroke-width="1.8" />
                            <circle cx="17.5" cy="18" r="2" fill="#fff" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                        <div class="pdp-coupon-modal-copy">
                            <h3>Free shipping on ₹1,000+</h3>
                            <p>Automatically applied when your cart qualifies.</p>
                        </div>
                    </div>
                    <p class="pdp-coupon-modal-note">Preview offers. Final eligibility and stacking rules are confirmed by the store.</p>
                </section>
            </div>

        </div>
    </div>
    <section class="pdp-world-nutrition" aria-label="Children’s nutrition research around the world">
        <iframe
            class="pdp-world-nutrition-frame"
            src="{{ asset('assets/research/world-nutrition-map.html') }}"
            title="Children’s supplement use around the world"
            loading="lazy"
            scrolling="no"></iframe>
    </section>
    <!-- ════════════════════════════════════════════════
                                                 PRODUCT DESCRIPTION SECTION
                                            ════════════════════════════════════════════════ -->
    <!-- Product Description Section -->

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
                <div class="nb-eyebrow">Ingredient Transparency</div>
                <h2 class="nb-ing-title">What’s inside <span class="nb-acc-pk">every gummy?</span></h2>
                <p class="nb-ing-sub">Explore our ingredients and what each one does.</p>
            </div>

            <!-- ── Category Filter (desktop) ── -->
            @php
                $productIngredients = $product->ingredients ?? collect();

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

            @endphp
            <div class="nb-ingredient-nav" aria-label="Browse ingredients">
                <button class="nb-ing-arrow" type="button" data-nb-scroll="-1" aria-label="Previous ingredients">‹</button>
                <div class="nb-list-scroll" id="nbList" role="tablist" aria-label="Ingredients"></div>
                <button class="nb-ing-arrow" type="button" data-nb-scroll="1" aria-label="Next ingredients">›</button>
            </div>

            <!-- ── Mobile Accordion Cards ── -->
            <div class="nb-mob-cards" id="nbMobCards"></div>

            <!-- ── Desktop: Two-column layout ── -->
            <div class="nb-ing-body">

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
                <figure class="transform-visual pdp-before-after" style="--split: 50%">
                    <img class="pdp-before-after-image pdp-before-after-after"
                        src="{{ asset('assets/brain/routine-after.webp') }}"
                        alt="After: a mother and children enjoying their shared puzzle routine" loading="lazy" decoding="async">
                    <img class="pdp-before-after-image pdp-before-after-before"
                        src="{{ asset('assets/brain/routine-before.png') }}"
                        alt="Before: the same family getting ready to begin a puzzle activity" loading="lazy" decoding="async">
                    <span class="pdp-before-after-label pdp-before-label">Before<small>Getting started</small></span>
                    <span class="pdp-before-after-label pdp-after-label">After<small>A shared little habit</small></span>
                    <span class="pdp-before-after-divider" aria-hidden="true"><span>← →</span></span>
                    <input type="range" min="0" max="100" value="50"
                        aria-label="Reveal before and after routine images"
                        aria-valuetext="50% of before image visible"
                        oninput="this.closest('.pdp-before-after').style.setProperty('--split', this.value + '%'); this.setAttribute('aria-valuetext', this.value + '% of before image visible')">
                    <figcaption class="pdp-before-after-hint">Drag to explore</figcaption>
                </figure>
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

        </div>

    <section class="pdp-formula-reveal" aria-labelledby="pdp-formula-title">
                <div class="pdp-formula-shell">
                    <header class="pdp-formula-heading section-heading center">
                        <p class="pdp-formula-eyebrow eyebrow">Meet your nutrition buddy</p>
                        <h2 id="pdp-formula-title">Little gummy. <em>Thoughtful formula.</em></h2>
                    </header>
                    <h3 class="pdp-formula-product-name">Brain Booster Gummies</h3>
                    <div class="pdp-ayurveda-pill">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 3h6m-5 0v6l-5.5 9.2A1.9 1.9 0 0 0 6.1 21h11.8a1.9 1.9 0 0 0 1.6-2.8L14 9V3M8 15h8"/></svg>
                        Ayurveda + nutraceutical
                    </div>
                    <div class="pdp-formula-stage">
                        <span class="pdp-formula-word brain-hero-word" aria-hidden="true">BRAIN</span>
                        <img src="{{ asset('assets/brain/hero-reveal.webp') }}" alt="NutriBuddy Brain Booster Gummies with grapes and botanicals">
                        <div class="pdp-formula-stat pdp-formula-stat--left">
                            <strong>2</strong>
                            <b>Ayurvedic botanicals<br><small>Brahmi &amp; Shankhpushpi</small></b>
                        </div>
                        <div class="pdp-formula-stat pdp-formula-stat--right">
                            <strong>20 mg</strong>
                            <b>algal DHA<br><small>in every gummy</small></b>
                        </div>
                    </div>
                    <div class="pdp-formula-badges" aria-label="Product qualities">
                        <div><span class="pdp-formula-icon tone-0" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 3h6m-5 0v6l-5.4 9a2 2 0 0 0 1.7 3h11.4a2 2 0 0 0 1.7-3L14 9V3M8 15h8"/></svg></span><b>No preservatives</b></div>
                        <div><span class="pdp-formula-icon tone-1" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3a9 9 0 1 0 0 18h1.2a2 2 0 0 0 1.5-3.3 1.8 1.8 0 0 1 1.4-3h1.4A3.5 3.5 0 0 0 21 11.2C21 6.7 17 3 12 3Z"/><circle cx="7.5" cy="10" r="1"/><circle cx="11" cy="7" r="1"/><circle cx="16" cy="8" r="1"/></svg></span><b>No added colours</b></div>
                        <div><span class="pdp-formula-icon tone-2" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M8 3h8m-6 0v6l-5.4 9a2 2 0 0 0 1.7 3h11.4a2 2 0 0 0 1.7-3L12 9V3M7.5 16h9"/><path d="m4 20 16-16"/></svg></span><b>No refined sugar</b></div>
                        <div><span class="pdp-formula-icon tone-3" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M20 4c-8 0-14 4-14 11a5 5 0 0 0 5 5c7 0 9-8 9-16Z"/><path d="M4 21c3-5 6-8 11-11"/></svg></span><b>100% vegetarian</b></div>
                    </div>
                    <p class="pdp-formula-caption">To complement a varied diet, when supplementation is needed.</p>
                    <div class="pdp-formula-actions">
                        <a class="pdp-formula-primary" href="#pdpAddToCartBtn">Choose your plan <span aria-hidden="true">→</span></a>
                        <a class="pdp-formula-secondary" href="#nb-ingredients">Explore ingredients <span aria-hidden="true">→</span></a>
                    </div>
                    <p class="pdp-formula-note">One-time purchase or Subscribe &amp; Save</p>
                </div>
    </section>

    <div class="ps-inner">
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

        </div>
    </section>

    <!-- ════════════════════════════════════════════════   ══════════════════════════════════════════
                                                     PARENT REVIEWS
                                                ══════════════════════════════════════════ -->
    <section class="pdp-family" data-pdp-family aria-labelledby="pdp-family-title">
        <div class="pdp-family-shell">
            <header class="pdp-family-heading">
                <p class="pdp-family-eyebrow">EXPLORE THE NUTRIBUDDY FAMILY</p>
                <h2 id="pdp-family-title">One favourite. <em>Or a little more.</em></h2>
                <p>Individual products and thoughtful combos, all in one place.</p>
            </header>

            <div class="pdp-family-toolbar">
                <div class="pdp-family-tabs" role="tablist" aria-label="Shop products and combos">
                    <button type="button" role="tab" id="pdp-family-tab-products" aria-selected="true" aria-controls="pdp-family-panel-products" data-family-tab="products" tabindex="0">
                        Individual products <span>{{ $familyProducts->count() }}</span>
                    </button>
                    <button type="button" role="tab" id="pdp-family-tab-combos" aria-selected="false" aria-controls="pdp-family-panel-combos" data-family-tab="combos" tabindex="-1">
                        Combo packs <span>{{ count($familyComboPacks) }}</span>
                    </button>
                </div>
            </div>

            <div class="pdp-family-panel" id="pdp-family-panel-products" role="tabpanel" aria-labelledby="pdp-family-tab-products" data-family-panel="products">
                <div class="pdp-family-track" data-family-track>
                    @foreach ($familyProducts as $familyProduct)
                        @php
                            $familyVariant = $familyProduct->variants->firstWhere('is_default', true)
                                ?: $familyProduct->variants->first(fn($variant) => $variant->is_active);
                            $familyPrice = $familyVariant?->display_price ?? $familyProduct->display_price;
                            $familyComparePrice = $familyVariant?->display_compare_price ?? $familyProduct->display_compare_price;
                            $familyDescription = $familyProduct->short_description ?: $familyProduct->description;
                        @endphp
                        <article class="pdp-family-card">
                            <a class="pdp-family-image" href="{{ route('product.show', $familyProduct->slug) }}" aria-label="Explore {{ $familyProduct->name }}">
                                <img src="{{ $familyProductImage($familyProduct) }}" alt="{{ $familyProduct->name }}" loading="lazy" decoding="async">
                            </a>
                            <div class="pdp-family-card-copy">
                                <span class="pdp-family-chip">{{ $familyProduct->category?->name ?: 'NutriBuddy favourite' }}</span>
                                <h3>{{ $familyProduct->name }}</h3>
                                <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $familyDescription), 105) }}</p>
                                <div class="pdp-family-facts">
                                    @if ($familyProduct->flavor)<span>{{ $familyProduct->flavor }}</span>@endif
                                    @if ($familyProduct->pack_size)<span>{{ $familyProduct->pack_size }}</span>@endif
                                    @if ($familyProduct->age_group)<span>{{ $familyProduct->age_group }}</span>@endif
                                </div>
                                <div class="pdp-family-price">
                                    <strong>₹{{ number_format((float) $familyPrice, 0) }}</strong>
                                    @if ((float) $familyComparePrice > (float) $familyPrice)
                                        <s>₹{{ number_format((float) $familyComparePrice, 0) }}</s>
                                    @endif
                                </div>
                                <a class="pdp-family-link" href="{{ route('product.show', $familyProduct->slug) }}">View product <span aria-hidden="true">→</span></a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="pdp-family-carousel-footer">
                    <div class="pdp-family-dots" data-family-dots aria-label="Product slides"></div>
                    <div class="pdp-family-arrows">
                        <button type="button" data-family-move="-1" aria-label="Previous product">‹</button>
                        <button type="button" data-family-move="1" aria-label="Next product">›</button>
                    </div>
                </div>
                <p class="pdp-family-note">Buy 2 individual items: save 5%. Buy 3+: save 10%. The best eligible offer is applied in your cart.</p>
            </div>

            <div class="pdp-family-panel" id="pdp-family-panel-combos" role="tabpanel" aria-labelledby="pdp-family-tab-combos" data-family-panel="combos" hidden>
                <div class="pdp-family-track" data-family-track>
                    @foreach ($familyComboPacks as $comboPack)
                        <article class="pdp-family-card pdp-family-combo-card">
                            <div class="pdp-family-combo-image" aria-label="{{ $comboPack['name'] }} product combination">
                                @foreach ($comboPack['products'] as $comboProductKey)
                                    <img src="{{ $familyImageFor($comboProductKey) }}" alt="" loading="lazy" decoding="async">
                                @endforeach
                            </div>
                            <div class="pdp-family-card-copy">
                                <span class="pdp-family-chip">{{ $comboPack['tag'] }}</span>
                                <h3>{{ $comboPack['name'] }}</h3>
                                <p>{{ $comboPack['description'] }}</p>
                                <div class="pdp-family-facts">
                                    <span>{{ $comboPack['products'] === ['brain booster', 'brain booster'] ? 'Ages 4+' : 'Family routine' }}</span>
                                    <span>{{ count($comboPack['products']) }} products</span>
                                </div>
                                <div class="pdp-family-price">
                                    <strong>₹{{ number_format($comboPack['price']) }}</strong>
                                    <s>₹{{ number_format($comboPack['mrp']) }}</s>
                                    <span class="pdp-family-saving">Save ₹{{ number_format($comboPack['mrp'] - $comboPack['price']) }}</span>
                                </div>
                                <p class="pdp-family-renewal">Subscribe from ₹{{ number_format($comboPack['renewal']) }}/month</p>
                                <a class="pdp-family-link" href="{{ route('product') }}">Explore products <span aria-hidden="true">→</span></a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="pdp-family-carousel-footer">
                    <div class="pdp-family-dots" data-family-dots aria-label="Combo pack slides"></div>
                    <div class="pdp-family-arrows">
                        <button type="button" data-family-move="-1" aria-label="Previous combo pack">‹</button>
                        <button type="button" data-family-move="1" aria-label="Next combo pack">›</button>
                    </div>
                </div>
                <p class="pdp-family-note">Combo packs are thoughtful product suggestions. Check current product availability and pricing before ordering.</p>
            </div>
        </div>
    </section>

    <section class="pdp-subscribe-story" aria-labelledby="pdp-subscribe-story-title">
        <p class="pdp-subscribe-story-eyebrow">SUBSCRIBE &amp; SAVE</p>
        <h2 id="pdp-subscribe-story-title">Never run out. <em>Save from month two.</em></h2>

        <div class="pdp-subscribe-story-steps">
            <article>
                <span>1</span>
                <h3>Pick your plan</h3>
                <p>Choose 2, 3 or 4 monthly deliveries of Brain Booster.</p>
            </article>
            <article>
                <span>2</span>
                <h3>See every payment</h3>
                <p>Pay ₹649 today. Your selected lower price starts in month two.</p>
            </article>
            <article>
                <span>3</span>
                <h3>Stay in control</h3>
                <p>Pause or cancel future deliveries. No full-plan payment upfront.</p>
            </article>
        </div>

        <a class="pdp-subscribe-story-cta" href="#pdp-purchase-options" data-pdp-subscribe-cta>
            Choose my plan <span aria-hidden="true">→</span>
        </a>
    </section>

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

    <div class="pdp-sticky-purchase" data-pdp-sticky-purchase aria-hidden="true">
        <div class="pdp-sticky-product">
            @if ($mainGalleryImage)
                <img src="{{ $productImageUrl($mainGalleryImage->image_path) }}" alt="">
            @endif
            <span>
                <b>{{ $isBrainBoosterProduct ? 'Brain Booster Gummies' : $product->name }}</b>
                <small>{{ $isBrainBoosterProduct ? 'Red grape · 30 gummies · Ages 4+' : collect([$defFlavour, $defPack, $defAge])->filter()->implode(' · ') }}</small>
            </span>
        </div>
        <div class="pdp-sticky-price">
            <b id="pdpStickyPrice">₹{{ number_format($initialPrice, 0) }}</b>
            <small data-pdp-sticky-purchase-type>One-time purchase</small>
        </div>
        <button type="button" class="pdp-sticky-action pdp-sticky-cart" data-pdp-sticky-action="cart">
            Add to Cart
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
        </button>
        <button type="button" class="pdp-sticky-action pdp-sticky-buy" data-pdp-sticky-action="buy">
            Buy Now
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
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
