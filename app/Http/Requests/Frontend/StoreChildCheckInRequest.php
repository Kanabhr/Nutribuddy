<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreChildCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'child_id' => ['required', 'integer'], 'weight_kg' => ['required', 'numeric', 'min:1'],
            'height_cm' => ['required', 'numeric', 'min:30'], 'body_fat' => ['nullable', 'numeric'],
            'waist_inches' => ['nullable', 'numeric'], 'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
