<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'coins_to_redeem' => ['nullable', 'integer', 'min:0'],
            'address_id' => ['nullable', 'integer'],
            'shipping_state' => ['nullable', 'string', 'max:100'],
        ];
    }
}
