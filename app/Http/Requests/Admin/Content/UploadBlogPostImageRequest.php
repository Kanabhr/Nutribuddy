<?php

namespace App\Http\Requests\Admin\Content;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UploadBlogPostImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage content') ?? false;
    }

    public function rules(): array
    {
        return ['upload' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'uploaded' => 0,
            'error' => ['message' => 'No image uploaded'],
        ], 422));
    }
}
