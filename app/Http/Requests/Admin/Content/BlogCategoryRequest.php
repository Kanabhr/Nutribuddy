<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\BlogCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlogCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage content') ?? false;
    }

    public function rules(): array
    {
        $category = $this->route('blogCategory');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('blog_categories', 'slug')->ignore($category instanceof BlogCategory ? $category->id : null)],
            'description' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean'],
        ];
    }
}
