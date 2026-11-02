<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\BlogPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage content') ?? false;
    }

    public function rules(): array
    {
        $post = $this->route('blog_post') ?? $this->route('blogPost');
        $postId = $post instanceof BlogPost ? $post->id : (is_numeric($post) ? (int) $post : null);

        return [
            'blog_category_id' => ['nullable', 'exists:blog_categories,id'],
            'author_id' => ['nullable', 'exists:users,id'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('blog_posts', 'slug')->ignore($postId)],
            'short_description' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $words = count(preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY));
                    if ($words > 100) {
                        $fail('The short description must not exceed 100 words (currently ' . $words . ' words).');
                    }
                },
            ],
            'content' => ['required', 'string'],
            'featured_image' => ['nullable', 'image', 'max:2048'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
            'tags' => ['nullable', 'string', 'max:500'],
        ];
    }
}
