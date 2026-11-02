<?php
namespace App\Http\Requests\Admin\Ecommerce;
use App\Support\OrderFlow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->can('manage orders') ?? false; }
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(OrderFlow::ORDER_STATUSES)],
            'fulfillment_status' => ['nullable', Rule::in(OrderFlow::FULFILLMENT_STATUSES)],
            'payment_status' => ['nullable', Rule::in(OrderFlow::PAYMENT_STATUSES)],
            'admin_note' => ['nullable', 'string'],
        ];
    }
}
