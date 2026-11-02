<?php

namespace App\Http\Requests\Frontend;

use App\Models\Nutrition\Child;
use Illuminate\Foundation\Http\FormRequest;

class CreateDietPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && Child::whereKey($this->input('child_id'))
            ->where('user_id', $this->user()->id)->exists();
    }

    public function rules(): array
    {
        return [
            'plan_slug' => ['required', 'string', 'exists:subscription_plans,slug'],
            'gateway' => ['required', 'string', 'in:razorpay,cashfree'],
            'cycle' => ['required', 'string', 'in:monthly,quarterly,annually'],
            'child_id' => ['required', 'exists:children,id'],
        ];
    }
}
