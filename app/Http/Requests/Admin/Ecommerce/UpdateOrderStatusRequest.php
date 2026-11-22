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
        // payment_status is intentionally not accepted here any more (point 33).
        // Payment status changes go through OrderPaymentAdjustmentController: permission, reason, reference, audit trail.
        return [
            'status' => ['required', Rule::in(OrderFlow::ORDER_STATUSES)],
            'fulfillment_status' => ['nullable', Rule::in(OrderFlow::FULFILLMENT_STATUSES)],
            'admin_note' => ['nullable', 'string'],
        ];
    }
}