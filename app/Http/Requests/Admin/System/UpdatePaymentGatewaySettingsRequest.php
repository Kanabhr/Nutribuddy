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
            'cod' => $this->codPricingRules(),
            default => $this->codPricingRules(),
        };
    }

    /** R9/R10: amounts must be numeric and not negative. All optional, so existing posts keep working. */
    private function codPricingRules(): array
    {
        return [
            'cod_fee' => ['nullable', 'numeric', 'min:0'],
            'cod_limit' => ['nullable', 'numeric', 'min:0'],
            'online_switch_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'online_switch_discount_enabled' => ['nullable', 'boolean'],
        ];
    }
}
