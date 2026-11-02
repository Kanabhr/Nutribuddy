@extends('layouts.main')
@section('title', $product->meta_title ?? $product->name . " – India's #1 Kids Wellness Gummy")
@section('meta_description', $product->meta_description ?? $product->short_description)
@section('meta_keywords', $product->meta_keywords)

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/product.css') }}?v={{ filemtime(public_path('assets/css/product.css')) }}">
@endpush

@section('content')
    <!-- ══ PRODUCT HERO ══ -->
    <div class="pdp-hero">

        <!-- LEFT: Gallery -->
        <div class="pdp-gallery">
            <div class="main-img-wrap">
                @if($product->is_featured)
                    <div class="badge-bestseller">Best Seller</div>
                @endif
                @if($product->display_compare_price > $product->display_price)
                    @php 
                        $discount = round((($product->display_compare_price - $product->display_price) / $product->display_compare_price) * 100);
                    @endphp
                    <div class="badge-discount" id="discountBadge">{{ $discount }}% OFF</div>
                @endif
                
                <div class="p-image" style="display:block;line-height:1">
                    @if($product->primaryImage)
                        <img src="{{ asset('storage/' . $product->primaryImage->image_path) }}" alt="{{ $product->name }}" id="mainPdpImage">
                    @endif
                </div>
            </div>
            <div class="thumb-row">
                @foreach($product->images as $image)
                    <div class="thumb {{ $image->is_primary ? 'active' : '' }}" onclick="changePdpImage(this, '{{ asset('storage/' . $image->image_path) }}')">
                        <img src="{{ asset('storage/' . $image->image_path) }}" alt="{{ $product->name }}">
                    </div>
                @endforeach
            </div>
        </div>

        <!-- RIGHT: Info -->
        <div class="pdp-info">
            @if($product->category)
            <div class="pdp-cat">{{ $product->category->name }} · Kids 2–14 yrs</div>
            @endif
            <h1 class="pdp-name">{{ $product->name }}</h1>
            @if($product->reviews->count() > 0)
            <div class="pdp-rating">
                <div class="stars">
                    @php $rating = (float) $product->reviews->avg('rating'); @endphp
                    @for($i=0; $i<5; $i++)
                        {{ $i < round($rating) ? '★' : '☆' }}
                    @endfor
                </div>
                <div class="rating-val">{{ number_format($rating, 1) }}</div>
                <div class="rating-divider"></div>
                <div class="rating-count">{{ $product->reviews->count() }} Verified Reviews</div>
            </div>
            @endif

            <!-- Price -->
            <div class="price-box">
                <div class="price-row" id="priceRow">
                    <div class="price-now">₹{{ number_format($product->display_price, 0) }}</div>
                    @if($product->display_compare_price > $product->display_price)
                        <div class="price-old">₹{{ number_format($product->display_compare_price, 0) }}</div>
                        <div class="price-save">Save ₹{{ number_format($product->display_compare_price - $product->display_price, 0) }}</div>
                    @endif
                </div>
                <div class="price-note">Inclusive of all taxes · Free shipping on this order</div>
                <div class="cashback-row" id="cashbackRow">
                    <span>🪙</span>
                    <span>Get {{ round($product->display_price * 0.05) }} NB Coins on this purchase!</span>
                </div>
            </div>

            <div class="arrange">
                @if($product->is_variant_enabled && $product->variants->count() > 0)
                <!-- Variant Selectors -->
                <div class="variant-block">
                    <div class="variant-label">Options: </div>
                    <div class="variant-row">
                        @foreach($product->variants as $variant)
                            <div class="vopt {{ $loop->first ? 'active' : '' }}" 
                                 onclick="selectVariant(this, '{{ $variant->id }}', '{{ $variant->display_price }}', '{{ $variant->display_compare_price }}')">
                                {{ $variant->name }}
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Static info for theme consistency -->
                <div class="variant-block">
                    <div class="variant-label">{{ $product->name }} Features </div>
                    <div class="variant-row" id="flavorRow">
                        <div class="flavor-opt active">
                            <div class="flavor-emoji"> <img src="{{ asset('img/sugar.png') }}" alt=""></div>
                            <div class="flavor-name">No Added Sugar</div>
                        </div>
                        <div class="flavor-opt active">
                            <div class="flavor-emoji"> <img src="{{ asset('img/no-preservatives.png') }}" alt=""></div>
                            <div class="flavor-name">No Preservatives</div>
                        </div>
                        <div class="flavor-opt active">
                            <div class="flavor-emoji"> <img src="{{ asset('img/no-artificial-colours.png') }}" alt=""> </div>
                            <div class="flavor-name">No Colours<br>Added</div>
                        </div>
                        <div class="flavor-opt active">
                            <div class="flavor-emoji"><img src="{{ asset('img/natural.png') }}" alt=""></div>
                            <div class="flavor-name">Rooted in <br> Ayurveda</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pincode Check hidden for now
            <div class="pincode-row">
                <div class="pincode-label">📍</div>
                <input type="text" maxlength="6" placeholder="Enter pincode to check delivery date" id="pincodeInput">
                <button onclick="checkPincode()">Check</button>
            </div>
            <div id="pincode-result" style="font-size:.82rem;color:var(--mn);font-weight:700;margin-bottom:14px;display:none;padding: 0 4px;">✅ Delivery by Tomorrow!</div>

            -->
            <!-- Action Stack (Quantity, Add to Cart, Buy Now) -->
            <div class="pdp-action-stack" style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px; max-width: 350px;">
                <!-- Quantity Selector -->
                <div class="pdp-qty-wrap">
                    <div class="variant-label" style="margin-bottom: 8px; font-size: 0.9rem; font-weight: 800; color: #475569; letter-spacing: 1px;">QUANTITY:</div>
                    <div class="pdp-qty-row" style="display: flex; justify-content: space-between; align-items: center; background: #fff; border: 2px solid #fce7f3; border-radius: 14px; padding: 6px 20px; width: 100%;">
                        <button type="button" class="qty-btn" id="pdpQtyMinus" style="border:none; background:none; font-size: 1.5rem; font-weight: 900; color: #ff4d6d; cursor: pointer; outline: none;">−</button>
                        <input type="number" id="pdpQtyVal" value="1" min="1" readonly style="width: 50px; text-align: center; border: none; background: transparent; font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 1.1rem; color: #0f172a; -moz-appearance: textfield; outline: none;">
                        <button type="button" class="qty-btn" id="pdpQtyPlus" style="border:none; background:none; font-size: 1.5rem; font-weight: 900; color: #ff4d6d; cursor: pointer; outline: none;">+</button>
                    </div>
                </div>

                <!-- CTAs -->
                <button class="btn-cart" id="pdpAddToCartBtn" style="width: 100%; margin: 0;" onclick="handleAddToCart('{{ $product->id }}', this)">Add to Cart</button>
                <button class="btn-buy" id="pdpBuyNowBtn" style="width: 100%; margin: 0;" onclick="handleBuyNow('{{ $product->id }}', this)">Buy Now</button>
            </div>

            <!-- Guarantees -->
            <div class="guarantees">
                <div class="guarantee"><div class="g-icon">🚚</div><div class="g-title">Free Shipping</div><div class="g-sub">On orders ₹200+</div></div>
                <div class="guarantee"><div class="g-icon">🔄</div><div class="g-title">30-Day Return</div><div class="g-sub">No questions asked</div></div>
                <div class="guarantee"><div class="g-icon">🔒</div><div class="g-title">Secure Payment</div><div class="g-sub">UPI · Cards · COD</div></div>
            </div>

            <!-- Product Highlights -->
            @php 
                $features = $product->description ? array_filter(array_map('trim', explode("\n", $product->description))) : [];
            @endphp
            @if(count($features) > 0)
            <div class="highlights">
                <h4>Why Parents Love {{ $product->name }}</h4>
                <ul class="highlight-list">
                    @foreach(array_slice($features, 0, 6) as $feature)
                        <li><div class="hl-dot"></div>{{ preg_replace('/^[•\-\*]\s*/', '', $feature) }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>

    <!-- ══ DESCRIPTION & DETAILS ══ -->
    <section class="section-wrap reveal">
        <div style="max-width:1200px;margin:0 auto;">
            <h2 class="sec-title">Product <span class="acc">Details</span></h2>
            <div class="pdp-description">
                {!! nl2br(e($product->description)) !!}
            </div>
        </div>
    </section>

    <!-- ══ PARENT REVIEWS ══ -->
    <section class="section-wrap reveal" id="reviews-section">
        <div style="max-width:1200px;margin:0 auto;padding:0 20px;">
            <div style="text-align: center; position: relative; margin-bottom: 30px;">
                <!-- Abstract floating circles for title -->
                <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-150px);">
                    <div style="width: 10px; height: 10px; background: #ff4d6d; border-radius: 50%; position: absolute; top: 0; left: -10px;"></div>
                    <div style="width: 20px; height: 20px; border: 2px solid #8b5cf6; border-radius: 50%; position: absolute; top: -5px; left: 0;"></div>
                </div>
                
                <div style="color: #ff4d6d; font-size: 0.8rem; font-weight: bold; letter-spacing: 2px; text-transform: uppercase;">Parent Reviews</div>
                <h2 style="font-size: 2.5rem; font-weight: 800; color: #111; margin-top: 10px; margin-bottom: 0;">10,000+ Happy Families</h2>
            </div>
            
            @php 
                $totalReviews = $product->reviews->count();
            @endphp
            
            @if($totalReviews > 0)
                @php 
                    $displayAvg = number_format($product->reviews->avg('rating'), 1);
                @endphp
                
                <!-- Summary Card -->
                <div style="background:#fff;border-radius:16px;padding:30px;display:flex;flex-wrap:wrap;align-items:center;gap:40px;margin-bottom:30px;box-shadow:0 4px 15px rgba(0,0,0,0.05);">
                    <div style="text-align:center; position: relative;">
                        <!-- Abstract floating circles for rating -->
                        <div style="position: absolute; top: 10px; left: -15px; z-index: 1;">
                            <div style="width: 14px; height: 14px; background: #ff4d6d; border-radius: 50%; position: absolute; top: -5px; left: -10px;"></div>
                            <div style="width: 45px; height: 45px; border: 3px solid #8b5cf6; border-radius: 50%; position: absolute; top: 0; left: 0;"></div>
                        </div>
                        
                        <div style="font-size:3.5rem;font-weight:900;color:#111;line-height:1; position: relative; z-index: 2;">{{ $displayAvg }}</div>
                        <div style="color:#fbbf24;font-size:1.2rem;margin:5px 0; position: relative; z-index: 2;">
                            @for($i=0; $i<5; $i++)
                                {{ $i < round((float)$displayAvg) ? '★' : '☆' }}
                            @endfor
                        </div>
                        <div style="color:#94a3b8;font-size:0.85rem;font-weight:600; position: relative; z-index: 2;">Based on {{ number_format($totalReviews) }} reviews</div>
                    </div>
                    
                    <div style="flex:1;min-width:250px;">
                        @foreach([5, 4, 3, 2, 1] as $star)
                            @php
                                $starCount = $product->reviews->where('rating', $star)->count();
                                $pct = $totalReviews > 0 ? round(($starCount / $totalReviews) * 100, 1) : 0;
                            @endphp
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;font-size:0.85rem;color:#64748b;font-weight:600;">
                                <span style="width:25px;">{{$star}}★</span>
                                <div style="flex:1;background:#f1f5f9;height:6px;border-radius:4px;overflow:hidden;">
                                    <div style="width:{{$pct}}%;background:{{$star>=4?'linear-gradient(90deg, #fbbf24, #f97316)':'#fbbf24'}};height:100%;border-radius:4px;"></div>
                                </div>
                                <span style="width:35px;text-align:right">{{$pct}}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Reviews Grid -->
                <div id="reviewsGrid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(320px, 1fr));gap:24px;">
                    <!-- Review cards generated by JS -->
                </div>
                
                <div style="text-align:center;margin-top:30px;">
                    <button id="loadMoreReviewsBtn" onclick="loadMoreReviews()" style="background:var(--mn, #359e6f);color:#fff;border:none;padding:12px 30px;border-radius:30px;font-size:1rem;font-weight:700;cursor:pointer;margin:0 5px;transition:0.2s;box-shadow:0 4px 10px rgba(53,158,111,0.2);">Load More</button>
                    <button id="readLessReviewsBtn" onclick="readLessReviews()" style="background:#f1f5f9;color:#475569;border:none;padding:12px 30px;border-radius:30px;font-size:1rem;font-weight:700;cursor:pointer;margin:0 5px;display:none;transition:0.2s;">Read Less</button>
                </div>
            @else
                <div style="text-align:center; padding: 60px 20px; background: #fff; border-radius: 16px; border: 1px dashed #cbd5e1; margin-bottom: 30px;">
                    <div style="font-size: 3rem; margin-bottom: 15px; line-height: 1;">⭐</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 8px;">No customer reviews yet.</div>
                    <div style="color: #64748b; font-size: 0.95rem;">Be the first to review this product.</div>
                </div>
            @endif
        </div>
    </section>

    <!-- ══ RELATED PRODUCTS ══ -->
    @if($relatedProducts->count() > 0)
    <section class="section-wrap reveal">
        <div style="max-width:1200px;margin:0 auto;">
            <h2 class="sec-title">You May Also <span class="acc">Like</span></h2>
            <div class="products-grid">
                @foreach($relatedProducts as $rel)
                <div class="pc pc-{{ $rel->category->slug ?? 'pk' }}">
                    <div class="pc-head pc-head-{{ $rel->category->slug ?? 'pk' }}">
                        <a href="{{ route('product.show', $rel->slug) }}" class="pc-emoji p-image">
                            @if($rel->primaryImage)
                                <img src="{{ asset('storage/' . $rel->primaryImage->image_path) }}" alt="{{ $rel->name }}" class="default-img">
                            @endif
                        </a>
                    </div>
                    <div class="pc-body">
                        <div class="pc-cat cat-{{ $rel->category->slug ?? 'pk' }}">{{ $rel->category->name }}</div>
                        <div class="pc-name"><a href="{{ route('product.show', $rel->slug) }}" style="color:inherit;text-decoration:none">{{ $rel->name }}</a></div>
                        <div class="pc-foot">
                            <div class="pc-price">₹{{ number_format($rel->base_price, 0) }}</div>
                            <button class="btn-add badd-{{ $rel->category->slug ?? 'pk' }}" onclick="addToCart('{{ $rel->id }}')">Add +</button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

@endsection

@push('scripts')
<script>
    function changePdpImage(el, src) {
        document.getElementById('mainPdpImage').src = src;
        document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
        el.classList.add('active');
    }

    let selectedVariantId = null;

    function selectVariant(el, id, price, comparePrice) {
        selectedVariantId = id;
        document.querySelectorAll('.vopt').forEach(v => v.classList.remove('active'));
        el.classList.add('active');
        
        price = Number(price);
        comparePrice = Number(comparePrice);

        let priceRowHtml = '<div class="price-now">₹' + price.toLocaleString('en-IN') + '</div>';
        let discountBadge = document.getElementById('discountBadge');

        if (comparePrice > price) {
            let diff = comparePrice - price;
            priceRowHtml += '<div class="price-old">₹' + comparePrice.toLocaleString('en-IN') + '</div>';
            priceRowHtml += '<div class="price-save">Save ₹' + diff.toLocaleString('en-IN') + '</div>';
            
            if (discountBadge) {
                let discountPercent = Math.round((diff / comparePrice) * 100);
                discountBadge.innerText = discountPercent + '% OFF';
                discountBadge.style.display = 'flex';
            }
        } else {
            if (discountBadge) {
                discountBadge.style.display = 'none';
            }
        }
        
        document.getElementById('priceRow').innerHTML = priceRowHtml;
        
        let coins = Math.round(price * 0.05);
        document.querySelector('#cashbackRow span:nth-child(2)').innerText = 'Get ' + coins + ' NB Coins on this purchase!';
    }

    function checkPincode() {
        const pin = document.getElementById('pincodeInput').value;
        if(pin.length === 6) {
            document.getElementById('pincode-result').style.display = 'block';
        }
    }

    async function handleAddToCart(productId, btn) {
        const qtyInput = document.getElementById('pdpQtyVal');
        const qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;
        const variantId = selectedVariantId || null;

        if (typeof window.addToCart === 'function') {
            await window.addToCart(productId, qty, variantId, btn);
        }
    }

    async function handleBuyNow(productId, btn) {
        const qtyInput = document.getElementById('pdpQtyVal');
        const qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;
        const variantId = selectedVariantId || null;

        if (typeof window.addToCart === 'function') {
            const added = await window.addToCart(productId, qty, variantId, btn);
            if (added) window.location.href = "{{ route('checkout.index') }}";
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const qtyVal = document.getElementById('pdpQtyVal');
        const qtyPlus = document.getElementById('pdpQtyPlus');
        const qtyMinus = document.getElementById('pdpQtyMinus');

        if (qtyVal && qtyPlus && qtyMinus) {
            qtyPlus.addEventListener('click', () => {
                const max = parseInt(qtyVal.max) || 99;
                const current = parseInt(qtyVal.value) || 1;
                if (current < max) {
                    qtyVal.value = current + 1;
                } else if (typeof nbToast === 'function') {
                    nbToast(`Only ${max} units available in stock.`, 'warning');
                }
            });

            qtyMinus.addEventListener('click', () => {
                const current = parseInt(qtyVal.value) || 1;
                if (current > 1) {
                    qtyVal.value = current - 1;
                }
            });
        }
        
        // Parent Reviews logic
        renderReviews();
    });

    const reviewsData = @json($product->reviews->map(function($r) {
        $colors = ['#fce7f3', '#e0f2fe', '#f3e8ff', '#fef3c7', '#e0e7ff', '#ffedd5'];
        return [
            'name' => $r->user ? $r->user->name : 'Customer',
            'role' => 'Verified Customer',
            'tag' => 'Verified Purchase',
            'tagColor' => '#dcfce7',
            'tagText' => '#15803d',
            'text' => $r->comment,
            'rating' => $r->rating,
            'avatar' => $colors[array_rand($colors)]
        ];
    }));

    let currentReviewCount = 3;

    function renderReviews() {
        const grid = document.getElementById('reviewsGrid');
        if (!grid) return;
        grid.innerHTML = '';
        const visibleReviews = reviewsData.slice(0, currentReviewCount);
        
        visibleReviews.forEach(r => {
            const card = document.createElement('div');
            card.style.cssText = 'background:#fff;border-radius:16px;padding:24px;box-shadow:0 4px 15px rgba(0,0,0,0.03);display:flex;flex-direction:column;border:1px solid #f1f5f9;';
            card.innerHTML = `
                <div style="color:#fbbf24;font-size:1.1rem;margin-bottom:12px;letter-spacing:1px;">★★★★★</div>
                <div style="font-style:italic;color:#475569;font-size:0.95rem;line-height:1.6;margin-bottom:20px;flex:1;">
                    <span style="color:#fbcfe8;font-size:2.5rem;font-family:Georgia, serif;line-height:0;vertical-align:bottom;margin-right:5px;">"</span>
                    ${r.text}
                </div>
                <div style="display:flex;align-items:center;gap:15px;margin-top:auto;">
                    <div style="width:46px;height:46px;border-radius:50%;background:${r.avatar};"></div>
                    <div>
                        <div style="font-weight:800;font-size:0.95rem;color:#0f172a;margin-bottom:2px;">${r.name}</div>
                        <div style="font-size:0.75rem;color:#64748b;margin-bottom:6px;">${r.role}</div>
                        <div style="display:inline-block;background:${r.tagColor};color:${r.tagText};font-size:0.7rem;font-weight:700;padding:4px 10px;border-radius:12px;">✓ ${r.tag}</div>
                    </div>
                </div>
            `;
            grid.appendChild(card);
        });

        document.getElementById('loadMoreReviewsBtn').style.display = currentReviewCount >= reviewsData.length ? 'none' : 'inline-block';
        document.getElementById('readLessReviewsBtn').style.display = currentReviewCount > 3 ? 'inline-block' : 'none';
    }

    function loadMoreReviews() {
        currentReviewCount = Math.min(currentReviewCount + 3, reviewsData.length);
        renderReviews();
    }

    function readLessReviews() {
        currentReviewCount = 3;
        renderReviews();
    }
</script>
@endpush
