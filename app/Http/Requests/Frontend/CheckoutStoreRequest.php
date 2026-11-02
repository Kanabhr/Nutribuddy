<?php

namespace App\Http\Requests\Frontend;

use App\Services\Checkout\StateCityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(StateCityService $stateCityService): array
    {
        $data = $stateCityService->getStateCityData();
        $stateCodes = array_keys($data);
        $stateCode = $this->input('state');
        $cities = $data[$stateCode]['cities'] ?? [];

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'state' => ['required', 'string', Rule::in($stateCodes)],
            'city' => ['required', 'string', Rule::in($cities)],
            'pincode' => ['required', 'digits:6'],
        ];
    }
}
