<?php

namespace App\Http\Requests\Admin\Ecommerce;

use App\Models\TaxRate;
use Illuminate\Validation\Rule;

class TaxRateRequest extends AdminProductResourceRequest
{
    public function rules(): array
    {
        $taxRate = $this->route('taxRate');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('tax_rates', 'code')->ignore($taxRate instanceof TaxRate ? $taxRate->id : null)],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'], 'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'], 'is_active' => ['nullable', 'boolean'],
            'show_in_checkout' => ['nullable', 'boolean'],
        ];
    }
}
