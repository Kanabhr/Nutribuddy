<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\UpdateProductReviewStatusRequest;
use App\Models\ProductReview;

class ReviewController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceWritesDisabled;
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    public function index()
    {
        $reviews = ProductReview::with(['product', 'user'])
            ->whereNull('video_path')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('admin.content.reviews.index', compact('reviews'));
    }

    public function update(UpdateProductReviewStatusRequest $request, ProductReview $review)
    {
        $review->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $review->is_active,
            ]);
        }

        return redirect()->back()->with('success', 'Review status updated successfully.');
    }

    public function destroy(ProductReview $review)
    {
        $review->delete();

        return redirect()->back()->with('success', 'Review deleted successfully.');
    }
}
