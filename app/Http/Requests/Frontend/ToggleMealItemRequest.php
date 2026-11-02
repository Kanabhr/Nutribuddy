<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class ToggleMealItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'child_id' => ['required', 'integer'], 'food_id' => ['required', 'integer'],
            'meal_id' => ['nullable', 'integer'], 'log_date' => ['nullable', 'date'],
        ];
    }
}
