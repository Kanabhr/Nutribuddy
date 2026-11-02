<?php
namespace App\Http\Requests\Admin\Support;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->can('manage customers') ?? false; }
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'exists:users,id'], 'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])], 'admin_note' => ['nullable', 'string'],
        ];
    }
}
