<?php
namespace App\Http\Requests\Admin\System;
use Illuminate\Foundation\Http\FormRequest;
class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->can('manage administration') ?? false; }
        return [
            'site_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'website_url' => ['nullable', 'string', 'max:255'],
            'gst_number' => ['nullable', 'string', 'max:100'],
            'pan_number' => ['nullable', 'string', 'max:50'],
            'cin_number' => ['nullable', 'string', 'max:50'],
            'store_state' => ['nullable', 'string', 'max:100'],
        ];
}
