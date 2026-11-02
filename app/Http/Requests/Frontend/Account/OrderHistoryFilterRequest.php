<?php

namespace App\Http\Requests\Frontend\Account;

use Illuminate\Foundation\Http\FormRequest;

class OrderHistoryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
