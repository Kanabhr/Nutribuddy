<?php
namespace App\Http\Requests\Admin\Support;
use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
class ReplySupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('supportTicket') ?? $this->route('support_ticket');
        return ($this->user('admin')?->can('manage customers') ?? false)
            && $ticket instanceof SupportTicket;
    }
    public function rules(): array { return ['message' => ['required', 'string']]; }
}
