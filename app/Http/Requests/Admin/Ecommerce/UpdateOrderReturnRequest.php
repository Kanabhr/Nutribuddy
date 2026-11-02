<?php
namespace App\Http\Requests\Admin\Ecommerce;
use App\Support\OrderFlow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateOrderReturnRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->can('manage orders') ?? false; }
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(OrderFlow::RETURN_STATUSES)],
            'refund_amount' => ['required', 'numeric', 'min:0'],
            'admin_note' => ['nullable', 'string'],
        ];
    }
}
