<?php

namespace App\Http\Controllers\Frontend\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreProductReviewRequest;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\Storefront\ProductReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    public function store(StoreProductReviewRequest $request, Product $product, ProductReviewService $reviews)
    {
        $validated = $request->validated();
        $reviews->create(
            $product,
            (int) Auth::id(),
            $validated,
            $request->file('review_images', []),
            $request->file('review_video'),
        );

        return back()->with('success', 'Your review has been submitted and is awaiting approval.');
    }

    public function userIndex(Request $request)
    {
        $userId = Auth::id();

        $purchasedProducts = OrderItem::whereHas('order', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->whereIn('status', ['delivered', 'completed']);
        })
            ->with(['product' => function ($query) {
                $query->with(['primaryImage', 'reviews' => function ($reviewQuery) {
                    $reviewQuery->where('user_id', Auth::id());
                }]);
            }])
            ->get()
            ->unique('product_id')
            ->pluck('product');

        $userReviews = ProductReview::where('user_id', $userId)
            ->with('product.primaryImage')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('frontend.account.reviews', compact('purchasedProducts', 'userReviews'));
    }
}
