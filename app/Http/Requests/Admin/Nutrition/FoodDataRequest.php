<?php

namespace App\Http\Requests\Admin\Nutrition;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FoodDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('view diet chart')
            || $this->user('admin')?->can('manage diet chart');
    }

    public function rules(): array
    {
        return [
            'draw' => ['nullable', 'integer', 'min:0'],
            'start' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'integer', 'min:1', 'max:100'],
            'order.0.column' => ['nullable', 'integer', 'between:0,8'],
            'order.0.dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'search.value' => ['nullable', 'string', 'max:255'],
            'food_group' => ['nullable', 'string', 'max:100'],
            'slot' => ['nullable', 'string', 'max:100'],
            'diet_type' => ['nullable', 'string', 'max:100'],
            'is_veg' => ['nullable', 'string', 'max:20'],
            'region' => ['nullable', 'string', 'max:100'],
        ];
    }
}
