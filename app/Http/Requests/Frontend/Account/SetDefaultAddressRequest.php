<?php

namespace App\Http\Requests\Frontend\Account;

use App\Models\CustomerAddress;
use Illuminate\Foundation\Http\FormRequest;

class SetDefaultAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $address = $this->route('address');
        return $address instanceof CustomerAddress
            && (int) $address->user_id === (int) $this->user()?->id;
    }
}
