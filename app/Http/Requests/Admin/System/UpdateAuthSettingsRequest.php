<?php
namespace App\Http\Requests\Admin\System;
use Illuminate\Foundation\Http\FormRequest;
class UpdateAuthSettingsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->can('manage administration') ?? false; }
    public function rules(): array
    {
        return [
            'google_client_id' => ['nullable', 'string', 'max:255'],
            'google_client_secret' => ['nullable', 'string', 'max:255'],
        ];
    }
}
