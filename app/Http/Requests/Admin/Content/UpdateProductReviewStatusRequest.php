<?php

namespace App\Http\Requests\Admin\Content;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductReviewStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage moderation') ?? false;
    }

    public function rules(): array
    {
        return ['is_active' => ['sometimes', 'boolean']];
    }
}
