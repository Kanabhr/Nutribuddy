<?php
namespace App\Http\Requests\Admin\System;
use Illuminate\Foundation\Http\FormRequest;
class UpdateSideSectionRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->can('manage administration') ?? false; }
    public function rules(): array
    {
        return [
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', 'contact_number' => 'required|string',
            'email' => 'required|email', 'address' => 'nullable|string', 'description' => 'nullable|string',
            'social_platform' => 'nullable|array', 'social_url' => 'nullable|array',
        ];
    }
}
