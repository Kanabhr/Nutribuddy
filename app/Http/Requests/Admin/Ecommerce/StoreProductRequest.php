<?php

namespace App\Http\Requests\Admin\Ecommerce;

class StoreProductRequest extends ProductMutationRequest
{
    public function rules(): array
    {
        return $this->productRules() + $this->transformSectionRules();
    }
}
