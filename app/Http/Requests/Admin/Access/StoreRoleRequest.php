<?php

namespace App\Http\Requests\Admin\Access;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage roles') ?? false;
    }

    public function rules(): array
    {
        $modules = ['dashboard', 'analytics', 'diet chart', 'products', 'orders', 'customers', 'promotions', 'loyalty', 'moderation', 'administration', 'content', 'assessment', 'users', 'roles'];

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->where('guard_name', 'admin')],
            'permissions' => ['sometimes', 'array:' . implode(',', $modules)],
            'permissions.*' => ['array:view,manage'],
            'permissions.*.view' => ['sometimes', Rule::in(['1'])],
            'permissions.*.manage' => ['sometimes', Rule::in(['1'])],
        ];
    }
}
