<?php

namespace App\Http\Requests\Admin\Nutrition;

use Illuminate\Foundation\Http\FormRequest;

class FoodBulkUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage diet chart') ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ];
    }
}
