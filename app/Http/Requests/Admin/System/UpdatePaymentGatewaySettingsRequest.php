<?php
namespace App\Http\Requests\Admin\System;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdatePaymentGatewaySettingsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->can('manage administration') ?? false; }
    public function rules(): array
    {
        return match ($this->input('gateway')) {
            'razorpay' => ['razorpay_env' => ['required', Rule::in(['sandbox', 'production'])]],
            'cashfree' => ['cashfree_env' => ['required', Rule::in(['sandbox', 'production'])]],
            default => [],
        };
    }
}
