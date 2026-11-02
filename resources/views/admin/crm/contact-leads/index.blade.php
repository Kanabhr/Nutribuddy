@extends('layouts.admin')
@php
    $title = 'Contact Leads';
    $subTitle = 'Ecommerce / Contact Leads';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="card basic-data-table">
        <div class="card-header"><h5 class="card-title mb-0">Lead Inbox</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>Lead Details</th>
                            <th>Contact Info</th>
                            <th>Status</th>
                            <th>Assignee</th>
                            <th class="text-end">Update Management</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leads as $lead)
                            @php
                                $statusClass = match(strtolower($lead->status)) {
                                    'new' => 'info',
                                    'in_progress' => 'warning',
                                    'closed' => 'success',
                                    default => 'secondary'
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-md fw-bold text-dark">{{ $lead->name }}</span>
                                        <small class="text-secondary-light fw-medium">{{ $lead->subject ?? 'No Subject' }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-sm text-dark fw-medium">{{ $lead->email }}</span>
                                        <small class="text-secondary-light">{{ $lead->phone ?? 'No Phone' }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $statusClass }}-100 text-{{ $statusClass }}-600 px-2 fw-medium">
                                        {{ str_replace('_', ' ', ucfirst($lead->status)) }}
                                    </span>
                                </td>
                                <td><span class="text-sm text-secondary-light fw-medium">{{ $lead->assignee?->name ?? 'Unassigned' }}</span></td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2 py-2">
                                        <form method="POST" action="{{ route('admin.ecommerce.contact-leads.update', $lead) }}" class="d-inline-flex gap-2 align-items-center">
                                            @csrf
                                            @method('PUT')
                                            <select name="status" class="form-select form-select-sm py-1" style="width: 120px">
                                                <option value="new" {{ $lead->status === 'new' ? 'selected' : '' }}>New</option>
                                                <option value="in_progress" {{ $lead->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                                <option value="closed" {{ $lead->status === 'closed' ? 'selected' : '' }}>Closed</option>
                                            </select>
                                            <select name="assigned_to" class="form-select form-select-sm py-1" style="width: 130px">
                                                <option value="">Unassigned</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}" {{ (string) $lead->assigned_to === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-success-600 radius-8 px-3">Save</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.ecommerce.contact-leads.destroy', $lead) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1" onclick="return confirm('Delete this lead?')">
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
        });
    </script>
@endsection
