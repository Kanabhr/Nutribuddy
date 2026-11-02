<?php

namespace App\Http\Requests\Admin\CRM;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage promotions') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['new', 'in_progress', 'closed'])],
            'assigned_to' => ['nullable', 'exists:users,id'], 'admin_note' => ['nullable', 'string'],
        ];
    }
}
