<?php

namespace App\Http\Requests\Admin\Content;

use Illuminate\Foundation\Http\FormRequest;

class ReplyProductReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage moderation') ?? false;
    }

    public function rules(): array
    {
        // Empty value removes an existing reply.
        return ['admin_reply' => ['nullable', 'string', 'max:2000']];
    }
}
