<?php

namespace App\Http\Requests\Admin\Nutrition;

use Illuminate\Foundation\Http\FormRequest;

abstract class ReviewLeadDietPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }
}
