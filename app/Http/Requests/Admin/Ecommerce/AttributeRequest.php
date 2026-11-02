<?php

namespace App\Http\Requests\Admin\Ecommerce;

use App\Models\Attribute;
use Illuminate\Validation\Rule;

class AttributeRequest extends AdminProductResourceRequest
{
    public function rules(): array
    {
        $attribute = $this->route('attribute');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('attributes', 'slug')->ignore($attribute instanceof Attribute ? $attribute->id : null)],
            'values_text' => ['required', 'string'], 'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
