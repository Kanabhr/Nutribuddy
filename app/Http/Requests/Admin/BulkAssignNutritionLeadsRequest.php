<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignNutritionLeadsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lead_ids' => ['required', 'array', 'min:1', 'max:10'],
            'lead_ids.*' => ['required', 'integer', 'distinct', 'exists:nutrition_leads,id'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
