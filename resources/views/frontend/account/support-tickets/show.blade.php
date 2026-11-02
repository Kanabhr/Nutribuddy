@extends('layouts.user-panel')
@section('title', 'Ticket #' . $ticket->ticket_number . ' — NutriBuddy Kids')
@section('panel-page-class', 'panel-userdashboard panel-tickets-show')

@section('panel-content')
    <div class="ud-main">
        <div class="page" style="padding: 30px;">
            <a href="{{ route('user.support-tickets') }}" class="back-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Tickets
            </a>

            @if(session('success'))
                <div
                    style="background: #dcfce7; color: #16a34a; padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; font-weight: 700;">
                    {{ session('success') }}
                </div>
            @endif

            <div class="header-box">
                <div>
                    <h1 class="ticket-title">#{{ $ticket->ticket_number }} - {{ $ticket->subject }}</h1>
                    <div style="margin-top: 15px;">
                        <span
                            class="status-badge status-{{ strtolower($ticket->status) }}">{{ str_replace('_', ' ', $ticket->status) }}</span>
                        <span class="status-badge priority-{{ strtolower($ticket->priority) }}">Priority:
                            {{ $ticket->priority }}</span>
                    </div>
                </div>
                <div style="text-align: right; color: #6b7280; font-size: 0.85rem; font-weight: 600;">
                    Created: {{ $ticket->created_at->format('d M, Y H:i') }}
                </div>
            </div>

            <div class="ticket-replies">
                @foreach($ticket->messages as $msg)
                    <div class="reply-card">
                        <div class="reply-header">
                            <div class="reply-author {{ $msg->is_admin ? 'admin-author' : '' }}">
                                @if($msg->is_admin)
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    </svg>
                                    NutriBuddy Support
                                @else
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    You
                                @endif
                            </div>
                            <div class="reply-time">
                                {{ $msg->created_at->format('d M, Y H:i') }}
                            </div>
                        </div>
                        <div class="reply-body">{{ $msg->message }}</div>
                    </div>
                @endforeach
            </div>

            @if(!in_array($ticket->status, ['resolved', 'closed']))
                <div class="reply-box">
                    <h3
                        style="font-family: 'Fredoka One', cursive; font-size: 1.1rem; margin-top: 0; margin-bottom: 15px; color: #1f2937;">
                        Write a Reply</h3>
                    <form action="{{ route('user.support-tickets.reply', $ticket) }}" method="POST">
                        @csrf
                        <textarea name="message" class="form-control" rows="4" placeholder="Type your message here..."
                            required></textarea>
                        @error('message') <span
                            style="color:red; font-size: 0.8rem; display:block; margin-top:-10px; margin-bottom:10px;">{{ $message }}</span>
                        @enderror
                        <button type="submit" class="btn-submit">Send Reply</button>
                    </form>
                </div>
            @else
                <div
                    style="text-align: center; padding: 30px; background: #e0f2fe; color: #0284c7; border-radius: 20px; font-weight: 700;">
                    This ticket has been marked as {{ str_replace('_', ' ', $ticket->status) }}. If you have a new issue, please
                    create a new ticket.
                </div>
            @endif

        </div>
    </div>
@endsection
