<?php

namespace App\Http\Requests\Admin\Ecommerce;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderPaymentAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Same trust level as editing payment gateway settings. Change here and on the route to use another permission.
        return $this->user('admin')?->can('manage administration') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['mark_paid', 'refund', 'partial_refund'])],
            'amount' => ['nullable', 'required_if:type,partial_refund', 'numeric', 'min:0.01', 'max:9999999'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'reference' => ['required', 'string', 'min:3', 'max:100'],
            'confirm' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.min' => 'Please explain the reason in at least 10 characters.',
            'confirm.accepted' => 'Please confirm that the money has really been received or returned.',
        ];
    }
}