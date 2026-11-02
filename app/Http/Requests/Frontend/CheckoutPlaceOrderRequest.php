<?php

namespace App\Http\Requests\Frontend;

use App\Services\Checkout\CodOtpService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutPlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'address_id' => ['required', 'integer', 'exists:customer_addresses,id'],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'coins_to_redeem' => ['nullable', 'integer', 'min:0'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['required', 'in:cashfree,razorpay,cod'],
            'checkout_token' => ['nullable', 'string', 'max:120'],
            'cod_otp' => [
                Rule::requiredIf(fn () => $this->input('payment_method') === 'cod' && app(CodOtpService::class)->isEnabled()),
                'nullable',
                'string',
                'max:10',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cod_otp.required' => 'Please enter the 6-digit verification code sent to your phone.',
        ];
    }
}
