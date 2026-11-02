<?php
namespace App\Http\Requests\Admin\Marketing;
use Illuminate\Foundation\Http\FormRequest;
class UpdateLoyaltySettingsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->can('manage loyalty') ?? false; }
    public function rules(): array
    {
        return [
            'loyalty_conversion_rate' => 'required|integer|min:1',
            'loyalty_discount_percentage' => 'required|integer|min:0|max:100',
            'loyalty_enabled' => 'required|boolean',
        ];
    }
}
