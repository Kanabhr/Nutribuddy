<?php

namespace App\Http\Requests\Admin\Nutrition;

class UpdateLeadDietPlanRequest extends ReviewLeadDietPlanRequest
{
    public function rules(): array
    {
        return [
            'foods' => ['required', 'array'], 'foods.*' => ['integer', 'exists:foods,id'],
            'supplement_product_ids' => ['nullable', 'array'], 'supplement_product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }
}
