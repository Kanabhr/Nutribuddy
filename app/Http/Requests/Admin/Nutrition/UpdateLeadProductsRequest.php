<?php

namespace App\Http\Requests\Admin\Nutrition;

class UpdateLeadProductsRequest extends ReviewLeadDietPlanRequest
{
    public function rules(): array
    {
        return [
            'supplement_product_ids' => ['nullable', 'array'],
            'supplement_product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }
}
