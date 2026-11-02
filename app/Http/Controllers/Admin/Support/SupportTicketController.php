<?php

namespace App\Http\Controllers\Admin\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Support\ReplySupportTicketRequest;
use App\Http\Requests\Admin\Support\StoreSupportTicketRequest;
use App\Http\Requests\Admin\Support\UpdateSupportTicketRequest;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    public function index(): View
    {
        return view('admin.support.support-tickets.index', [
            'tickets' => SupportTicket::with('user')->latest()->get(),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['ticket_number'] = 'NB-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        $validated['last_replied_at'] = now();

        SupportTicket::create($validated);

        return back()->with('success', 'Support ticket created successfully.');
    }

    public function update(UpdateSupportTicketRequest $request, SupportTicket $supportTicket): RedirectResponse
    {
        $validated = $request->validated();

        $validated['last_replied_at'] = now();

        $supportTicket->update($validated);

        return back()->with('success', 'Support ticket updated successfully.');
    }

    public function show(SupportTicket $supportTicket): View
    {
        $supportTicket->load('messages.user');
        
        return view('admin.support.support-tickets.show', [
            'ticket' => $supportTicket,
        ]);
    }

    public function reply(ReplySupportTicketRequest $request, SupportTicket $supportTicket): RedirectResponse
    {
        $supportTicket->messages()->create([
            'user_id' => auth()->id(),
            'is_admin' => true,
            'message' => $request->message,
        ]);

        $supportTicket->update(['last_replied_at' => now(), 'status' => 'in_progress']);

        return back()->with('success', 'Reply sent successfully.');
    }

    public function destroy(SupportTicket $supportTicket): RedirectResponse
    {
        $supportTicket->delete();

        return redirect()->route('admin.ecommerce.support-tickets.index')->with('success', 'Support ticket deleted successfully.');
    }
}
