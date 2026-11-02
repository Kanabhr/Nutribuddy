<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\UpdateProductReviewStatusRequest;
use App\Models\ProductReview;

class VideoReviewController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceWritesDisabled;
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    public function index()
    {
        $reviews = ProductReview::with(['product', 'user'])
            ->whereNotNull('video_path')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('admin.content.video-reviews.index', compact('reviews'));
    }

    public function update(UpdateProductReviewStatusRequest $request, ProductReview $video_review)
    {
        $video_review->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $video_review->is_active,
            ]);
        }

        return redirect()->back()->with('success', 'Video review status updated successfully.');
    }

    public function destroy(ProductReview $video_review)
    {
        $video_review->delete();

        return redirect()->back()->with('success', 'Video review deleted successfully.');
    }
}
