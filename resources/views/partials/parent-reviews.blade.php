@php
    if (isset($product) && $product) {
        // Product specific
        $baseQuery = $product->reviews()->where('is_active', true);
    } else {
        // Global / Homepage
        $baseQuery = \App\Models\ProductReview::where('is_active', true);
    }

    // For summary calculation
    $allActiveReviews = (clone $baseQuery)->latest()->take(20)->get();

    $hasDynamicProductReviews = $allActiveReviews->isNotEmpty();
    $gradients = [
        'linear-gradient(160deg,#FF8FAB,#FF4D8F)',
        'linear-gradient(160deg,#7BC8FF,#0099DD)',
        'linear-gradient(160deg,#B79FFF,#7C3AED)',
        'linear-gradient(160deg,#FFD97D,#FF9900)',
        'linear-gradient(160deg,#6EF0C0,#00A87A)',
        'linear-gradient(160deg,#FFB3C6,#FF6B8A)',
    ];

    if ($hasDynamicProductReviews) {
        if (isset($product) && $product) {
            $videoReviews = (clone $baseQuery)->whereNotNull('video_path')->latest()->paginate(5, ['*'], 'vpage');
            $textReviews = (clone $baseQuery)->whereNull('video_path')->latest()->paginate(5, ['*'], 'tpage');
        } else {
            $videoReviews = (clone $baseQuery)->whereNotNull('video_path')->latest()->take(10)->get();
            $textReviews = (clone $baseQuery)->whereNull('video_path')->latest()->take(10)->get();
        }
    } else {
        $videoReviews = collect();
        $textReviews = collect();
    }
@endphp
<style>
    .wreviews-viewport {
        overflow: hidden;
        width: 100%;
        padding: 10px 0;
    }

    .wreviews-track {
        display: flex;
        gap: 22px;
        transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1);
        width: 100%;
    }

    .wreviews-track .wrev {
        flex: 0 0 calc((100% - 44px) / 3);
        margin-top: 0 !important;
    }

    @media (max-width: 991px) {
        .wreviews-track .wrev {
            flex: 0 0 calc((100% - 22px) / 2);
        }
    }

    @media (max-width: 576px) {
        .wreviews-track .wrev {
            flex: 0 0 100%;
        }
    }

    .wreviews-dots .wdot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #e2e8f0;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        padding: 0;
    }

    .wreviews-dots .wdot.active {
        background: var(--pk);
        transform: scale(1.2);
    }

    .hide-scroll::-webkit-scrollbar {
        display: none;
    }

    .hide-scroll {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .slider-nav-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.2s;
        font-size: 14px;
    }

    .slider-nav-btn:hover {
        background: var(--pk);
        color: #fff;
        border-color: var(--pk);
    }

    .mobile-nav {
        display: none;
    }

    @media (max-width: 768px) {
        .desktop-nav {
            display: none !important;
        }

        .mobile-nav {
            display: flex !important;
            justify-content: center;
            margin-top: 20px;
        }

        .custom-reel {
            flex: 0 0 45vw !important;
            min-width: 150px;
        }

        .custom-wrev {
            flex: 0 0 85vw !important;
        }
    }

    /* Lightbox mobile responsive */
    @media (max-width: 600px) {
        #lbPrev {
            left: 4px !important;
        }

        #lbNext {
            right: 4px !important;
        }

        #imageLightbox>div {
            max-width: 100vw !important;
        }
    }
</style>
<!-- ══════════════════════════════════════════
                   TESTIMONIALS
              ══════════════════════════════════════════ -->
<section class="testi-section-new reveal ">
    @if(!isset($hideSummary) || !$hideSummary)
        <section class="testi-section reveal" id="reviews">
            <span class="sec-eye">Parent Reviews</span>
            <h2 class="sec-title" style="text-align:center">10,000+ Happy Families </h2>

            @if($hasDynamicProductReviews)
                @php
                    $totalReviews = $allActiveReviews->count();
                    $displayAvg = number_format($allActiveReviews->avg('rating'), 1);
                @endphp
                <div class="rev-summary reveal">
                    <div class="rev-big">
                        <div class="rev-big-n">{{ $displayAvg }}</div>
                        <div class="rev-big-stars">
                            @for($i = 0; $i < 5; $i++)
                                {{ $i < round((float) $displayAvg) ? '★' : '☆' }}
                            @endfor
                        </div>
                        <div class="rev-big-l">Based on {{ number_format($totalReviews) }} reviews</div>
                    </div>
                    <div class="rev-bars">
                        @foreach([5, 4, 3, 2, 1] as $star)
                            @php
                                $starCount = $allActiveReviews->where('rating', $star)->count();
                                $pct = $totalReviews > 0 ? round(($starCount / $totalReviews) * 100, 1) : 0;
                            @endphp
                            <div class="rbar-row">{{ $star }} ★
                                <div class="rbar-track">
                                    <div class="rbar-fill" style="width:{{ $pct }}%"></div>
                                </div> {{ $pct }}%
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="rev-summary reveal"
                    style="display:flex; justify-content:center; align-items:center; min-height: 150px; background: #fff; border-radius: 16px; border: 1px dashed #cbd5e1; box-shadow: none;">
                    <div style="text-align:center;">
                        <div style="font-size: 2rem; margin-bottom: 10px;">⭐</div>
                        <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 5px;">No customer
                            reviews yet.</div>
                        <div style="color: #64748b; font-size: 0.9rem;">Be the first to review this product.</div>
                    </div>
                </div>
            @endif
        </section>
    @endif
    @if($videoReviews->isNotEmpty())
        <div class="reels-section-wrap" style="margin-top: 48px;">
            <div class="reels-header"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <p class="reels-title" style="margin-bottom: 0;">Parent Video Reviews</p>
                <div class="slider-nav-wrap desktop-nav" style="display: flex; gap: 12px;">
                    <button class="slider-nav-btn"
                        onclick="document.getElementById('videoReviewsGrid').scrollBy({left: -280, behavior: 'smooth'})">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                    </button>
                    <button class="slider-nav-btn"
                        onclick="document.getElementById('videoReviewsGrid').scrollBy({left: 280, behavior: 'smooth'})">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 18l6-6-6-6" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="video-reviews-grid hide-scroll" id="videoReviewsGrid"
                style="display: flex; overflow-x: auto; scroll-snap-type: x mandatory; gap: 20px; padding-bottom: 10px;">
                @foreach($videoReviews as $index => $review)
                    @php $grad = $gradients[$index % count($gradients)]; @endphp
                    <div class="reel custom-reel"
                        style="background:{{ $grad }}; margin: 0; flex: 0 0 260px; scroll-snap-align: center;">
                        <div class="reel-bg">
                            <video muted loop playsinline preload="auto">
                                <source src="{{ asset('storage/' . $review->video_path) }}" type="video/mp4">
                            </video>
                        </div>
                        <div class="reel-ov"></div>
                        <div class="custom-mute-btn"
                            style="position: absolute; top: 12px; right: 12px; width: 36px; height: 36px; background: rgba(255,255,255,0.22); border-radius: 50%; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); cursor: pointer; z-index: 10;">
                            🔇</div>
                        <div class="custom-play-btn"
                            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 2rem; z-index: 5; pointer-events: none; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
                            ▶</div>
                        <div class="reel-info">
                            <div class="reel-stars">
                                @for($i = 0; $i < 5; $i++)
                                    {{ $i < $review->rating ? '★' : '☆' }}
                                @endfor
                            </div>
                            <div class="reel-ava">👱‍♀️</div>
                            <div class="reel-name">{{ $review->user?->name ?? 'Anonymous Parent' }}</div>
                            <div class="reel-txt">"{{ $review->comment }}"</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Mobile Navigation (hidden on desktop, centered below grid on mobile) -->
            <div class="slider-nav-wrap mobile-nav" style="gap: 16px;">
                <button class="slider-nav-btn"
                    onclick="document.getElementById('videoReviewsGrid').scrollBy({left: -280, behavior: 'smooth'})">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6" />
                    </svg>
                </button>
                <button class="slider-nav-btn"
                    onclick="document.getElementById('videoReviewsGrid').scrollBy({left: 280, behavior: 'smooth'})">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18l6-6-6-6" />
                    </svg>
                </button>
            </div>

        </div>
    @endif

    @if($textReviews->isNotEmpty())
        <div class="wreviews-section-wrap" style="position: relative; margin-top: 48px;">
            <div class="reels-header"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <p class="reels-title" style="margin-bottom: 0;">What Parents Say</p>
                <div class="slider-nav-wrap desktop-nav" style="display: flex; gap: 12px;">
                    <button class="slider-nav-btn"
                        onclick="document.getElementById('textReviewsGrid').scrollBy({left: -340, behavior: 'smooth'})">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                    </button>
                    <button class="slider-nav-btn"
                        onclick="document.getElementById('textReviewsGrid').scrollBy({left: 340, behavior: 'smooth'})">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 18l6-6-6-6" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="text-reviews-grid hide-scroll" id="textReviewsGrid"
                style="display: flex; overflow-x: auto; scroll-snap-type: x mandatory; gap: 20px; padding-bottom: 10px;">
                @foreach($textReviews as $review)
                    <div class="wrev custom-wrev"
                        style="margin: 0; flex: 0 0 320px; scroll-snap-align: center; border-radius: 16px;">
                        <div class="wrev-stars">
                            @for($i = 0; $i < 5; $i++)
                                {{ $i < $review->rating ? '★' : '☆' }}
                            @endfor
                        </div>
                        <p class="wrev-txt">{{ $review->comment }}</p>
                        @php
                            $cardImages = [];
                            if (!empty($review->images)) {
                                $cardImages = $review->images;
                            } elseif ($review->image_path) {
                                $cardImages = [$review->image_path];
                            }
                        @endphp
                        @if(count($cardImages) > 0)
                            <div style="margin: 12px 0; display: flex; gap: 8px;">
                                @foreach(array_slice($cardImages, 0, 3) as $idx => $img)
                                    <div style="width: 70px; height: 70px; border-radius: 8px; overflow: hidden; position: relative; cursor: pointer;"
                                        onclick="openLightbox('{{ json_encode($cardImages) }}', {{ $idx }})">
                                        <img src="{{ asset('storage/' . $img) }}" alt="Review Image"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                        @if($idx === 2 && count($cardImages) > 3)
                                            <div
                                                style="position: absolute; inset: 0; background: rgba(0,0,0,0.5); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                                +{{ count($cardImages) - 3 }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <div class="wrev-author">
                            <div class="wrev-ava" style="background:{{ $loop->index % 2 == 0 ? '#FFE8F5' : '#E8F5FF' }}"></div>
                            <div>
                                <div class="wrev-name">{{ $review->user?->name ?? 'Anonymous Parent' }}</div>
                                <div class="wrev-meta">Verified Parent</div>
                                <div class="wrev-badge">✓ Verified Purchase</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Mobile Navigation (hidden on desktop, centered below grid on mobile) -->
            <div class="slider-nav-wrap mobile-nav" style="gap: 16px;">
                <button class="slider-nav-btn"
                    onclick="document.getElementById('textReviewsGrid').scrollBy({left: -340, behavior: 'smooth'})">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6" />
                    </svg>
                </button>
                <button class="slider-nav-btn"
                    onclick="document.getElementById('textReviewsGrid').scrollBy({left: 340, behavior: 'smooth'})">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18l6-6-6-6" />
                    </svg>
                </button>
            </div>

            @if(isset($product) && $product)
                <div style="display: flex; justify-content: center; margin-top: 32px; padding: 0 16px;">
                    <a href="{{ route('testimonials', ['product_id' => $product->id]) }}" class="show-all-reviews-btn">Show all
                        reviews</a>
                </div>
            @endif
        </div>
    @endif
</section>

@once
    <!-- Lightbox Modal -->
    <div id="imageLightbox"
        style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(0,0,0,0.92); align-items:center; justify-content:center; flex-direction:column;"
        onclick="handleLightboxBackdropClick(event)">
        <button onclick="closeLightbox()"
            style="position:absolute; top:18px; right:24px; background:rgba(255,255,255,0.12); border:none; color:#fff; width:40px; height:40px; border-radius:50%; font-size:20px; cursor:pointer; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px); transition:.2s; z-index:3;"
            onmouseenter="this.style.background='rgba(255,255,255,0.25)'"
            onmouseleave="this.style.background='rgba(255,255,255,0.12)'">✕</button>

        <div
            style="position:relative; max-width:92vw; max-height:75vh; display:flex; align-items:center; justify-content:center;">
            <button id="lbPrev"
                style="position:absolute; left:-52px; background:rgba(255,255,255,0.12); border:none; color:#fff; width:44px; height:44px; border-radius:50%; font-size:26px; cursor:pointer; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px); transition:.2s; z-index:2;"
                onmouseenter="this.style.background='rgba(255,255,255,0.28)'"
                onmouseleave="this.style.background='rgba(255,255,255,0.12)'">‹</button>
            <img id="lbMainImg" src="" alt="Review photo"
                style="max-width:92vw; max-height:75vh; object-fit:contain; border-radius:10px; box-shadow:0 8px 40px rgba(0,0,0,0.6); user-select:none;">
            <button id="lbNext"
                style="position:absolute; right:-52px; background:rgba(255,255,255,0.12); border:none; color:#fff; width:44px; height:44px; border-radius:50%; font-size:26px; cursor:pointer; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px); transition:.2s; z-index:2;"
                onmouseenter="this.style.background='rgba(255,255,255,0.28)'"
                onmouseleave="this.style.background='rgba(255,255,255,0.12)'">›</button>
        </div>

        <div id="lbCounter" style="color:rgba(255,255,255,0.6); font-size:0.85rem; margin-top:12px; letter-spacing:0.5px;">
        </div>

        <div id="lbThumbnails"
            style="display:flex; gap:8px; margin-top:14px; max-width:90vw; overflow-x:auto; padding:4px 0 8px; scrollbar-width:thin;">
        </div>
    </div>

    <script>
        @if(isset($product) && $product)
            document.addEventListener('DOMContentLoaded', function () {
                function setupDots(gridId, dotsId) {
                    const grid = document.getElementById(gridId);
                    const dotsContainer = document.getElementById(dotsId);
                    if (!grid || !dotsContainer) return;

                    const dots = dotsContainer.querySelectorAll('.dot');

                    grid.addEventListener('scroll', () => {
                        const scrollLeft = grid.scrollLeft;
                        const itemWidth = grid.scrollWidth / dots.length;
                        const activeIndex = Math.round(scrollLeft / itemWidth);

                        dots.forEach((dot, index) => {
                            if (index === activeIndex) {
                                dot.style.background = '#ff4d8f';
                                dot.classList.add('active');
                            } else {
                                dot.style.background = '#e2e8f0';
                                dot.classList.remove('active');
                            }
                        });
                    });
                }

                setupDots('videoReviewsGrid', 'videoDots');
                setupDots('textReviewsGrid', 'textDots');
            });
        @endif

        let currentLightboxImages = [];
        let currentLightboxIndex = 0;
        let lbTouchStartX = 0;

        function openLightbox(imagesJson, startIndex = 0) {
            try {
                currentLightboxImages = typeof imagesJson === 'string' ? JSON.parse(imagesJson) : imagesJson;
                if (currentLightboxImages.length === 0) return;

                currentLightboxIndex = startIndex;
                document.getElementById('imageLightbox').style.display = 'flex';
                document.body.style.overflow = 'hidden';
                updateLightbox();
            } catch (e) { console.error('Lightbox error:', e); }
        }

        function closeLightbox() {
            document.getElementById('imageLightbox').style.display = 'none';
            document.body.style.overflow = '';
        }

        function handleLightboxBackdropClick(e) {
            if (e.target === document.getElementById('imageLightbox')) closeLightbox();
        }

        function updateLightbox() {
            const mainImg = document.getElementById('lbMainImg');
            const thumbsContainer = document.getElementById('lbThumbnails');
            const counter = document.getElementById('lbCounter');
            const btnPrev = document.getElementById('lbPrev');
            const btnNext = document.getElementById('lbNext');
            if (!mainImg || !thumbsContainer) return;

            mainImg.src = '/storage/' + currentLightboxImages[currentLightboxIndex];
            if (counter) counter.textContent = `${currentLightboxIndex + 1} / ${currentLightboxImages.length}`;

            thumbsContainer.innerHTML = '';
            currentLightboxImages.forEach((img, idx) => {
                const thumb = document.createElement('img');
                thumb.src = '/storage/' + img;
                thumb.alt = 'thumbnail';
                thumb.style.cssText = `width:56px; height:56px; object-fit:cover; border-radius:7px; cursor:pointer; flex-shrink:0; transition:.2s; border:2px solid ${idx === currentLightboxIndex ? '#fff' : 'transparent'}; opacity:${idx === currentLightboxIndex ? '1' : '0.55'};`;
                thumb.onclick = (e) => { e.stopPropagation(); currentLightboxIndex = idx; updateLightbox(); };
                thumbsContainer.appendChild(thumb);
            });

            // Scroll active thumb into view
            const activeThumb = thumbsContainer.children[currentLightboxIndex];
            if (activeThumb) activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });

            if (btnPrev) btnPrev.style.display = currentLightboxImages.length > 1 ? 'flex' : 'none';
            if (btnNext) btnNext.style.display = currentLightboxImages.length > 1 ? 'flex' : 'none';
        }

        const btnPrev = document.getElementById('lbPrev');
        if (btnPrev) btnPrev.onclick = (e) => { e.stopPropagation(); currentLightboxIndex = (currentLightboxIndex - 1 + currentLightboxImages.length) % currentLightboxImages.length; updateLightbox(); };

        const btnNext = document.getElementById('lbNext');
        if (btnNext) btnNext.onclick = (e) => { e.stopPropagation(); currentLightboxIndex = (currentLightboxIndex + 1) % currentLightboxImages.length; updateLightbox(); };

        // Keyboard navigation
        document.addEventListener('keydown', function (e) {
            const lb = document.getElementById('imageLightbox');
            if (!lb || lb.style.display !== 'flex') return;
            if (e.key === 'ArrowLeft') { currentLightboxIndex = (currentLightboxIndex - 1 + currentLightboxImages.length) % currentLightboxImages.length; updateLightbox(); }
            if (e.key === 'ArrowRight') { currentLightboxIndex = (currentLightboxIndex + 1) % currentLightboxImages.length; updateLightbox(); }
            if (e.key === 'Escape') closeLightbox();
        });

        // Touch swipe
        const lbEl = document.getElementById('imageLightbox');
        if (lbEl) {
            lbEl.addEventListener('touchstart', (e) => { lbTouchStartX = e.touches[0].clientX; }, { passive: true });
            lbEl.addEventListener('touchend', (e) => {
                const diff = lbTouchStartX - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 40) {
                    if (diff > 0) { currentLightboxIndex = (currentLightboxIndex + 1) % currentLightboxImages.length; }
                    else { currentLightboxIndex = (currentLightboxIndex - 1 + currentLightboxImages.length) % currentLightboxImages.length; }
                    updateLightbox();
                }
            }, { passive: true });
        }

        // --- Video Playback Logic ---
        document.addEventListener('click', function (e) {
            const reel = e.target.closest('.custom-reel');
            if (!reel) return;

            const video = reel.querySelector('video');
            const playBtn = reel.querySelector('.custom-play-btn');

            if (e.target.closest('.custom-mute-btn')) {
                video.muted = !video.muted;
                e.target.closest('.custom-mute-btn').innerText = video.muted ? '🔇' : '🔊';
                return;
            }

            if (video) {
                if (video.paused) {
                    document.querySelectorAll('.custom-reel video').forEach(v => {
                        v.pause();
                        const pb = v.closest('.custom-reel').querySelector('.custom-play-btn');
                        if (pb) pb.style.display = 'block';
                    });
                    video.play();
                    if (playBtn) playBtn.style.display = 'none';
                } else {
                    video.pause();
                    if (playBtn) playBtn.style.display = 'block';
                }
            }
        });
    </script>
@endonce