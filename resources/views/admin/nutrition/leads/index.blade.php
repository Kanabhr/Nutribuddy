@extends('layouts.admin')

@php($title = 'Nutrition Leads')
@php($subTitle = 'Assign purchased diet plans to nutritionists')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/nutrition-leads.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/nutrition-leads.js') }}" defer></script>
@endpush

@section('content')
<div class="card nutrition-leads-card">
    <div class="card-body p-24">
        <div class="alert alert-success d-none" role="status" aria-live="polite" data-lead-message></div>
        <div class="alert alert-danger d-none" role="alert" data-lead-error></div>

        <div id="leadFilters" class="row g-3 align-items-end mb-4">
            <div class="col-sm-6 col-lg-3">
                <label for="leadStatusFilter" class="form-label">Lead status</label>
                <select id="leadStatusFilter" class="form-select" data-lead-filter="status">
                    <option value="">All statuses</option>
                    @foreach (['new', 'assigned', 'awaiting_review', 'completed'] as $status)
                        <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <label for="leadAssigneeFilter" class="form-label">Assigned nutritionist</label>
                <select id="leadAssigneeFilter" class="form-select" data-lead-filter="assigned_to">
                    <option value="">All nutritionists</option>
                    <option value="unassigned">Unassigned</option>
                    @foreach ($nutritionists as $nutritionist)
                        <option value="{{ $nutritionist->id }}">{{ $nutritionist->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label for="leadStartDate" class="form-label">Purchased from</label>
                <input type="date" id="leadStartDate" class="form-control" data-lead-filter="start_date">
            </div>
            <div class="col-sm-6 col-lg-2">
                <label for="leadEndDate" class="form-label">Purchased to</label>
                <input type="date" id="leadEndDate" class="form-control" data-lead-filter="end_date">
            </div>
            <div class="col-sm-6 col-lg-2">
                <button type="button" class="btn btn-outline-secondary w-100" data-clear-lead-filters>Clear filters</button>
            </div>
        </div>

        <form action="{{ route('admin.nutrition.leads.bulk-assign') }}" method="POST" data-bulk-leads data-bulk-url="{{ route('admin.nutrition.leads.bulk-assign') }}">
            @csrf
            @method('PATCH')
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <select class="form-select w-auto" name="assigned_to" required aria-label="Assign selected leads to">
                    <option value="">Assign selected to…</option>
                    @foreach ($nutritionists as $nutritionist)
                        <option value="{{ $nutritionist->id }}">{{ $nutritionist->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-outline-primary" type="submit">Bulk assign selected</button>
                <span class="text-sm text-secondary-light" data-selection-count>Select rows on this page</span>
            </div>

            <select class="d-none" aria-hidden="true" tabindex="-1" data-assignee-options>
                <option value="">Choose nutritionist…</option>
                @foreach ($nutritionists as $nutritionist)
                    <option value="{{ $nutritionist->id }}">{{ $nutritionist->name }}</option>
                @endforeach
            </select>

            <div class="table-responsive">
                <table class="table align-middle w-100" id="nutritionLeadsTable" data-source="{{ route('admin.nutrition.leads.data') }}">
                    <thead>
                        <tr>
                            <th><input class="form-check-input border-secondary" type="checkbox" data-select-all aria-label="Select leads on this page"></th>
                            <th>Customer</th>
                            <th>Child / Plan</th>
                            <th>Purchased</th>
                            <th>Status</th>
                            <th>Assigned to</th>
                            <th>Assign</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </form>
    </div>
</div>
@endsection
