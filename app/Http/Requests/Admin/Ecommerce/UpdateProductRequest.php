<?php

namespace App\Http\Requests\Admin\Ecommerce;

use App\Models\Product;

class UpdateProductRequest extends ProductMutationRequest
{
    public function rules(): array
    {
        $product = $this->route('product');

        return $this->productRules($product instanceof Product ? $product : null)
            + $this->problemSolutionRules()
            + $this->transformSectionRules();
    }
}
