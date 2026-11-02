<?php
namespace App\Http\Requests\Admin\Marketing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateNewsletterSubscriberRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->can('manage promotions') ?? false; }
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'], 'source' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['subscribed', 'unsubscribed'])],
        ];
    }
}
