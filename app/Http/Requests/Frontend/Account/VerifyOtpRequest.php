<?php
namespace App\Http\Requests\Frontend\Account;
use Illuminate\Foundation\Http\FormRequest;
class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['phone' => 'required|numeric|digits:10', 'otp' => 'required|numeric|digits:6'];
    }
}
