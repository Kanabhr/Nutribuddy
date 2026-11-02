<?php

namespace App\Http\Requests\Admin\Ecommerce;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage products') ?? false;
    }

    protected function variantRules(?ProductVariant $variant = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($variant?->id)],
            'attributes' => ['nullable', 'json'], 'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'], 'cost_price' => ['nullable', 'numeric', 'min:0'],
            'position' => ['nullable', 'integer', 'min:0'], 'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
