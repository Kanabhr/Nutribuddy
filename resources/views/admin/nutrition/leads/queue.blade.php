@extends('layouts.admin')

@php($title = $isSuperAdmin ? 'All Nutrition Leads' : 'My Nutrition Leads')
@php($subTitle = $isSuperAdmin ? 'All customers awaiting diet-chart review' : 'Assigned customers awaiting diet-chart review')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/nutrition-leads.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/nutritionist-queue.js') }}" defer></script>
@endpush

@section('content')
<div class="card nutrition-leads-card">
    <div class="card-body p-24">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row g-3 align-items-end mb-4" id="queueFilters">
            <div class="col-sm-6 col-lg-3">
                <label for="queueStatusFilter" class="form-label">Lead status</label>
                <select id="queueStatusFilter" class="form-select" data-queue-filter="status">
                    <option value="">All statuses</option>
                    @foreach (['new', 'assigned', 'awaiting_review', 'completed'] as $status)
                        <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            @if ($isSuperAdmin)
                <div class="col-sm-6 col-lg-3">
                    <label for="queueAssigneeFilter" class="form-label">Assigned nutritionist</label>
                    <select id="queueAssigneeFilter" class="form-select" data-queue-filter="assigned_to">
                        <option value="">All nutritionists</option>
                        <option value="unassigned">Unassigned</option>
                        @foreach ($nutritionists as $nutritionist)
                            <option value="{{ $nutritionist->id }}">{{ $nutritionist->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-sm-6 col-lg-2">
                <label for="queueStartDate" class="form-label">Assigned from</label>
                <input type="date" id="queueStartDate" class="form-control" data-queue-filter="date_from">
            </div>
            <div class="col-sm-6 col-lg-2">
                <label for="queueEndDate" class="form-label">Assigned to</label>
                <input type="date" id="queueEndDate" class="form-control" data-queue-filter="date_to">
            </div>
            <div class="col-sm-6 col-lg-2">
                <button type="button" class="btn btn-outline-secondary w-100" data-clear-queue-filters>Clear filters</button>
            </div>
        </div>

        <div class="table-responsive">
            <table
                class="table align-middle w-100"
                id="nutritionistQueueTable"
                data-source="{{ route('admin.nutrition.lead-queue.data') }}"
                data-review-base="{{ route('admin.nutrition.lead-queue.index') }}"
                data-super-admin="{{ $isSuperAdmin ? 'true' : 'false' }}"
            >
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Child</th>
                        <th>Plan</th>
                        <th>Assigned</th>
                        <th>Status</th>
                        <th>Review</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
