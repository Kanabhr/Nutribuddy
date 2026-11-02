<?php

namespace App\Http\Requests\Frontend;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;

class ReplySupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $this->user() !== null && $ticket instanceof SupportTicket
            && (int) $ticket->user_id === (int) $this->user()->id;
    }

    public function rules(): array
    {
        return ['message' => ['required', 'string']];
    }
}
