@extends('layouts.user-panel')
@section('title', 'Support Tickets - NutriBuddy Kids')
@section('panel-page-class', 'panel-userdashboard panel-tickets')

@section('panel-content')
    <div class="ud-main">
        <div class="page tickets-page">
            @if(session('success'))
                <div class="ticket-alert">
                    {{ session('success') }}
                </div>
            @endif

            <div class="ticket-compose-card">
                <div class="ticket-compose-head">
                    <div>
                        <span class="ticket-kicker">Need help?</span>
                        <h3>Create New Support Ticket</h3>
                        <p>Share the issue clearly and our team can respond faster.</p>
                    </div>
                    <div class="ticket-head-icon">?</div>
                </div>

                <form action="{{ route('user.support-tickets.store') }}" method="POST" class="ticket-form">
                    @csrf

                    <div class="ticket-form-grid">
                        <div class="ticket-field ticket-field-subject">
                            <label>Subject</label>
                            <input type="text" name="subject" class="ticket-control" placeholder="Briefly describe your issue" required>
                            @error('subject') <span class="ticket-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="ticket-field">
                            <label>Priority</label>
                            <select name="priority" class="ticket-control" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                            @error('priority') <span class="ticket-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="ticket-field">
                        <label>Message / Details</label>
                        <textarea name="message" class="ticket-control ticket-textarea" rows="5" placeholder="Please provide all relevant details so we can help you faster..." required></textarea>
                        @error('message') <span class="ticket-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ticket-actions">
                        <button type="submit" class="ticket-submit">Submit Ticket</button>
                    </div>
                </form>
            </div>

            <div class="ticket-list-card">
                <div class="ticket-list-head">
                    <div>
                        <span class="ticket-kicker">History</span>
                        <h3>Your Support Tickets</h3>
                    </div>
                </div>

                <div class="ticket-table-wrap">
                    <table class="tickets-table">
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Created / Replied</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tickets as $ticket)
                                <tr>
                                    <td>
                                        <a href="{{ route('user.support-tickets.show', $ticket) }}" class="ticket-id-link">
                                            {{ $ticket->ticket_number }}
                                        </a>
                                    </td>
                                    <td>{{ $ticket->subject }}</td>
                                    <td>
                                        <span class="status-badge status-{{ strtolower($ticket->status) }}">
                                            {{ str_replace('_', ' ', $ticket->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge priority-{{ strtolower($ticket->priority) }}">
                                            {{ $ticket->priority }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="ticket-date">
                                            Created: {{ $ticket->created_at->format('d M, Y') }}<br>
                                            @if($ticket->last_replied_at)
                                                <span>Replied: {{ $ticket->last_replied_at->format('d M, Y') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('user.support-tickets.show', $ticket) }}" class="ticket-chat-link">
                                            View Chat
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="ticket-empty">
                                        <div>+</div>
                                        You don't have any support tickets yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
