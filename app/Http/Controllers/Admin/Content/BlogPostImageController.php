<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\UploadBlogPostImageRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPostImageController extends Controller
{
    public function upload(UploadBlogPostImageRequest $request)
    {
        if ($request->hasFile('upload')) {
            $extension = $request->file('upload')->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . strtolower($extension);
            $path = $request->file('upload')->storeAs('blog-images', $fileName, 'public');

            abort_unless($path, 500, 'The uploaded image could not be saved.');

            return response()->json([
                'uploaded' => 1,
                'fileName' => $fileName,
                'url' => Storage::disk('public')->url($path),
            ]);
        }

        return response()->json([
            'uploaded' => 0,
            'error' => ['message' => 'No image uploaded'],
        ]);
    }
}
