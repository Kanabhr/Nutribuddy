<?php

namespace App\Http\Requests\Frontend\Account;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');
        return $order instanceof Order && (int) $order->user_id === (int) $this->user()?->id;
    }
}
