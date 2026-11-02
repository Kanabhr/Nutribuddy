<?php

namespace App\Http\Requests\Admin\Ecommerce;

use App\Models\ProductVariant;

class UpdateProductVariantRequest extends ProductVariantRequest
{
    public function rules(): array
    {
        $variant = $this->route('variant');

        return $this->variantRules($variant instanceof ProductVariant ? $variant : null) + [
            'stock_qty' => ['nullable', 'integer', 'min:0'], 'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'track_stock' => ['nullable', 'boolean'], 'is_in_stock' => ['nullable', 'boolean'],
        ];
    }
}
