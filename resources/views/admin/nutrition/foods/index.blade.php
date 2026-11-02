@extends('layouts.admin')

@php($title = 'Master Food Data')
@php($subTitle = 'Diet Chart / Master Food Data')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/nutrition-foods.css') }}">
@endpush

@section('content')
    <div class="card food-admin-card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="card-title mb-1">Master Food Data</h5>
                <p class="text-muted mb-0">Search and review foods used by diet chart generation.</p>
            </div>
            @can('manage diet chart')
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#bulkUploadModal">
                    <i class="fa fa-upload me-1" aria-hidden="true"></i> Bulk upload
                </button>
            @endcan
        </div>

        <div class="card-body">
            <div class="food-filters" aria-label="Food filters">
                @foreach ([
                    'food_group' => 'Food group',
                    'slot' => 'Meal slot',
                    'diet_type' => 'Diet type',
                    'is_veg' => 'Vegetarian',
                    'region' => 'Region',
                ] as $filter => $label)
                    <label>
                        <span>{{ $label }}</span>
                        <select class="form-select food-filter" data-filter="{{ $filter }}">
                            <option value="">All {{ strtolower($label) }}s</option>
                            @foreach ($foodFilters[$filter] ?? [] as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach
                <button class="btn btn-outline-secondary" id="clearFoodFilters" type="button">Clear filters</button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="foodsTable" data-feed-url="{{ route('admin.nutrition.foods.data') }}" data-show-url="{{ url('admin/nutrition/foods') }}" data-delete-url="{{ url('admin/nutrition/foods') }}" data-can-manage="{{ $canManageFoods ? 'true' : 'false' }}">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Dish ID</th>
                            <th>Dish name</th>
                            <th>Food group</th>
                            <th>Meal slot</th>
                            <th>Diet type</th>
                            <th>Vegetarian</th>
                            <th>Region</th>
                            <th>Energy (kcal)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="foodDetailsModal" tabindex="-1" aria-labelledby="foodDetailsTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div><span class="food-modal-eyebrow">Food record</span><h5 class="modal-title" id="foodDetailsTitle">Food details</h5></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body"><div id="foodDetailsContent" class="food-details-grid"><p class="text-muted mb-0">Loading food details…</p></div></div>
            </div>
        </div>
    </div>

    @can('manage diet chart')
        <div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-labelledby="bulkUploadTitle" aria-hidden="true" data-upload-url="{{ route('admin.nutrition.foods.bulk-upload.store') }}">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div><span class="food-modal-eyebrow">Food catalog</span><h5 class="modal-title" id="bulkUploadTitle">Bulk upload foods</h5></div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label" for="bulkUploadFile">Choose an Excel or CSV file</label>
                        <input type="file" id="bulkUploadFile" class="form-control" accept=".xlsx,.xls,.csv,.txt">
                        <small class="form-text">Required columns: dish_id, dish_name, food_group, slot, diet_type, is_veg, region, energy_kcal. Existing or repeated dish IDs are reported as skipped duplicates.</small>
                        <div id="bulkUploadResult" class="mt-3" aria-live="polite"></div>
                        <div id="bulkUploadFailedWrap" class="food-upload-failures" hidden>
                            <h6>Rows needing attention</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead><tr><th>Dish ID</th><th>Dish name</th><th>Food group</th><th>Slot</th><th>Reason</th></tr></thead>
                                    <tbody id="bulkUploadFailedBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="bulkUploadSubmit"><i class="fa fa-upload me-1" aria-hidden="true"></i> Upload file</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
    <script src="{{ asset('js/nutrition-foods.js') }}" defer></script>
@endpush
