@extends('layouts.admin')
@php
    $title = 'Support Ticket #' . $ticket->ticket_number;
    $subTitle = 'Ecommerce / Support Tickets / Ticket Details';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Conversation</h5>
                    <a href="{{ route('admin.ecommerce.support-tickets.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                        <iconify-icon icon="mingcute:arrow-left-line" class="text-xl"></iconify-icon> 
                        <span>Back</span>
                    </a>
                </div>
                <div class="card-body p-24" style="background-color: #f8f9fa;">
                    <div class="d-flex flex-column gap-4 mb-24">
                        @forelse($ticket->messages as $msg)
                            <div class="card shadow-sm border-0" style="border-left: 4px solid {{ $msg->is_admin ? '#487fff' : '#6c757d' }} !important;">
                                <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                                    <div class="fw-bold d-flex align-items-center gap-2" style="color: {{ $msg->is_admin ? '#487fff' : '#495057' }};">
                                        <iconify-icon icon="{{ $msg->is_admin ? 'mingcute:user-shield-2-fill' : 'mingcute:user-3-fill' }}" class="fs-5"></iconify-icon>
                                        {{ $msg->is_admin ? 'You (Admin)' : ($msg->user->name ?? 'Customer') }}
                                    </div>
                                    <div class="text-secondary small fw-medium">
                                        <iconify-icon icon="mingcute:time-line" class="me-1 align-middle"></iconify-icon>
                                        {{ $msg->created_at->format('d M, Y H:i') }}
                                    </div>
                                </div>
                                <div class="card-body pt-2 pb-3">
                                    <div style="white-space: pre-wrap; font-size: 0.95rem; color: #343a40;">{{ $msg->message }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-secondary py-5">
                                No messages yet.
                            </div>
                        @endforelse
                    </div>

                    @if(!in_array($ticket->status, ['resolved', 'closed']))
                        <div class="card mt-4 shadow-sm border-0">
                            <div class="card-body">
                                <form action="{{ route('admin.ecommerce.support-tickets.reply', $ticket) }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Send Reply</label>
                                        <textarea name="message" class="form-control bg-light" rows="4" placeholder="Type your response to the customer here..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                                        <iconify-icon icon="mingcute:send-plane-fill"></iconify-icon> Send Reply
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info text-center mt-4">
                            This ticket has been marked as <strong>{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</strong>. Replies are disabled.
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ticket Information</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary fw-semibold">Ticket #</span>
                            <span class="fw-bold">{{ $ticket->ticket_number }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary fw-semibold">Customer</span>
                            <span class="fw-bold text-end">{{ $ticket->user->name ?? 'Guest' }}<br><small class="text-secondary">{{ $ticket->user->email ?? '' }}</small></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary fw-semibold">Created</span>
                            <span class="fw-bold">{{ $ticket->created_at->format('d M, Y H:i') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary fw-semibold">Status</span>
                            @php
                                $statusClass = match(strtolower($ticket->status)) {
                                    'resolved', 'closed' => 'success',
                                    'in_progress' => 'info',
                                    default => 'warning'
                                };
                            @endphp
                            <span class="badge bg-{{ $statusClass }}-100 text-{{ $statusClass }}-600 px-2 py-1 rounded-pill">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary fw-semibold">Priority</span>
                            @php
                                $priorityClass = match(strtolower($ticket->priority)) {
                                    'high' => 'danger',
                                    'medium' => 'warning',
                                    default => 'success'
                                };
                            @endphp
                            <span class="badge bg-{{ $priorityClass }}-100 text-{{ $priorityClass }}-600 px-2 py-1 rounded-pill">{{ ucfirst($ticket->priority) }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Update Status</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.ecommerce.support-tickets.update', $ticket) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                                <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>High</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Admin Note (Internal)</label>
                            <textarea name="admin_note" class="form-control" rows="3">{{ $ticket->admin_note }}</textarea>
                            <small class="text-secondary">This note is only visible to admins.</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Ticket</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
