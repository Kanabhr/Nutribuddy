<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignNutritionLeadRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['assigned_to' => ['required', 'integer', 'exists:users,id']]; }
}
