<?php

namespace App\Http\Requests\Frontend;

use App\Models\DietSubscription;
use Illuminate\Foundation\Http\FormRequest;

class VerifyDietPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && DietSubscription::whereKey($this->input('subscription_id'))
            ->where('user_id', $this->user()->id)->exists();
    }

    public function rules(): array
    {
        return [
            'subscription_id' => ['required', 'exists:diet_subscriptions,id'],
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ];
    }
}
