@php
    $reviewState = $reviewState ?? 'guest';
@endphp

<section id="leave-review" class="pdp-review-box-wrap">
    <style>
        .pdp-review-box-wrap { padding: 32px 5% 48px; }
        .pdp-review-box { max-width: 860px; margin: 0 auto; background: #fff; border: 1px solid #f3dce8; border-radius: 20px; padding: 28px; box-shadow: 0 8px 30px rgba(0, 0, 0, .05); }
        .pdp-review-box h3 { font-size: 1.3rem; font-weight: 800; margin: 0 0 6px; }
        .pdp-review-box p.sub { color: #6b7280; font-size: .92rem; margin: 0 0 18px; }
        .pdp-review-stars { display: flex; gap: 6px; margin-bottom: 14px; }
        .pdp-review-stars span { font-size: 2rem; cursor: pointer; color: #d1d5db; line-height: 1; }
        .pdp-review-stars span.on { color: #fbbf24; }
        .pdp-review-box textarea { width: 100%; border: 1px solid #e5e7eb; border-radius: 14px; padding: 12px 14px; font-size: .95rem; resize: vertical; }
        .pdp-review-files { display: flex; gap: 12px; flex-wrap: wrap; margin: 14px 0; }
        .pdp-review-files label { border: 1px dashed #c4b5fd; border-radius: 12px; padding: 10px 16px; font-weight: 700; font-size: .85rem; cursor: pointer; }
        .pdp-review-files input { display: none; }
        .pdp-review-chosen { font-size: .8rem; color: #6b7280; margin-bottom: 12px; }
        .pdp-review-submit { background: linear-gradient(90deg, #ec4899, #7c3aed); color: #fff; border: 0; border-radius: 14px; padding: 12px 28px; font-weight: 800; cursor: pointer; }
        .pdp-review-note { background: #fff7ed; border-radius: 12px; padding: 14px 16px; font-size: .92rem; }
        .pdp-review-errors { background: #fef2f2; color: #b91c1c; border-radius: 12px; padding: 10px 16px; margin-bottom: 14px; font-size: .88rem; }
    </style>

    <div class="pdp-review-box">
        <h3>Leave a review</h3>

        @if ($errors->any())
            <div class="pdp-review-errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if ($reviewState === 'guest')
            <p class="sub">Please log in to review this product.</p>
            <a class="pdp-review-submit" style="text-decoration:none;display:inline-block;" href="{{ route('frontend.login') }}">Log in</a>
        @elseif ($reviewState === 'not_purchased')
            <div class="pdp-review-note">Only customers who have received this product can review it. Once your order is delivered, you can review it here.</div>
        @elseif ($reviewState === 'already_reviewed')
            <div class="pdp-review-note">
                Thank you, you have already reviewed this product.
                @if ($userReview && ! $userReview->is_active)
                    Your review is awaiting approval.
                @endif
            </div>
        @else
            <p class="sub">Tell other parents what you thought. Your review will appear after approval.</p>
            <form action="{{ route('reviews.store', $product->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="pdp-review-stars" id="pdpReviewStars" aria-label="Your rating">
                    @for ($i = 1; $i <= 5; $i++)
                        <span data-val="{{ $i }}" class="{{ $i <= (int) old('rating', 5) ? 'on' : '' }}">★</span>
                    @endfor
                </div>
                <input type="hidden" name="rating" id="pdpReviewRating" value="{{ (int) old('rating', 5) }}">

                <textarea name="comment" rows="4" maxlength="1000" placeholder="What did you like about it?" required>{{ old('comment') }}</textarea>

                <div class="pdp-review-files">
                    <label>+ Photos <input type="file" name="review_images[]" multiple accept="image/*" id="pdpReviewImages"></label>
                    <label>+ Video <input type="file" name="review_video" accept="video/*" id="pdpReviewVideo"></label>
                </div>
                <div class="pdp-review-chosen" id="pdpReviewChosen"></div>

                <button type="submit" class="pdp-review-submit">Post review</button>
            </form>

            <script>
                (function () {
                    var stars = document.querySelectorAll('#pdpReviewStars span');
                    var input = document.getElementById('pdpReviewRating');
                    stars.forEach(function (star) {
                        star.addEventListener('click', function () {
                            var val = parseInt(star.dataset.val, 10);
                            input.value = val;
                            stars.forEach(function (s) { s.classList.toggle('on', parseInt(s.dataset.val, 10) <= val); });
                        });
                    });
                    var chosen = document.getElementById('pdpReviewChosen');
                    function showChosen() {
                        var parts = [];
                        var imgs = document.getElementById('pdpReviewImages').files.length;
                        var vid = document.getElementById('pdpReviewVideo').files.length;
                        if (imgs) parts.push(imgs + ' photo(s)');
                        if (vid) parts.push('1 video');
                        chosen.textContent = parts.length ? 'Selected: ' + parts.join(', ') : '';
                    }
                    document.getElementById('pdpReviewImages').addEventListener('change', showChosen);
                    document.getElementById('pdpReviewVideo').addEventListener('change', showChosen);
                })();
            </script>
        @endif
    </div>
</section>