<?php

namespace App\Http\Requests\Admin\Marketing;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage promotions') ?? false;
    }

    public function rules(): array
    {
        $coupon = $this->route('coupon');

        return [
            'user_id' => ['nullable', 'exists:users,id'],
            'code' => ['required', 'string', 'max:255', Rule::unique('coupons', 'code')->ignore($coupon instanceof Coupon ? $coupon->id : null)],
            'name' => ['nullable', 'string', 'max:255'], 'discount_type' => ['required', Rule::in(['percentage', 'flat'])],
            'discount_value' => ['required', 'numeric', 'min:0'], 'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'], 'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'], 'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'], 'is_active' => ['nullable', 'boolean'],
        ];
    }
}
