<?php

namespace App\Http\Requests\Admin\Ecommerce;

use App\Models\Category;
use Illuminate\Validation\Rule;

class CategoryRequest extends AdminProductResourceRequest
{
    public function rules(): array
    {
        $category = $this->route('category');
        $id = $category instanceof Category ? $category->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($id)],
            'parent_id' => $id
                ? ['nullable', 'exists:categories,id', 'different:id']
                : ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'], 'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'], 'meta_keywords' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'], 'is_active' => ['nullable', 'boolean'],
        ];
    }
}
