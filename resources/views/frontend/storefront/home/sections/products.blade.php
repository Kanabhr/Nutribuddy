<section class="products-section reveal" id="products">
        <span class="sec-eye">Our Products</span>
        <h2 class="sec-title">Nutrition Kids <span class="acc">Actually Love</span></h2>
        <p class="sec-sub">Each product crafted with Ayurvedic wisdom + modern science. Balanced doses, kid-safe, genuinely
            delicious flavors.</p>
        <div class="products-grid">
            @foreach ($featuredProducts as $product)
                @php
                    $catSlug = $product->category->slug ?? 'pk';
                    // Map database slugs to CSS classes if they don't match
                    if ($catSlug == 'multivitamins') {
                        $catSlug = 'pk';
                    } elseif ($catSlug == 'whey-protein') {
                        $catSlug = 'sk';
                    } elseif ($catSlug == 'pre-workout') {
                        $catSlug = 'pu';
                    } else {
                        $catSlug = 'pk';
                    }
                    $activeVariants = $product->variants
                        ->filter(fn($variant) => $variant->is_active && !empty($variant->attributes))
                        ->values();
                    $variationLabels = $activeVariants
                        ->map(function ($variant) {
                            $label = collect($variant->attributes ?? [])
                                ->filter(fn($value) => trim((string) $value) !== '')
                                ->map(fn($value, $key) => $key . ': ' . $value)
                                ->implode(' / ');

                            return $label ?: $variant->name;
                        })
                        ->filter()
                        ->unique()
                        ->take(4)
                        ->values()
                        ->all();

                    if (empty($variationLabels)) {
                        $variationLabels = collect([
                            $product->flavor ? 'Flavour: ' . $product->flavor : null,
                            $product->pack_size ? 'Pack Size: ' . $product->pack_size : null,
                            $product->age_group ? 'Age Group: ' . $product->age_group : null,
                            $product->dosage ? 'Dosage: ' . $product->dosage : null,
                        ])->filter()->take(4)->values()->all();
                    }

                    $variantGroups = [];
                    foreach ($activeVariants as $variant) {
                        foreach (($variant->attributes ?? []) as $name => $value) {
                            $value = trim((string) $value);
                            if ($value === '') {
                                continue;
                            }
                            $variantGroups[$name] ??= [];
                            if (!in_array($value, $variantGroups[$name], true)) {
                                $variantGroups[$name][] = $value;
                            }
                        }
                    }

                    $selectedVariant = $activeVariants->firstWhere('is_default', true) ?: $activeVariants->first();
                    $selectedAttributes = $selectedVariant?->attributes ?? [];
                    $selectedLabel = collect($selectedAttributes)
                        ->filter(fn($value) => trim((string) $value) !== '')
                        ->map(fn($value, $key) => $key . ': ' . $value)
                        ->implode(' / ');
                    $stockQty = (int) ($selectedVariant?->inventory?->stock_qty ?? 0);
                    $trackStock = (bool) ($selectedVariant?->inventory?->track_stock ?? false);
                    $isAvailable = !$trackStock || (($selectedVariant?->inventory?->is_in_stock ?? true) && $stockQty > 0);
                    $hasVariantOptions = !empty($variantGroups) || !empty($variationLabels);
                    $showInlineVariants = false;
                    $frontendVariants = $activeVariants
                        ->map(function ($variant) {
                            $stockQty = (int) ($variant->inventory?->stock_qty ?? 0);
                            $trackStock = (bool) ($variant->inventory?->track_stock ?? false);

                            return [
                                'id' => $variant->id,
                                'name' => $variant->name,
                                'attributes' => $variant->attributes ?? [],
                                'price' => (float) $variant->display_price,
                                'compare_price' => (float) ($variant->display_compare_price ?? 0),
                                'stock_qty' => $stockQty,
                                'track_stock' => $trackStock,
                                'available' => !$trackStock || (($variant->inventory?->is_in_stock ?? true) && $stockQty > 0),
                            ];
                        })
                        ->values()
                        ->all();
                    $cardPrice = (float) ($selectedVariant?->display_price ?? $product->display_price);
                    $cardComparePrice = (float) ($selectedVariant?->display_compare_price ?? $product->display_compare_price ?? 0);
                    $fallbackDefaultImage = $product->primaryImage ?: $product->images->first();
                    $fallbackHoverImage = $product->images
                        ->where('id', '!=', $fallbackDefaultImage?->id)
                        ->first() ?: $fallbackDefaultImage;
                    $defaultImagePath = $product->card_image_path ?: $fallbackDefaultImage?->image_path;
                    $hoverImagePath = $product->card_hover_image_path ?: ($fallbackHoverImage?->image_path ?: $defaultImagePath);
                @endphp
                <div class="pc pc-{{ $catSlug }} {{ $selectedVariant ? 'has-variants' : 'no-variants' }}"
                    data-selected-variant-id="{{ $selectedVariant?->id }}" data-selected-variant-label="{{ $selectedLabel }}"
                    data-variants='{{ json_encode($frontendVariants, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}'>
                    <div class="pc-head pc-head-{{ $catSlug }}">
                        <a href="{{ route('product.show', $product->slug) }}" class="pc-emoji p-image">
                            @if ($defaultImagePath)
                                <img src="{{ asset('storage/' . $defaultImagePath) }}" alt="{{ $product->name }}"
                                    class="default-img" loading="lazy" decoding="async">
                                <img src="{{ asset('storage/' . $hoverImagePath) }}" alt="{{ $product->name }}" class="hover-img"
                                    loading="lazy" decoding="async">
                            @endif
                        </a>
                        @if ($product->is_featured)
                            <div class="pc-badge">Best Seller</div>
                        @endif
                    </div>
                    <div class="pc-body">
                        <a href="{{ route('product.show', $product->slug) }}#reviews" class="pc-stars"
                            style="text-decoration: none;">
                            @php
                                $reviewCount = $product->reviews->count();
                                $rating = $reviewCount > 0 ? $product->reviews->avg('rating') : 0;
                            @endphp
                            @for ($i = 0; $i < 5; $i++)
                                {{ $i < $rating ? '★' : '☆' }}
                            @endfor
                            <span style="color:#aaa;font-size:.75rem;font-family:'DM Sans',sans-serif">
                                ({{ $reviewCount }} reviews)
                            </span>
                        </a>
                        <div class="pc-cat cat-{{ $catSlug }}">{{ $product->category->name ?? 'Uncategorized' }}
                        </div>
                        <div class="pc-name"><a href="{{ route('product.show', $product->slug) }}"
                                style="color: inherit; text-decoration: none;">{{ $product->name }}</a></div>
                        @if($showInlineVariants && $hasVariantOptions)
                            <div class="pc-variant-panel">
                                @if(!empty($variantGroups))
                                    <div class="pc-variant-groups">
                                        @foreach($variantGroups as $attributeName => $values)
                                            @if(count($values) > 1)
                                                <div class="pc-variant-block">
                                                    <div class="pc-variant-label">{{ $attributeName }}</div>
                                                    <div class="pc-option-row" data-attribute-group="{{ $attributeName }}">
                                                        @foreach($values as $value)
                                                            <button type="button"
                                                                class="pc-option-btn {{ ($selectedAttributes[$attributeName] ?? null) === $value ? 'active' : '' }}"
                                                                data-attribute="{{ $attributeName }}" data-value="{{ $value }}">
                                                                {{ $value }}
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    @foreach(array_slice($variationLabels, 0, 3) as $variation)
                                        <div class="pc-option-row">
                                            <button type="button" class="pc-option-btn active">{{ $variation }}</button>
                                        </div>
                                    @endforeach
                                @endif

                                <div class="pc-variant-meta">
                                    <span class="pc-stock-pill {{ $isAvailable ? '' : 'out' }}">
                                        @if($isAvailable)
                                            Available
                                        @else
                                            Out of stock
                                        @endif
                                    </span>
                                    <span class="pc-selected-pill" title="{{ $selectedLabel ?: 'Product option' }}">
                                        {{ $selectedLabel ?: 'Product option' }}
                                    </span>
                                </div>
                            </div>
                        @endif

                        <div class="pc-foot">
                            <div class="pc-price" data-price-label>
                                ₹{{ number_format($cardPrice, 0) }}
                                @if ($cardComparePrice > $cardPrice)
                                    <s>₹{{ number_format($cardComparePrice, 0) }}</s>
                                @endif
                            </div>
                            <button class="btn-add badd-{{ $catSlug }}" data-id="{{ $product->id }}"
                                data-variant-id="{{ $selectedVariant?->id }}">Add to Cart
                                +</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
