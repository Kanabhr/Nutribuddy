<?php
namespace App\Http\Requests\Admin\Access;
use Illuminate\Foundation\Http\FormRequest;
class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['email' => ['required', 'email'], 'password' => ['required']]; }
}
