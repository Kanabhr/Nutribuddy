@extends('layouts.admin')
@php
    $title = 'Order Returns';
    $subTitle = 'Ecommerce / Returns';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="card basic-data-table">
        <div class="card-header">
            <h5 class="card-title mb-0">Return Requests</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>Return Details</th>
                            <th>Order Details</th>
                            <th>Customer</th>
                            <th>Refund Amount</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($returns as $return)
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <a href="{{ route('admin.ecommerce.order-returns.show', $return) }}" class="text-md fw-bold text-primary-600 hover-text-primary-700">#{{ $return->return_number }}</a>
                                        <small class="text-secondary-light">{{ optional($return->created_at)->format('d M Y, H:i') }}</small>
                                        <small class="text-secondary-light">Return Qty: {{ $return->items->sum('quantity') }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <a href="{{ $return->order_id ? route('admin.ecommerce.orders.show', $return->order_id) : '#' }}" class="text-md fw-bold text-primary-600 hover-text-primary-700">#{{ $return->order?->order_number ?? 'N/A' }}</a>
                                        <small class="text-secondary-light">Order Items: {{ $return->order?->items_count ?? $return->order?->items?->count() ?? 0 }}</small>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $customerName = $return->order?->user?->name ?? $return->order?->customer_name ?? 'Guest Customer';
                                        $customerEmail = $return->order?->user?->email ?? $return->order?->customer_email ?? 'N/A';
                                        $avatarInitial = strtoupper(substr(trim($customerName), 0, 1) ?: 'C');
                                    @endphp
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="w-32-px h-32-px radius-circle bg-primary-100 d-flex align-items-center justify-content-center text-primary-600 fw-bold text-xs">
                                            {{ $avatarInitial }}
                                        </div>
                                        <div class="d-flex flex-column">
                                            <h6 class="text-sm mb-0 fw-medium">{{ $customerName }}</h6>
                                            <span class="text-xs text-secondary-light">{{ $customerEmail }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary-600">INR {{ number_format($return->refund_amount, 2) }}</span>
                                </td>
                                <td>
                                    @php
                                        $statusClass = match(strtolower($return->status)) {
                                            'completed' => 'success',
                                            'approved' => 'info',
                                            'rejected' => 'danger',
                                            default => 'warning'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}-100 text-{{ $statusClass }}-600 w-fit">{{ ucfirst($return->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1 status-edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editReturnStatusModal"
                                            data-status="{{ $return->status }}"
                                            data-amount="{{ $return->refund_amount }}"
                                            data-action="{{ route('admin.ecommerce.order-returns.update', $return) }}">
                                            <iconify-icon icon="lucide:edit"></iconify-icon> Status
                                        </button>
                                        <a href="{{ route('admin.ecommerce.order-returns.show', $return) }}" class="btn btn-sm btn-outline-primary-600 radius-8 d-inline-flex align-items-center gap-1">
                                            <iconify-icon icon="lucide:eye"></iconify-icon> View
                                        </a>
                                        <form action="{{ route('admin.ecommerce.order-returns.destroy', $return) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1" onclick="return confirm('Are you sure you want to delete this return record?')">
                                                <iconify-icon icon="lucide:trash-2"></iconify-icon> Delete
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

    <!-- Edit Return Status Modal -->
    <div class="modal fade" id="editReturnStatusModal" tabindex="-1" aria-labelledby="editReturnStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editReturnStatusModalLabel">Edit Return Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editReturnStatusForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Return Status</label>
                            <select name="status" id="edit_return_status" class="form-select" required>
                                @foreach (\App\Support\OrderFlow::RETURN_STATUSES as $statusOption)
                                    <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Refund Amount (INR)</label>
                            <input type="number" step="0.01" name="refund_amount" id="edit_refund_amount" class="form-control" required>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('dataTable')) {
                new DataTable('#dataTable');
            }

            const editModal = document.getElementById('editReturnStatusModal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    const action = button.getAttribute('data-action');
                    const status = button.getAttribute('data-status');
                    const amount = button.getAttribute('data-amount');

                    const form = editModal.querySelector('#editReturnStatusForm');
                    form.setAttribute('action', action);
                    
                    editModal.querySelector('#edit_return_status').value = status;
                    editModal.querySelector('#edit_refund_amount').value = amount;
                });
            }
        });
    </script>
@endsection
