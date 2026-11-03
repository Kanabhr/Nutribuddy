<?php

namespace App\Services\Content;

use App\Models\BlogPost;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/** Keeps blog post persistence and featured image lifecycle in sync. */
class BlogPostManagementService
{
    /** @param array<string, mixed> $data */
    public function create(array $data, ?UploadedFile $featuredImage = null): BlogPost
    {
        $data = $this->prepareAttributes($data);
        $imagePath = $featuredImage?->store('blog-posts', 'public');
        if ($imagePath) {
            $data['featured_image'] = $imagePath;
        }

        try {
            return DB::transaction(fn (): BlogPost => BlogPost::create($data));
        } catch (Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            throw $exception;
        }
    }

    /** @param array<string, mixed> $data */
    public function update(BlogPost $post, array $data, ?UploadedFile $featuredImage = null): void
    {
        $data = $this->prepareAttributes($data, $post);
        $newImagePath = $featuredImage?->store('blog-posts', 'public');
        $previousImagePath = $post->featured_image;
        if ($newImagePath) {
            $data['featured_image'] = $newImagePath;
        } else {
            unset($data['featured_image']);
        }

        try {
            DB::transaction(function () use ($post, $data): void {
                BlogPost::query()->whereKey($post->id)->lockForUpdate()->firstOrFail()->update($data);
            });
        } catch (Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }
            throw $exception;
        }

        if ($newImagePath && $previousImagePath) {
            Storage::disk('public')->delete($previousImagePath);
        }
    }

    public function forceDelete(BlogPost $post): void
    {
        $imagePath = $post->featured_image;
        DB::transaction(fn () => $post->forceDelete());

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }
    }

    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    private function prepareAttributes(array $data, ?BlogPost $post = null): array
    {
        $baseSlug = !empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['title'] ?? 'post');
        $slug = $baseSlug;
        $counter = 1;
        while (BlogPost::withTrashed()
            ->where('slug', $slug)
            ->when($post, fn ($q) => $q->where('id', '!=', $post->id))
            ->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }
        $data['slug'] = $slug;

        if (empty($data['author_id'])) {
            $data['author_id'] = auth('admin')->id() ?? auth()->id();
        }

        if (($data['status'] ?? '') === 'published' && empty($data['published_at']) && ! $post?->published_at) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
