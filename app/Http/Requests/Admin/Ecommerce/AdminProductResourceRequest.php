<?php

namespace App\Http\Requests\Admin\Ecommerce;

use Illuminate\Foundation\Http\FormRequest;

abstract class AdminProductResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage products') ?? false;
    }
}
