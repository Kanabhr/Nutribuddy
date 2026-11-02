@extends('layouts.admin')
@php
    $title = 'Support Tickets';
    $subTitle = 'Ecommerce / Support Tickets';
@endphp

@section('content')
    @include('admin.partials.messages')



    <div class="card basic-data-table">
        <div class="card-header"><h5 class="card-title mb-0">Ticket List</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>Ticket Details</th>
                            <th>User</th>
                            <th>Status/Priority</th>
                            <th>Last Activity</th>
                            <th class="text-end">Update Management</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            @php
                                $statusClass = match(strtolower($ticket->status)) {
                                    'resolved', 'closed' => 'success',
                                    'in_progress' => 'info',
                                    default => 'warning'
                                };
                                $priorityClass = match(strtolower($ticket->priority)) {
                                    'high' => 'danger',
                                    'medium' => 'warning',
                                    default => 'success'
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-md fw-bold text-primary-600">{{ $ticket->ticket_number }}</span>
                                        <small class="text-dark fw-medium text-truncate" style="max-width: 250px">{{ $ticket->subject }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-sm text-secondary-light fw-medium">{{ $ticket->user?->name ?? 'Guest' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <span class="badge bg-{{ $statusClass }}-100 text-{{ $statusClass }}-600 w-fit px-2">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</span>
                                        <span class="badge bg-{{ $priorityClass }}-100 text-{{ $priorityClass }}-600 w-fit px-2">{{ ucfirst($ticket->priority) }}</span>
                                    </div>
                                </td>
                                <td><span class="text-xs text-secondary-light fw-medium">{{ $ticket->last_replied_at?->format('d M Y, H:i') ?? $ticket->created_at->format('d M Y, H:i') }}</span></td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2 py-2">
                                        <a href="{{ route('admin.ecommerce.support-tickets.show', $ticket) }}" class="btn btn-sm btn-outline-info-600 radius-8 d-inline-flex align-items-center gap-1">
                                            <iconify-icon icon="mingcute:chat-3-line"></iconify-icon> Chat
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-primary-600 radius-8 d-inline-flex align-items-center gap-1"
                                            data-bs-toggle="modal" data-bs-target="#editTicketModal"
                                            data-action="{{ route('admin.ecommerce.support-tickets.update', $ticket) }}"
                                            data-status="{{ $ticket->status }}"
                                            data-priority="{{ $ticket->priority }}"
                                            data-note="{{ $ticket->admin_note }}">
                                            <iconify-icon icon="mingcute:edit-2-line"></iconify-icon> Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.ecommerce.support-tickets.destroy', $ticket) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1" onclick="return confirm('Delete this ticket?')">
                                                <iconify-icon icon="mingcute:delete-2-line"></iconify-icon> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if ($.fn.DataTable.isDataTable('#dataTable')) {
                $('#dataTable').DataTable().destroy();
            }
            $('#dataTable').DataTable({
                responsive: true,
                order: [[0, 'desc']]
            });

            const editTicketModal = document.getElementById('editTicketModal');
            if (editTicketModal) {
                editTicketModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const action = button.getAttribute('data-action');
                    const status = button.getAttribute('data-status');
                    const priority = button.getAttribute('data-priority');
                    const note = button.getAttribute('data-note');

                    const form = editTicketModal.querySelector('#editTicketForm');
                    form.setAttribute('action', action);
                    
                    form.querySelector('[name="status"]').value = status;
                    form.querySelector('[name="priority"]').value = priority;
                    form.querySelector('[name="admin_note"]').value = note;
                });
            }
        });
    </script>

    <!-- Edit Ticket Modal -->
    <div class="modal fade" id="editTicketModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Support Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editTicketForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Admin Note</label>
                            <textarea name="admin_note" class="form-control" rows="3" placeholder="Add note here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
