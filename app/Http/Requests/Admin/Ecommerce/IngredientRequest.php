<?php

namespace App\Http\Requests\Admin\Ecommerce;

use Illuminate\Foundation\Http\FormRequest;

class IngredientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage products') ?? false;
    }

    public function rules(): array
    {
        return [
            'ingredient_category_id' => ['nullable', 'exists:ingredient_categories,id'],
            'main_heading' => ['required', 'string', 'max:255'], 'short_heading' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'], 'dosage_heading_one' => ['nullable', 'string', 'max:255'],
            'dosage_heading_two' => ['nullable', 'string', 'max:255'], 'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'], 'is_featured' => ['nullable', 'boolean'],
            'icon' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'benefits' => ['nullable', 'array'], 'benefits.*' => ['nullable', 'string', 'max:255'],
            'products' => ['nullable', 'array'], 'products.*' => ['exists:products,id'],
        ];
    }
}
