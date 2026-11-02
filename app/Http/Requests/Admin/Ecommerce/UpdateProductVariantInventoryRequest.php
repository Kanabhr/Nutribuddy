<?php

namespace App\Http\Requests\Admin\Ecommerce;

class UpdateProductVariantInventoryRequest extends ProductVariantRequest
{
    public function rules(): array
    {
        return [
            'track_stock' => ['nullable', 'boolean'], 'stock_qty' => ['required', 'integer', 'min:0'],
            'reserved_qty' => ['nullable', 'integer', 'min:0'], 'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'is_in_stock' => ['nullable', 'boolean'],
        ];
    }
}
