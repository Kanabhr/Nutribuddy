@extends('layouts.admin')
@php
    $title = 'Newsletter Subscribers';
    $subTitle = 'Ecommerce / Newsletter';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="card mb-24">
        <div class="card-header"><h5 class="card-title mb-0">Add Subscriber</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.ecommerce.newsletter.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="subscribed">Subscribed</option>
                        <option value="unsubscribed">Unsubscribed</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Add Subscriber</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card basic-data-table">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="card-title mb-0">Subscriber List</h5>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('admin.ecommerce.newsletter.export', ['status' => 'subscribed']) }}" class="btn btn-sm btn-success-600 d-inline-flex align-items-center gap-1">
                    <iconify-icon icon="lucide:download"></iconify-icon> Export Subscribed CSV
                </a>
                <a href="{{ route('admin.ecommerce.newsletter.export') }}" class="btn btn-sm btn-outline-primary-600 d-inline-flex align-items-center gap-1">
                    <iconify-icon icon="lucide:file-down"></iconify-icon> Export All CSV
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>Subscriber</th>
                            <th>Status</th>
                            <th>Source</th>
                            <th>Joined At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subscribers as $subscriber)
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-md fw-bold text-dark">{{ $subscriber->email }}</span>
                                        <small class="text-secondary-light fw-medium">{{ $subscriber->name ?? 'No Name' }}</small>
                                    </div>
                                </td>
                                <td>
                                    @if($subscriber->status == 'subscribed')
                                        <span class="badge bg-success-100 text-success-600 px-2 fw-medium">Subscribed</span>
                                    @else
                                        <span class="badge bg-danger-100 text-danger-600 px-2 fw-medium">Unsubscribed</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info-100 text-info-600 px-2 fw-medium">{{ ucfirst(str_replace('_', ' ', $subscriber->source ?: 'manual')) }}</span>
                                </td>
                                <td><span class="text-sm text-secondary-light fw-medium">{{ optional($subscriber->created_at)->format('d M Y') ?? 'N/A' }}</span></td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1 edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editSubscriberModal"
                                            data-name="{{ $subscriber->name }}"
                                            data-status="{{ $subscriber->status }}"
                                            data-action="{{ route('admin.ecommerce.newsletter.update', $subscriber) }}">
                                            <iconify-icon icon="lucide:edit"></iconify-icon> Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.ecommerce.newsletter.destroy', $subscriber) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1" onclick="return confirm('Delete this subscriber?')">
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

    <!-- Edit Subscriber Modal -->
    <div class="modal fade" id="editSubscriberModal" tabindex="-1" aria-labelledby="editSubscriberModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSubscriberModalLabel">Edit Subscriber</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editSubscriberForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="subscribed">Subscribed</option>
                                <option value="unsubscribed">Unsubscribed</option>
                            </select>
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
            // Initialize DataTable
            if (document.getElementById('dataTable')) {
                new DataTable('#dataTable');
            }

            const editModal = document.getElementById('editSubscriberModal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    const action = button.getAttribute('data-action');
                    const name = button.getAttribute('data-name');
                    const status = button.getAttribute('data-status');

                    const form = editModal.querySelector('#editSubscriberForm');
                    form.setAttribute('action', action);
                    

                    editModal.querySelector('#edit_status').value = status;
                });
            }
        });
    </script>
@endsection
