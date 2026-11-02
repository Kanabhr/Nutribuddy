<?php

namespace App\Http\Requests\Frontend;

class StoreAddressRequest extends AddressRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }
}
