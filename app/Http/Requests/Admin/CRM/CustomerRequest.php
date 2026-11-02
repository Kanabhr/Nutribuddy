<?php

namespace App\Http\Requests\Admin\CRM;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage customers') ?? false;
    }

    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($customer instanceof User ? $customer->id : null)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => [$customer instanceof User ? 'nullable' : 'required', 'string', 'min:8'],
            'is_active' => ['boolean'],
        ];
    }
}
