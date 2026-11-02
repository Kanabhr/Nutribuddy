@extends('layouts.user-panel')
@section('title', 'My Reviews — NutriBuddy Kids')
@section('panel-page-class', 'panel-reviews')
@section('panel-content')
   
    <style>
        .rev-card {
            background: #fff;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            border: 1px solid #f3f4f6;
            margin-bottom: 20px;
            transition: 0.3s ease;
        }
        .rev-card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.06);
            border-color: #fce7f3;
        }
        .rev-upload-btn {
            border: 2px dashed #e2e8f0;
            border-radius: 14px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: #f8fafc;
            transition: 0.2s;
            color: #64748b;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .rev-upload-btn:hover {
            background: #fff1f2;
            border-color: #fda4af;
            color: #e11d48;
        }
        .preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 12px;
        }
        .preview-box {
            position: relative;
            width: 65px;
            height: 65px;
            border-radius: 12px;
            overflow: visible;
        }
        .preview-box-inner {
            width: 100%;
            height: 100%;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #fff;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            background: #000;
        }
        .preview-box img, .preview-box video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .remove-btn {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #ef4444;
            color: #fff;
            border: 2px solid #fff;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 10px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            padding: 0;
            transition: 0.2s;
        }
        .remove-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }
        .star-opt {
            font-size: 1.8rem;
            color: #e2e8f0;
            cursor: pointer;
            transition: 0.2s;
            margin: 0 2px;
        }
        .rev-textarea {
            width: 100%;
            padding: 15px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            font-size: 0.95rem;
            background: #f8fafc;
            transition: 0.3s;
            resize: none;
        }
        .rev-textarea:focus {
            outline: none;
            border-color: #f472b6;
            background: #fff;
            box-shadow: 0 0 0 4px #fce7f3;
        }
        /* Lightbox mobile responsive */
        @media (max-width: 600px) {
            #rlbPrev { left: 4px !important; }
            #rlbNext { right: 4px !important; }
        }
    </style>

    <div class="row">
        <div class="col-12">
            <h5 style="font-family: 'Fredoka One', cursive; color: var(--dk); margin-bottom: 24px; font-size: 1.3rem; border-left: 5px solid var(--pk); padding-left: 14px;">Pending Your Review</h5>

            @forelse($purchasedProducts as $product)
                <div class="rev-card">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div style="width: 70px; height: 70px; border-radius: 14px; overflow: hidden; border: 1px solid #f1f5f9; background: #fff; padding: 5px;">
                                <img src="{{ $product->primaryImage ? asset('storage/' . $product->primaryImage->image_path) : asset('img/product2.png') }}" style="width: 100%; height: 100%; object-fit: contain;">
                            </div>
                        </div>
                        <div class="col">
                            <h6 style="font-weight: 800; margin: 0 0 4px; color: var(--dk); font-size: 1.05rem;">{{ $product->name }}</h6>
                            <span style="font-size: 0.8rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">{{ $product->category->name ?? 'Immunity & Growth' }}</span>
                        </div>
                        <div class="col-auto">
                            <button onclick="toggleReviewForm('{{ $product->id }}')" style="padding: 10px 24px; border-radius: 50px; font-size: 0.9rem; font-weight: 700; background: linear-gradient(135deg, #ff4d8f, #f43f5e); color: #fff; border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(244,63,94,0.3); transition: 0.2s;">
                                Rate Now
                            </button>
                        </div>
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger" style="margin-top: 15px; border-radius: 12px; font-size: 0.9rem;">
                            <ul style="margin: 0; padding-left: 20px;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Hidden Review Form -->
                    <div id="reviewForm_{{ $product->id }}" style="display: none; margin-top: 24px; padding-top: 24px; border-top: 1px dashed #e2e8f0;">
                        <form action="{{ route('reviews.store', $product->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row g-4">
                                <div class="col-md-3 text-center d-flex flex-column justify-content-center">
                                    <label style="display:block; font-weight:800; font-size:0.75rem; color:#94a3b8; margin-bottom:10px; letter-spacing: 1px;">YOUR RATING</label>
                                    <div class="rating-input_{{ $product->id }}">
                                        @for($i=1; $i<=5; $i++)
                                            <span data-val="{{ $i }}" class="star-opt star-opt-{{ $product->id }}">★</span>
                                        @endfor
                                    </div>
                                    <input type="hidden" name="rating" id="ratingValue_{{ $product->id }}" value="5">
                                </div>
                                
                                <div class="col-md-5">
                                    <textarea name="comment" rows="3" class="rev-textarea" placeholder="Tell us what you loved about it..." required></textarea>
                                </div>
                                
                                <div class="col-md-4">
                                    <div style="display: flex; gap: 10px;">
                                        <!-- Photo Upload -->
                                        <div style="flex: 1;">
                                            <label class="rev-upload-btn" onclick="document.getElementById('img_input_{{ $product->id }}').click()">
                                                <span>+ Photos</span>
                                            </label>
                                            <input type="file" id="img_input_{{ $product->id }}" name="review_images[]" multiple accept="image/*" style="display: none;" onchange="handleFileSelect(event, '{{ $product->id }}', 'image')">
                                        </div>
                                        
                                        <!-- Video Upload -->
                                        <div style="flex: 1;">
                                            <label class="rev-upload-btn" onclick="document.getElementById('vid_input_{{ $product->id }}').click()">
                                                <span>+ Video</span>
                                            </label>
                                            <input type="file" id="vid_input_{{ $product->id }}" name="review_video" accept="video/*" style="display: none;" onchange="handleFileSelect(event, '{{ $product->id }}', 'video')">
                                        </div>
                                    </div>

                                    <!-- Previews Container -->
                                    <div id="previewContainer_image_{{ $product->id }}" class="preview-container"></div>
                                    <div id="previewContainer_video_{{ $product->id }}" class="preview-container"></div>

                                    <button type="submit" style="width: 100%; margin-top: 16px; padding: 12px; border-radius: 14px; font-size: 0.95rem; font-weight: 800; background: var(--dk); color: #fff; border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: 0.2s;">
                                        Post Review 🚀
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 20px; border: 2px dashed #e2e8f0;">
                    <iconify-icon icon="solar:box-minimalistic-bold-duotone" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 16px;"></iconify-icon>
                    <h5 style="color: #64748b; margin: 0; font-weight: 700;">No products to review yet</h5>
                    <p style="color: #94a3b8; font-size: 0.9rem; margin-top: 8px;">Purchase some products to leave a review!</p>
                </div>
            @endforelse

            <!-- MODERN CARD-BASED HISTORY -->
            @if($userReviews->count() > 0)
                <h5 style="font-family: 'Fredoka One', cursive; color: var(--dk); margin: 60px 0 25px; font-size: 1.3rem; border-left: 5px solid var(--mn); padding-left: 14px;">My Review History</h5>
                
                <div style="display: grid; gap: 24px;">
                    @foreach($userReviews as $review)
                        <div class="rev-card" style="padding: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                                <div style="display: flex; align-items: center; gap: 16px;">
                                    <div style="width: 65px; height: 65px; border-radius: 14px; overflow: hidden; border: 1px solid #f1f5f9; background: #fff; padding: 5px; flex-shrink: 0;">
                                        <img src="{{ $review->product->primaryImage ? asset('storage/' . $review->product->primaryImage->image_path) : asset('img/product2.png') }}" onerror="this.onerror=null; this.src='{{ asset('img/product2.png') }}';" style="width: 100%; height: 100%; object-fit: contain;">
                                    </div>
                                    <div>
                                        <h4 style="font-family: 'Nunito', sans-serif; font-weight: 800; color: var(--dk); margin: 0 0 6px; font-size: 1.1rem;">{{ $review->product->name }}</h4>
                                        <div style="color: #FFD700; font-size: 1rem; letter-spacing: 2px;">
                                            @for($i=0; $i<5; $i++)
                                                {{ $i < $review->rating ? '★' : '☆' }}
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                                    <span style="background: {{ $review->is_active ? '#ecfdf5' : '#fffbeb' }}; color: {{ $review->is_active ? '#10b981' : '#f59e0b' }}; padding: 6px 14px; border-radius: 50px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                                        {{ $review->is_active ? 'Published' : 'Pending Approval' }}
                                    </span>
                                    <span style="font-size: 0.8rem; color: #94a3b8; font-weight: 600;">{{ $review->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>

                            <div style="background: #f8fafc; border-radius: 16px; padding: 20px; position: relative;">
                                <div style="font-size: 2rem; color: #cbd5e1; position: absolute; top: 12px; left: 16px; font-family: serif; line-height: 1;">"</div>
                                <p style="font-size: 1rem; color: #475569; line-height: 1.6; margin: 0; padding-left: 24px; font-style: italic; font-weight: 500;">
                                    {{ $review->comment }}
                                </p>
                            </div>

                            @if(!empty($review->images) || $review->image_path || $review->video_path)
                                <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                                    @php
                                        $histImages = !empty($review->images) ? $review->images : ($review->image_path ? [$review->image_path] : []);
                                    @endphp
                                    @if(count($histImages) > 0)
                                        @foreach($histImages as $hIdx => $img)
                                            <div style="width: 80px; height: 80px; border-radius: 12px; overflow: hidden; border: 2px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); cursor: pointer;"
                                                onclick="openReviewLightbox({{ json_encode($histImages) }}, {{ $hIdx }})">
                                                <img src="{{ asset('storage/' . $img) }}" style="width: 100%; height: 100%; object-fit: cover;" alt="Review photo">
                                            </div>
                                        @endforeach
                                    @endif

                                    @if($review->video_path)
                                        <div style="width: 80px; height: 80px; border-radius: 12px; overflow: hidden; border: 2px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); cursor: pointer; background: #000; position: relative;" onclick="window.open('{{ asset('storage/' . $review->video_path) }}')">
                                            <video src="{{ asset('storage/' . $review->video_path) }}" style="width: 100%; height: 100%; object-fit: cover;"></video>
                                            <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #fff; background: rgba(0,0,0,0.3);">
                                                <iconify-icon icon="solar:play-circle-bold" style="font-size: 1.5rem;"></iconify-icon>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div style="margin-top: 30px;">
                    {{ $userReviews->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ─── REVIEW PHOTO LIGHTBOX ─── --}}
    <div id="reviewLightboxOverlay"
        style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(0,0,0,0.92); align-items:center; justify-content:center; flex-direction:column;"
        onclick="if(event.target===this) closeReviewLightbox()">

        {{-- Close Button --}}
        <button onclick="closeReviewLightbox()"
            style="position:absolute; top:18px; right:24px; background:rgba(255,255,255,0.12); border:none; color:#fff; width:40px; height:40px; border-radius:50%; font-size:20px; cursor:pointer; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px); transition:.2s;"
            onmouseenter="this.style.background='rgba(255,255,255,0.25)'" onmouseleave="this.style.background='rgba(255,255,255,0.12)'">✕</button>

        {{-- Main image area --}}
        <div style="position:relative; max-width:92vw; max-height:75vh; display:flex; align-items:center; justify-content:center;">
            <button id="rlbPrev"
                style="position:absolute; left:-52px; background:rgba(255,255,255,0.12); border:none; color:#fff; width:44px; height:44px; border-radius:50%; font-size:26px; cursor:pointer; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px); transition:.2s; z-index:2;"
                onmouseenter="this.style.background='rgba(255,255,255,0.28)'" onmouseleave="this.style.background='rgba(255,255,255,0.12)'">‹</button>

            <img id="rlbMainImg" src="" alt="Review photo"
                style="max-width:92vw; max-height:75vh; object-fit:contain; border-radius:10px; box-shadow:0 8px 40px rgba(0,0,0,0.6); user-select:none;">

            <button id="rlbNext"
                style="position:absolute; right:-52px; background:rgba(255,255,255,0.12); border:none; color:#fff; width:44px; height:44px; border-radius:50%; font-size:26px; cursor:pointer; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px); transition:.2s; z-index:2;"
                onmouseenter="this.style.background='rgba(255,255,255,0.28)'" onmouseleave="this.style.background='rgba(255,255,255,0.12)'">›</button>
        </div>

        {{-- Counter --}}
        <div id="rlbCounter" style="color:rgba(255,255,255,0.6); font-size:0.85rem; margin-top:12px; letter-spacing:0.5px;"></div>

        {{-- Thumbnails --}}
        <div id="rlbThumbs"
            style="display:flex; gap:8px; margin-top:14px; max-width:90vw; overflow-x:auto; padding:4px 0 8px; scrollbar-width:thin;"></div>
    </div>

    <script>
        // ─── Review Lightbox Logic ───
        let rlbImages = [];
        let rlbIndex = 0;
        let rlbTouchStartX = 0;

        function openReviewLightbox(imagesArray, startIndex = 0) {
            rlbImages = Array.isArray(imagesArray) ? imagesArray : JSON.parse(imagesArray);
            if (!rlbImages.length) return;
            rlbIndex = startIndex;
            document.getElementById('reviewLightboxOverlay').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            rlbRender();
        }

        function closeReviewLightbox() {
            document.getElementById('reviewLightboxOverlay').style.display = 'none';
            document.body.style.overflow = '';
        }

        function rlbRender() {
            const mainImg = document.getElementById('rlbMainImg');
            const thumbs = document.getElementById('rlbThumbs');
            const counter = document.getElementById('rlbCounter');
            const prevBtn = document.getElementById('rlbPrev');
            const nextBtn = document.getElementById('rlbNext');

            mainImg.src = '/storage/' + rlbImages[rlbIndex];
            counter.textContent = `${rlbIndex + 1} / ${rlbImages.length}`;
            prevBtn.style.display = rlbImages.length > 1 ? 'flex' : 'none';
            nextBtn.style.display = rlbImages.length > 1 ? 'flex' : 'none';

            thumbs.innerHTML = '';
            rlbImages.forEach((img, idx) => {
                const t = document.createElement('img');
                t.src = '/storage/' + img;
                t.alt = 'thumbnail';
                t.style.cssText = `width:56px; height:56px; object-fit:cover; border-radius:7px; cursor:pointer; flex-shrink:0; transition:.2s; border:2px solid ${idx === rlbIndex ? '#fff' : 'transparent'}; opacity:${idx === rlbIndex ? '1' : '0.55'};`;
                t.onclick = (e) => { e.stopPropagation(); rlbIndex = idx; rlbRender(); };
                thumbs.appendChild(t);
            });

            // scroll active thumb into view
            const activeThumb = thumbs.children[rlbIndex];
            if (activeThumb) activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }

        document.getElementById('rlbPrev').onclick = (e) => { e.stopPropagation(); rlbIndex = (rlbIndex - 1 + rlbImages.length) % rlbImages.length; rlbRender(); };
        document.getElementById('rlbNext').onclick = (e) => { e.stopPropagation(); rlbIndex = (rlbIndex + 1) % rlbImages.length; rlbRender(); };

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (document.getElementById('reviewLightboxOverlay').style.display !== 'flex') return;
            if (e.key === 'ArrowLeft') { rlbIndex = (rlbIndex - 1 + rlbImages.length) % rlbImages.length; rlbRender(); }
            if (e.key === 'ArrowRight') { rlbIndex = (rlbIndex + 1) % rlbImages.length; rlbRender(); }
            if (e.key === 'Escape') { document.getElementById('reviewLightboxOverlay').style.display = 'none'; document.body.style.overflow = ''; }
        });

        // Touch swipe
        document.getElementById('reviewLightboxOverlay').addEventListener('touchstart', (e) => { rlbTouchStartX = e.touches[0].clientX; }, { passive: true });
        document.getElementById('reviewLightboxOverlay').addEventListener('touchend', (e) => {
            const diff = rlbTouchStartX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 40) {
                if (diff > 0) { rlbIndex = (rlbIndex + 1) % rlbImages.length; }
                else { rlbIndex = (rlbIndex - 1 + rlbImages.length) % rlbImages.length; }
                rlbRender();
            }
        }, { passive: true });

        // Track uploaded files per product and type (image/video)
        const fileStore = {};

        function handleFileSelect(event, productId, type) {
            const input = event.target;
            if (!input.files || input.files.length === 0) return;

            const storeKey = `${productId}_${type}`;
            const dt = fileStore[storeKey] || new DataTransfer();

            // For videos (single file input), clear the existing transfer object
            if (type === 'video') {
                dt.items.clear();
            }

            // Append new files
            for (let i = 0; i < input.files.length; i++) {
                dt.items.add(input.files[i]);
            }

            fileStore[storeKey] = dt;
            input.files = dt.files; // Sync input

            renderPreviews(productId, type);
        }

        function removeFile(productId, type, index) {
            const storeKey = `${productId}_${type}`;
            const dt = fileStore[storeKey];
            if (!dt) return;

            const newDt = new DataTransfer();
            for (let i = 0; i < dt.files.length; i++) {
                if (i !== index) {
                    newDt.items.add(dt.files[i]);
                }
            }

            fileStore[storeKey] = newDt;
            
            // Sync with input element
            const inputId = type === 'image' ? `img_input_${productId}` : `vid_input_${productId}`;
            document.getElementById(inputId).files = newDt.files;

            renderPreviews(productId, type);
        }

        function renderPreviews(productId, type) {
            const storeKey = `${productId}_${type}`;
            const dt = fileStore[storeKey];
            const container = document.getElementById(`previewContainer_${type}_${productId}`);
            container.innerHTML = '';

            if (!dt || dt.files.length === 0) return;

            for (let i = 0; i < dt.files.length; i++) {
                const file = dt.files[i];
                const url = URL.createObjectURL(file);
                
                const box = document.createElement('div');
                box.className = 'preview-box';

                const innerBox = document.createElement('div');
                innerBox.className = 'preview-box-inner';
                
                if (type === 'image' || file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.src = url;
                    innerBox.appendChild(img);
                } else if (type === 'video' || file.type.startsWith('video/')) {
                    const vid = document.createElement('video');
                    vid.src = url;
                    innerBox.appendChild(vid);
                }

                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-btn';
                removeBtn.innerHTML = '✕';
                removeBtn.type = 'button';
                removeBtn.onclick = () => removeFile(productId, type, i);

                box.appendChild(innerBox);
                box.appendChild(removeBtn);
                container.appendChild(box);
            }
        }

        function toggleReviewForm(productId) {
            const form = document.getElementById('reviewForm_' + productId);
            if (form.style.display === 'none') {
                form.style.display = 'block';
                // Reset file store for this form if reopening
                fileStore[`${productId}_image`] = new DataTransfer();
                fileStore[`${productId}_video`] = new DataTransfer();
                document.getElementById(`img_input_${productId}`).files = fileStore[`${productId}_image`].files;
                document.getElementById(`vid_input_${productId}`).files = fileStore[`${productId}_video`].files;
                document.getElementById(`previewContainer_image_${productId}`).innerHTML = '';
                document.getElementById(`previewContainer_video_${productId}`).innerHTML = '';
            } else {
                form.style.display = 'none';
            }
        }

        // Star Rating Interaction
        document.querySelectorAll('.star-opt').forEach(star => {
            const productId = star.parentElement.className.split('_')[1];
            
            star.addEventListener('click', function() {
                const val = this.getAttribute('data-val');
                document.getElementById('ratingValue_' + productId).value = val;
                
                document.querySelectorAll('.star-opt-' + productId).forEach(s => {
                    if(parseInt(s.getAttribute('data-val')) <= parseInt(val)) {
                        s.style.color = '#FFD700';
                        s.style.transform = 'scale(1.1)';
                    } else {
                        s.style.color = '#e2e8f0';
                        s.style.transform = 'scale(1)';
                    }
                });
            });
            
            star.addEventListener('mouseover', function() {
                const val = this.getAttribute('data-val');
                document.querySelectorAll('.star-opt-' + productId).forEach(s => {
                    if(parseInt(s.getAttribute('data-val')) <= parseInt(val)) {
                        s.style.color = '#fde047';
                    } else {
                        s.style.color = '#e2e8f0';
                    }
                });
            });
            
            star.addEventListener('mouseout', function() {
                const val = document.getElementById('ratingValue_' + productId).value;
                document.querySelectorAll('.star-opt-' + productId).forEach(s => {
                    if(parseInt(s.getAttribute('data-val')) <= parseInt(val)) {
                        s.style.color = '#FFD700';
                        s.style.transform = 'scale(1)';
                    } else {
                        s.style.color = '#e2e8f0';
                    }
                });
            });
        });

        // Default set 5 stars
        window.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[id^="ratingValue_"]').forEach(input => {
                const productId = input.id.split('_')[1];
                document.querySelectorAll('.star-opt-' + productId).forEach(s => {
                    s.style.color = '#FFD700';
                });
            });
        });
    </script>
@endsection
