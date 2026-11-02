<?php

namespace App\Http\Requests\Frontend;

use App\Models\CustomerAddress;

class UpdateAddressRequest extends AddressRequest
{
    public function authorize(): bool
    {
        $address = $this->route('address');

        return $this->user() !== null && $address instanceof CustomerAddress
            && (int) $address->user_id === (int) $this->user()->id;
    }
}
