<?php

namespace App\Http\Requests\Admin\Content;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteBlogPostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage content') ?? false;
    }

    public function rules(): array
    {
        return ['post_ids' => ['required', 'array', 'min:1'], 'post_ids.*' => ['integer']];
    }
}
