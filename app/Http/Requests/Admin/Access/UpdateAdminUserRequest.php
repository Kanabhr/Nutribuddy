<?php

namespace App\Http\Requests\Admin\Access;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage users') ?? false;
    }

    public function rules(): array
    {
        $admin = $this->route('admin');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin instanceof User ? $admin->id : null)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'admin')],
            'status' => ['required', 'in:1,0'],
        ];
    }
}
