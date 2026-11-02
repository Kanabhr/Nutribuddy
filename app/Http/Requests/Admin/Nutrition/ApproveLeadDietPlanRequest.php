<?php

namespace App\Http\Requests\Admin\Nutrition;

class ApproveLeadDietPlanRequest extends ReviewLeadDietPlanRequest
{
    public function rules(): array
    {
        return ['review_notes' => ['nullable', 'string', 'max:2000']];
    }
}
