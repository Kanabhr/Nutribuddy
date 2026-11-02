<?php

namespace App\Http\Requests\Frontend;

use App\Models\CustomerAddress;
use Illuminate\Foundation\Http\FormRequest;

class SendCodOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'address_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $exists = CustomerAddress::where('id', $value)
                        ->where('user_id', auth()->id())
                        ->exists();

                    if (! $exists) {
                        $fail('The selected delivery address is invalid.');
                    }
                },
            ],
        ];
    }
}

