<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z\s.\'-]+$/'],
            'last_name' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z\s.\'-]+$/'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'subject' => ['required', 'string', 'max:255', Rule::in([
                'Where is my order?', 'Question about a product', 'Help with Diet Chart',
                'Wholesale / Partnership', 'Other',
            ])],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.regex' => 'First name can only contain letters, spaces, dots, apostrophes, and hyphens.',
            'last_name.regex' => 'Last name can only contain letters, spaces, dots, apostrophes, and hyphens.',
            'phone.regex' => 'Please enter a valid phone number.',
            'message.min' => 'Please enter at least 10 characters in your message.',
        ];
    }
}
