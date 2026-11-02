<?php
namespace App\Http\Requests\Admin\Support;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->can('manage customers') ?? false; }
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])], 'admin_note' => ['nullable', 'string'],
        ];
    }
}
