<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Admin\ResourceIndexFallbacks;
use App\Http\Controllers\Admin\ResourceWritesDisabled;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\ReplyProductReviewRequest;
use App\Http\Requests\Admin\Content\UpdateProductReviewStatusRequest;
use App\Models\Order;
use App\Models\ProductReview;

class ReviewController extends Controller
{
    use ResourceIndexFallbacks;
    use ResourceWritesDisabled;

    public function index()
    {
        $reviews = ProductReview::with(['product', 'user'])
            ->whereNull('video_path')
            ->orderBy('created_at', 'desc')
            ->get();

        // One query for the customer popup: order count and spend per reviewer (cancelled orders excluded).
        $customerStats = Order::query()
            ->whereIn('user_id', $reviews->pluck('user_id')->filter()->unique())
            ->where('status', '!=', 'cancelled')
            ->selectRaw('user_id, COUNT(*) as orders_count, COALESCE(SUM(grand_total), 0) as total_spent')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        return view('admin.content.reviews.index', compact('reviews', 'customerStats'));
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

    public function reply(ReplyProductReviewRequest $request, ProductReview $review)
    {
        $reply = trim((string) $request->validated('admin_reply'));
        $hasReply = $reply !== '';

        $review->update([
            'admin_reply' => $hasReply ? $reply : null,
            'admin_replied_at' => $hasReply ? now() : null,
            'admin_replied_by' => $hasReply ? $request->user('admin')->id : null,
        ]);

        $message = $hasReply ? 'Reply saved successfully.' : 'Reply removed.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function destroy(ProductReview $review)
    {
        $review->delete();

        return redirect()->back()->with('success', 'Review deleted successfully.');
    }
}
