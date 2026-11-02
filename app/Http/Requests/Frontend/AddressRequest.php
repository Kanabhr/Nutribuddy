<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

abstract class AddressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:50'], 'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:15'], 'email' => ['nullable', 'email', 'max:255'],
            'address_line_1' => ['required', 'string', 'max:255'], 'address_line_2' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'], 'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'], 'postal_code' => ['required', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'max:100'],
        ];
    }
}
