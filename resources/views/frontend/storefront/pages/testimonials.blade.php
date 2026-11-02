@extends('layouts.main')

@section('title', 'Testimonials - NutriBuddy')

@section('content')
    @php
        $sort = request('sort', 'latest');
        $baseQuery = \App\Models\ProductReview::with(['user', 'product'])->where('is_active', true);
        
        if (request('product_id')) {
            $baseQuery->where('product_id', request('product_id'));
        }

        if ($sort === 'positive') {
            $baseQuery->orderBy('rating', 'desc')->latest();
        } elseif ($sort === 'negative') {
            $baseQuery->orderBy('rating', 'asc')->latest();
        } else {
            $baseQuery->latest();
        }
        $textReviews = (clone $baseQuery)->whereNull('video_path')->paginate(5, ['*'], 'tpage')->appends(request()->query());
        $videoReviews = (clone $baseQuery)->whereNotNull('video_path')->paginate(5, ['*'], 'vpage')->appends(request()->query());
        
        $featuredReview = collect($textReviews->items())->first(function ($review) {
            return $review->image_path && Storage::disk('public')->exists($review->image_path);
        });

        if (!$featuredReview) {
            $featuredReview = collect($textReviews->items())->first();
        }
        
        $gradients = [
            'linear-gradient(160deg,#FF8FAB,#FF4D8F)',
            'linear-gradient(160deg,#7BC8FF,#0099DD)',
            'linear-gradient(160deg,#B79FFF,#7C3AED)',
            'linear-gradient(160deg,#6EF0C0,#00A87A)',
        ];
    @endphp

    <section class="testimonials-hero">
        <div class="testimonials-hero-inner">
            <div>
                <div class="testimonials-crumb">
                    <a href="{{ route('home') }}">Home</a>
                    <span>/</span>
                    <span>Testimonials</span>
                </div>
                <span class="testimonials-badge">Parent Reviews</span>
                <h1 class="testimonials-title">Real stories from <span>NutriBuddy</span> families</h1>
                <p class="testimonials-sub">Parents use NutriBuddy for daily wellness routines, picky eating support, focus, immunity, and calmer family habits. Here is what they are saying.</p>
                <div class="testimonials-actions">
                    <a class="testimonials-btn" href="{{ route('product') }}">Shop Products</a>
                    <a class="testimonials-link" href="{{ route('diet_chart') }}">Get Diet Chart</a>
                </div>
            </div>

            <div class="testimonials-score-card">
                @php
                    $totalReviews = \App\Models\ProductReview::where('is_active', true)->count();
                @endphp
                @if($totalReviews > 0)
                    @php
                        $displayAvg = number_format(\App\Models\ProductReview::where('is_active', true)->avg('rating'), 1);
                    @endphp
                    <div class="score-top">
                        <div class="score-number">{{ $displayAvg }}</div>
                        <div class="score-copy">
                            <strong>Parent rated</strong>
                            <span>Based on {{ number_format($totalReviews) }} verified reviews</span>
                            <div class="score-stars">
                                @for($i=0; $i<5; $i++)
                                    {{ $i < round((float)$displayAvg) ? '★' : '☆' }}
                                @endfor
                            </div>
                        </div>
                    </div>
                    <div class="score-bars">
                        @foreach([5, 4, 3, 2, 1] as $star)
                            @php
                                $starCount = \App\Models\ProductReview::where('is_active', true)->where('rating', $star)->count();
                                $pct = round(($starCount / $totalReviews) * 100, 1);
                            @endphp
                            <div class="score-row"><span>{{ $star }} ★</span><div class="score-track"><div class="score-fill" style="width:{{ $pct }}%"></div></div><span>{{ $pct }}%</span></div>
                        @endforeach
                    </div>
                @else
                    <div class="score-top" style="display:flex; justify-content:center; align-items:center; min-height: 120px;">
                        <div class="score-copy" style="text-align:center;">
                            <strong>No customer reviews yet.</strong>
                            <span>Check back later as our families share their stories!</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="testimonials-page">
        <div class="testimonials-wrap">
            <div class="testimonials-section-head">
                <div>
                    <span class="section-kicker">Family results</span>
                    <h2 class="section-title">Wellness routines parents can actually keep</h2>
                    <p class="section-sub">Clean, scannable stories from families using NutriBuddy as part of their child's everyday nutrition and wellness routine.</p>
                </div>
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <div class="trust-pills">
                        <span class="testimonials-trust-pill">Verified parents</span>
                        <span class="testimonials-trust-pill">Kid-approved taste</span>
                        <span class="testimonials-trust-pill">Daily routine friendly</span>
                    </div>
                    <div class="sort-pills" style="display:flex; gap:8px; overflow-x:auto; padding-bottom:4px; scrollbar-width:none;">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'latest']) }}" class="testimonials-trust-pill" style="text-decoration:none; {{ request('sort', 'latest') === 'latest' ? 'background:var(--pk); color:#fff; border-color:var(--pk);' : '' }}">Latest</a>
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'positive']) }}" class="testimonials-trust-pill" style="text-decoration:none; {{ request('sort') === 'positive' ? 'background:var(--pk); color:#fff; border-color:var(--pk);' : '' }}">Positive</a>
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'negative']) }}" class="testimonials-trust-pill" style="text-decoration:none; {{ request('sort') === 'negative' ? 'background:var(--pk); color:#fff; border-color:var(--pk);' : '' }}">Negative</a>
                    </div>
                </div>
            </div>

            @if($featuredReview)
            <div class="featured-story">
                <div class="featured-media" style="position: relative; overflow: hidden;">
                    @if($featuredReview->image_path)
                        <img src="{{ asset('storage/' . $featuredReview->image_path) }}" alt="Featured Review" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 0;">
                        <!-- Overlay for text readability -->
                        <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 60%); z-index: 1;"></div>
                    @else
                        <div class="featured-avatar" style="position: relative; z-index: 2;">💬</div>
                    @endif
                    <div class="featured-product" style="position: relative; z-index: 2; margin-top: auto;">Featured Story · {{ $featuredReview->product?->name ?? 'NutriBuddy' }}</div>
                </div>    
                
                <div class="featured-content">
                    <div class="featured-stars">
                        @for($i=0; $i<5; $i++) {{ $i < $featuredReview->rating ? '★' : '☆' }} @endfor
                    </div>
                    <div class="featured-quote">"{{ Str::limit($featuredReview->comment, 60) }}"</div>
                    <p class="featured-text">{{ $featuredReview->comment }}</p>
                    <div class="featured-author">
                        <div class="author-mark">{{ strtoupper(substr($featuredReview->user?->name ?? 'A', 0, 2)) }}</div>
                        <div>
                            <div class="author-name">{{ $featuredReview->user?->name ?? 'Anonymous Parent' }}</div>
                            <div class="author-meta">Verified Purchase</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="reviews-grid" id="textReviewsGrid">
                @foreach($textReviews->reject(fn($r) => $featuredReview && $r->id === $featuredReview->id)->values() as $index => $review)
                    @php $bgColor = ['#FFE8F5', '#E8F5FF', '#EDE9FE', '#FFF4D6', '#E7FFF5', '#FFEAF0'][$index % 6]; @endphp
                    <article class="review-card">
                        <div class="review-stars">
                            @for($i=0; $i<5; $i++) {{ $i < $review->rating ? '★' : '☆' }} @endfor
                        </div>
                        <span class="review-tag">Parent Review</span>
                        @php
                            $cardImages = [];
                            if (!empty($review->images)) {
                                $cardImages = $review->images;
                            } elseif ($review->image_path) {
                                $cardImages = [$review->image_path];
                            }
                        @endphp
                        @if(count($cardImages) > 0)
                            <div style="margin: 15px 0; display: flex; gap: 8px;">
                                @foreach(array_slice($cardImages, 0, 3) as $idx => $img)
                                    <div style="width: 70px; height: 70px; border-radius: 8px; overflow: hidden; position: relative; cursor: pointer;" onclick="openLightbox('{{ json_encode($cardImages) }}', {{ $idx }})">
                                        <img src="{{ asset('storage/' . $img) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @if($idx === 2 && count($cardImages) > 3)
                                            <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.5); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                                +{{ count($cardImages) - 3 }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <p class="review-text">"{{ $review->comment }}"</p>
                        <div class="review-author">
                            <div class="review-avatar" style="background: {{ $bgColor }}; color: var(--dk);">
                                {{ strtoupper(substr($review->user?->name ?? 'A', 0, 2)) }}
                            </div>
                            <div>
                                <div class="review-name">{{ $review->user?->name ?? 'Anonymous Parent' }}</div>
                                <div class="review-meta">Verified Purchase</div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if($textReviews->hasMorePages())
                <div class="mt-4" style="display: flex; justify-content: center; gap: 10px; margin-top: 40px;">
                    <button class="btn load-more-btn" id="loadMoreTextBtn" data-page="2" data-url="{{ request()->url() }}" style="background: var(--dk); color: #fff; border: none; padding: 10px 24px; border-radius: 30px; font-weight: 600; cursor: pointer;">Load More Testimonials</button>
                    <button class="btn show-less-btn" id="showLessTextBtn" style="display: none; background: #e2e8f0; color: var(--dk); border: none; padding: 10px 24px; border-radius: 30px; font-weight: 600; cursor: pointer;">Show Less</button>
                </div>
            @endif

            <div class="video-review-section">
                <div class="testimonials-section-head">
                    <div>
                        <span class="section-kicker">Video reviews</span>
                        <h2 class="section-title">Short stories from real routines</h2>
                        <p class="section-sub">A cleaner video review layout that feels native to the page and works well across desktop and mobile.</p>
                    </div>
                </div>

                <div class="video-strip" id="videoReviewsGrid">
                    @foreach($videoReviews as $index => $video)
                        @php $bg = $gradients[$index % count($gradients)]; @endphp
                        <article class="video-card" style="background: {{ $bg }}; position: relative; overflow: hidden; cursor: pointer;" onclick="toggleTestimonialVideo(this, event)">
                            <video loop playsinline preload="metadata" style="position: absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; opacity: 0.6; transition: opacity 0.3s;">
                                @if($video->video_path && Storage::disk('public')->exists($video->video_path))
                                    <source src="{{ asset('storage/' . $video->video_path) }}" type="video/mp4">
                                @endif
                            </video>
                            <div style="display: flex; justify-content: space-between; position: relative; z-index: 2; align-items: center;">
                                <div class="video-mute-toggle" style="background: rgba(255,255,255,0.22); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); cursor: pointer;" onclick="toggleMute(this, event)">🔇</div>
                                <div class="video-play">▶</div>
                            </div>
                            <div class="video-info" style="position: relative; z-index: 2; pointer-events: none;">
                                <div class="video-stars">
                                    @for($i=0; $i<5; $i++) {{ $i < $video->rating ? '★' : '☆' }} @endfor
                                </div>
                                <div class="video-name">{{ $video->user?->name ?? 'Anonymous Parent' }}</div>
                                <div class="video-copy">{{ Str::limit($video->comment, 50) }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if($videoReviews->hasMorePages())
                    <div class="mt-4" style="display: flex; justify-content: center; gap: 10px; margin-top: 30px;">
                        <button class="btn load-more-btn" id="loadMoreVideosBtn" data-page="2" data-url="{{ request()->url() }}" style="background: var(--dk); color: #fff; border: none; padding: 10px 24px; border-radius: 30px; font-weight: 600; cursor: pointer;">Load More Videos</button>
                        <button class="btn show-less-btn" id="showLessVideosBtn" style="display: none; background: #e2e8f0; color: var(--dk); border: none; padding: 10px 24px; border-radius: 30px; font-weight: 600; cursor: pointer;">Show Less</button>
                    </div>
                @endif

                <script>
                    function toggleTestimonialVideo(card, e) {
                        const video = card.querySelector('video');
                        const playBtn = card.querySelector('.video-play');
                        const muteBtn = card.querySelector('.video-mute-toggle');
                        if (!video) return;
                        
                        if (video.paused) {
                            // Pause all others
                            document.querySelectorAll('.video-card video').forEach(v => { 
                                v.pause(); 
                                v.muted = true; 
                                v.style.opacity = '0.6';
                                v.parentElement.querySelector('.video-play').innerText = '▶';
                                v.parentElement.querySelector('.video-mute-toggle').innerText = '🔇';
                            });
                            
                            video.muted = false; // start with sound
                            muteBtn.innerText = '🔊';
                            video.play();
                            playBtn.innerText = 'II';
                            video.style.opacity = '1';
                        } else {
                            video.pause();
                            video.muted = true;
                            muteBtn.innerText = '🔇';
                            playBtn.innerText = '▶';
                            video.style.opacity = '0.6';
                        }
                    }

                    function toggleMute(btn, e) {
                        e.stopPropagation(); // prevent clicking the card
                        const card = btn.closest('.video-card');
                        const video = card.querySelector('video');
                        if (!video) return;

                        if (video.muted) {
                            video.muted = false;
                            btn.innerText = '🔊';
                        } else {
                            video.muted = true;
                            btn.innerText = '🔇';
                        }
                    }

                    // --- AJAX Pagination Logic ---
                    function setupLoadMore(btnId, lessBtnId, gridId, pageParam) {
                        const btn = document.getElementById(btnId);
                        const lessBtn = document.getElementById(lessBtnId);
                        if (!btn) return;
                        
                        btn.addEventListener('click', function() {
                            const page = this.dataset.page;
                            const url = new URL(this.dataset.url);
                            url.searchParams.set(pageParam, page);
                            
                            if (!btn.dataset.originalText) btn.dataset.originalText = btn.innerText;
                            btn.innerText = 'Loading...';
                            btn.disabled = true;
                            
                            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(res => res.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newGrid = doc.getElementById(gridId);
                                
                                if (newGrid && newGrid.innerHTML.trim() !== '') {
                                    Array.from(newGrid.children).forEach(child => {
                                        child.dataset.loadedPage = page;
                                        document.getElementById(gridId).appendChild(child);
                                    });
                                    
                                    const newBtn = doc.getElementById(btnId);
                                    if (newBtn) {
                                        btn.dataset.page = newBtn.dataset.page;
                                        btn.innerText = btn.dataset.originalText;
                                        btn.disabled = false;
                                    } else {
                                        btn.style.display = 'none'; 
                                    }
                                    if (lessBtn) lessBtn.style.display = 'inline-block';
                                } else {
                                    btn.style.display = 'none';
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                if (btn.dataset.originalText) btn.innerText = btn.dataset.originalText;
                                btn.disabled = false;
                            });
                        });

                        if (lessBtn) {
                            lessBtn.addEventListener('click', function() {
                                const grid = document.getElementById(gridId);
                                const loadedItems = Array.from(grid.querySelectorAll('[data-loaded-page]'));
                                if (loadedItems.length === 0) return;
                                
                                const pages = loadedItems.map(i => parseInt(i.dataset.loadedPage));
                                const maxPage = Math.max(...pages);
                                
                                grid.querySelectorAll(`[data-loaded-page="${maxPage}"]`).forEach(i => i.remove());
                                
                                btn.dataset.page = maxPage;
                                btn.style.display = 'inline-block';
                                btn.disabled = false;
                                if (btn.dataset.originalText) btn.innerText = btn.dataset.originalText;
                                
                                if (maxPage <= 2) lessBtn.style.display = 'none';
                            });
                        }
                    }
                    
                    setupLoadMore('loadMoreVideosBtn', 'showLessVideosBtn', 'videoReviewsGrid', 'vpage');
                    setupLoadMore('loadMoreTextBtn', 'showLessTextBtn', 'textReviewsGrid', 'tpage');
                </script>
            </div>

            <div class="testimonial-cta">
                <div>
                    <h2>Ready to build your child's daily wellness routine?</h2>
                    <p>Explore gummies by goal, compare formulas, or start with a personalized diet chart to understand what your child needs most.</p>
                </div>
                <a class="testimonials-btn" href="{{ route('product') }}">Explore Products</a>
            </div>
        </div>
    </section>

    <!-- Lightbox Modal -->
    <div id="imageLightbox" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.9); align-items: center; justify-content: center; flex-direction: column;">
        <button onclick="closeLightbox()" style="position: absolute; top: 20px; right: 30px; background: none; border: none; color: white; font-size: 30px; cursor: pointer;">✕</button>
        
        <div style="position: relative; max-width: 90%; max-height: 80vh; display: flex; align-items: center;">
            <button id="lbPrev" style="position: absolute; left: -50px; background: none; border: none; color: white; font-size: 40px; cursor: pointer;">‹</button>
            <img id="lbMainImg" style="max-width: 100%; max-height: 80vh; object-fit: contain; border-radius: 8px;">
            <button id="lbNext" style="position: absolute; right: -50px; background: none; border: none; color: white; font-size: 40px; cursor: pointer;">›</button>
        </div>
        
        <div id="lbThumbnails" style="display: flex; gap: 10px; margin-top: 20px; max-width: 90%; overflow-x: auto; padding-bottom: 10px;"></div>
    </div>

    <script>
        let currentLightboxImages = [];
        let currentLightboxIndex = 0;

        function openLightbox(imagesJson, startIndex = 0) {
            try {
                currentLightboxImages = JSON.parse(imagesJson);
                if(currentLightboxImages.length === 0) return;
                
                currentLightboxIndex = startIndex;
                document.getElementById('imageLightbox').style.display = 'flex';
                updateLightbox();
            } catch(e) {}
        }

        function closeLightbox() {
            document.getElementById('imageLightbox').style.display = 'none';
        }

        function updateLightbox() {
            const mainImg = document.getElementById('lbMainImg');
            const thumbsContainer = document.getElementById('lbThumbnails');
            
            mainImg.src = '/storage/' + currentLightboxImages[currentLightboxIndex];
            
            thumbsContainer.innerHTML = '';
            currentLightboxImages.forEach((img, idx) => {
                const thumb = document.createElement('img');
                thumb.src = '/storage/' + img;
                thumb.style.height = '60px';
                thumb.style.width = '60px';
                thumb.style.objectFit = 'cover';
                thumb.style.borderRadius = '6px';
                thumb.style.cursor = 'pointer';
                thumb.style.opacity = idx === currentLightboxIndex ? '1' : '0.5';
                thumb.style.border = idx === currentLightboxIndex ? '2px solid white' : 'none';
                thumb.onclick = () => {
                    currentLightboxIndex = idx;
                    updateLightbox();
                };
                thumbsContainer.appendChild(thumb);
            });

            document.getElementById('lbPrev').style.display = currentLightboxImages.length > 1 ? 'block' : 'none';
            document.getElementById('lbNext').style.display = currentLightboxImages.length > 1 ? 'block' : 'none';
        }

        document.getElementById('lbPrev').onclick = () => {
            currentLightboxIndex = (currentLightboxIndex - 1 + currentLightboxImages.length) % currentLightboxImages.length;
            updateLightbox();
        };

        document.getElementById('lbNext').onclick = () => {
            currentLightboxIndex = (currentLightboxIndex + 1) % currentLightboxImages.length;
            updateLightbox();
        };
    </script>
@endsection
