<?php

namespace App\Http\Requests\Admin\Ecommerce;

class StoreProductVariantRequest extends ProductVariantRequest
{
    public function rules(): array
    {
        return $this->variantRules() + [
            'product_id' => ['required', 'exists:products,id'], 'track_stock' => ['nullable', 'boolean'],
            'stock_qty' => ['nullable', 'integer', 'min:0'], 'reserved_qty' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'], 'is_in_stock' => ['nullable', 'boolean'],
        ];
    }
}
