<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class CreateChildProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'age' => ['required', 'integer'],
            'sex' => ['required', 'string', 'in:boy,girl,other'],
            'height' => ['required', 'numeric', 'min:50', 'max:220'],
            'weight' => ['required', 'numeric', 'min:5', 'max:150'],
            'region' => ['required', 'string'],
            'diet' => ['required', 'string'],
            'picky' => ['required', 'string'],
            'allergies' => ['nullable', 'string'],
            'activity' => ['nullable', 'string'],
        ];
    }
}
