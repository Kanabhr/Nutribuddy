<?php
namespace App\Http\Requests\Frontend\Account;
use Illuminate\Foundation\Http\FormRequest;
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255', 'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20', 'dob' => 'nullable|date', 'gender' => 'nullable|string',
            'bio' => 'nullable|string|max:1000', 'avatar' => 'nullable|image|max:5120',
        ];
    }
}
