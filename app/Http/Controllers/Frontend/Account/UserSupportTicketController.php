<?php

namespace App\Http\Controllers\Frontend\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\ReplySupportTicketRequest;
use App\Http\Requests\Frontend\StoreSupportTicketRequest;
use App\Models\SupportTicket;
use Illuminate\Support\Str;

class UserSupportTicketController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::where('user_id', auth()->id())->latest()->get();

        return view('frontend.account.support-tickets.index', compact('tickets'));
    }

    public function store(StoreSupportTicketRequest $request)
    {
        $data = $request->validated();
        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-' . strtoupper(Str::random(8)),
            'user_id' => auth()->id(),
            'subject' => $data['subject'],
            'priority' => $data['priority'],
            'message' => $data['message'],
            'status' => 'open',
        ]);

        $ticket->messages()->create([
            'user_id' => auth()->id(),
            'is_admin' => false,
            'message' => $data['message'],
        ]);

        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\NewSupportTicketNotification($ticket));
        }

        return redirect()->back()->with('success', 'Support ticket created successfully!');
    }

    public function show(SupportTicket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $ticket->load('messages.user');

        return view('frontend.account.support-tickets.show', compact('ticket'));
    }

    public function reply(ReplySupportTicketRequest $request, SupportTicket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $message = $request->validated('message');

        $ticket->messages()->create([
            'user_id' => auth()->id(),
            'is_admin' => false,
            'message' => $message,
        ]);

        $ticket->update(['last_replied_at' => now(), 'status' => 'open']);

        return redirect()->back()->with('success', 'Reply sent successfully!');
    }
}
