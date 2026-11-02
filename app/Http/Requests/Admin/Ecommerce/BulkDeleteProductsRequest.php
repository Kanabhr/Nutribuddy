<?php

namespace App\Http\Requests\Admin\Ecommerce;

class BulkDeleteProductsRequest extends ProductMutationRequest
{
    public function rules(): array
    {
        return ['product_ids' => ['required', 'array', 'min:1'], 'product_ids.*' => ['integer']];
    }
}
