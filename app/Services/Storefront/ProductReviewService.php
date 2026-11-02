<?php

namespace App\Services\Storefront;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Stores review media and its pending moderation record as one workflow. */
class ProductReviewService
{
    /** @param array<string, mixed> $data
     *  @param array<int, UploadedFile> $images
     */
    public function create(Product $product, int $userId, array $data, array $images = [], ?UploadedFile $video = null): ProductReview
    {
        $storedPaths = [];
        $imagePaths = [];

        try {
            foreach ($images as $image) {
                $imagePaths[] = $image->store('reviews', 'public');
                $storedPaths[] = end($imagePaths);
            }

            $videoPath = $video?->store('reviews/videos', 'public');
            if ($videoPath) {
                $storedPaths[] = $videoPath;
            }

            return DB::transaction(fn (): ProductReview => ProductReview::create([
                'product_id' => $product->id,
                'user_id' => $userId,
                'rating' => $data['rating'],
                'comment' => $data['comment'],
                'image_path' => $imagePaths[0] ?? null,
                'images' => $imagePaths,
                'video_path' => $videoPath,
                'is_active' => false,
            ]));
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        }
    }
}
