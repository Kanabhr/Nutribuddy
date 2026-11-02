<?php
namespace App\Http\Requests\Frontend\Account;
use Illuminate\Foundation\Http\FormRequest;
class SendOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['phone' => 'required|numeric|digits:10']; }
}
