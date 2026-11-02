<?php

namespace App\Http\Requests\Admin\Nutrition;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage diet chart') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:subscription_plans,slug'],
            'price' => ['required', 'numeric', 'min:0'],
            'level' => ['required', Rule::in(['basic', 'plus', 'pro', 'premium'])],
            'monthly_price' => ['nullable', 'numeric', 'min:0'],
            'quarterly_price' => ['nullable', 'numeric', 'min:0'],
            'annual_price' => ['nullable', 'numeric', 'min:0'],
            'features_text' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
