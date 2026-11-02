<?php

namespace App\Http\Requests\Admin\Ecommerce;

use App\Models\IngredientCategory;
use Illuminate\Validation\Rule;

class IngredientCategoryRequest extends AdminProductResourceRequest
{
    public function rules(): array
    {
        $category = $this->route('ingredientCategory');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('ingredient_categories', 'slug')->ignore($category instanceof IngredientCategory ? $category->id : null)],
            'description' => ['nullable', 'string'], 'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
