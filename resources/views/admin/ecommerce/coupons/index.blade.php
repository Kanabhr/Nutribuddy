@extends('layouts.admin')
@php
    $title = 'Coupons';
    $subTitle = 'Ecommerce / Coupons';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="card mb-24">
        <div class="card-header">
            <h5 class="card-title mb-0">Create Coupon</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.ecommerce.coupons.store') }}" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Assign to User (Optional)</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="solar:user-linear"></iconify-icon>
                        </span>
                        <select name="user_id" class="form-select select2-user" style="padding-left: 40px;">
                            <option value="">Any User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} {{ $user->phone ? '('.$user->phone.')' : ($user->email ? '('.$user->email.')' : '') }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Code</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="mdi:ticket-percent"></iconify-icon>
                        </span>
                        <input type="text" name="code" class="form-control" placeholder="SUMMER50" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Name</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="mdi:alphabetical"></iconify-icon>
                        </span>
                        <input type="text" name="name" class="form-control" placeholder="Promo Name">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="discount_type" class="form-select" required>
                        <option value="percentage">Percentage</option>
                        <option value="flat">Flat</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Value</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:indian-rupee"></iconify-icon>
                        </span>
                        <input type="number" step="0.01" min="0" name="discount_value" class="form-control" placeholder="0.00" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Min Order Amount</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:shopping-cart"></iconify-icon>
                        </span>
                        <input type="number" step="0.01" min="0" name="min_order_amount" class="form-control" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Max Discount</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:trending-down"></iconify-icon>
                        </span>
                        <input type="number" step="0.01" min="0" name="max_discount_amount" class="form-control" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Usage Limit</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="mdi:counter"></iconify-icon>
                        </span>
                        <input type="number" min="1" name="usage_limit" class="form-control" placeholder="∞">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per User Limit</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="solar:user-linear"></iconify-icon>
                        </span>
                        <input type="number" min="1" name="usage_limit_per_user" class="form-control" placeholder="1">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Starts At</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="solar:calendar-linear"></iconify-icon>
                        </span>
                        <input type="date" name="starts_at" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ends At</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="solar:calendar-linear"></iconify-icon>
                        </span>
                        <input type="date" name="ends_at" class="form-control">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch d-flex align-items-center gap-2 p-0 mb-8">
                        <input class="form-check-input m-0 float-none" type="checkbox" value="1" name="is_active" id="createCouponActive" checked>
                        <label class="form-check-label m-0" for="createCouponActive">Active</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary-600">Create Coupon</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card basic-data-table">
        <div class="card-header">
            <h5 class="card-title mb-0">Coupon List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>Coupon Details</th>
                            <th>Assigned To</th>
                            <th>Discount</th>
                            <th>Usage</th>
                            <th>Validity</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($coupons as $coupon)
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-md fw-bold text-primary-600">{{ $coupon->code }}</span>
                                        <small class="text-secondary-light">{{ $coupon->name ?? 'No Name' }}</small>
                                    </div>
                                </td>
                                <td>
                                    @if($coupon->user)
                                        <span class="badge bg-warning-100 text-warning-600 px-2 fw-medium">{{ $coupon->user->name }}</span>
                                    @else
                                        <span class="text-secondary-light">Anyone</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-md fw-semibold text-dark">
                                            {{ $coupon->discount_type == 'percentage' ? $coupon->discount_value . '%' : 'INR ' . number_format($coupon->discount_value, 2) }}
                                        </span>
                                        <span class="badge bg-info-100 text-info-600 w-fit px-2">{{ ucfirst($coupon->discount_type) }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-sm fw-medium text-dark">Used: {{ $coupon->used_count }}</span>
                                        <small class="text-secondary-light">Limit: {{ $coupon->usage_limit ?? '∞' }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <small class="text-secondary-light">From: {{ $coupon->starts_at ? $coupon->starts_at->timezone(config('app.timezone'))->format('d M Y') : 'Anytime' }}</small>
                                        <small class="text-secondary-light">To: {{ $coupon->ends_at ? $coupon->ends_at->timezone(config('app.timezone'))->format('d M Y') : 'No Expiry' }}</small>
                                    </div>
                                </td>
                                <td>
                                    @if($coupon->is_active)
                                        <span class="badge bg-success-100 text-success-600 px-2 fw-medium">Active</span>
                                    @else
                                        <span class="badge bg-danger-100 text-danger-600 px-2 fw-medium">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1 edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editCouponModal"
                                            data-id="{{ $coupon->id }}"
                                            data-user_id="{{ $coupon->user_id }}"
                                            data-code="{{ $coupon->code }}"
                                            data-name="{{ $coupon->name }}"
                                            data-discount_type="{{ $coupon->discount_type }}"
                                            data-discount_value="{{ $coupon->discount_value }}"
                                            data-min_order_amount="{{ $coupon->min_order_amount }}"
                                            data-max_discount_amount="{{ $coupon->max_discount_amount }}"
                                            data-usage_limit="{{ $coupon->usage_limit }}"
                                            data-usage_limit_per_user="{{ $coupon->usage_limit_per_user }}"
                                            data-starts_at="{{ $coupon->starts_at ? $coupon->starts_at->timezone(config('app.timezone'))->format('Y-m-d') : '' }}"
                                            data-ends_at="{{ $coupon->ends_at ? $coupon->ends_at->timezone(config('app.timezone'))->format('Y-m-d') : '' }}"
                                            data-is_active="{{ $coupon->is_active }}"
                                            data-action="{{ route('admin.ecommerce.coupons.update', $coupon) }}">
                                            <iconify-icon icon="lucide:edit"></iconify-icon> Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.ecommerce.coupons.destroy', $coupon) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1" onclick="return confirm('Delete this coupon?')">
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

    <!-- Edit Coupon Modal -->
    <div class="modal fade" id="editCouponModal" tabindex="-1" aria-labelledby="editCouponModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCouponModalLabel">Edit Coupon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editCouponForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12 mb-2">
                                <label class="form-label">Assign to User (Optional)</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="solar:user-linear"></iconify-icon>
                                    </span>
                                    <select name="user_id" id="edit_coupon_user_id" class="form-select select2-user" style="padding-left: 40px;">
                                        <option value="">Any User</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }} {{ $user->phone ? '('.$user->phone.')' : ($user->email ? '('.$user->email.')' : '') }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Code</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="mdi:ticket-percent"></iconify-icon>
                                    </span>
                                    <input type="text" name="code" id="edit_coupon_code" class="form-control" placeholder="SUMMER50" required>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Name</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="mdi:alphabetical"></iconify-icon>
                                    </span>
                                    <input type="text" name="name" id="edit_coupon_name" class="form-control" placeholder="Promo Name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <select name="discount_type" id="edit_coupon_discount_type" class="form-select" required>
                                    <option value="percentage">Percentage</option>
                                    <option value="flat">Flat</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Value</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="lucide:indian-rupee"></iconify-icon>
                                    </span>
                                    <input type="number" step="0.01" min="0" name="discount_value" id="edit_coupon_discount_value" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Min Order Amount</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="lucide:shopping-cart"></iconify-icon>
                                    </span>
                                    <input type="number" step="0.01" min="0" name="min_order_amount" id="edit_coupon_min_order_amount" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Max Discount Amount</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="lucide:trending-down"></iconify-icon>
                                    </span>
                                    <input type="number" step="0.01" min="0" name="max_discount_amount" id="edit_coupon_max_discount_amount" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Usage Limit (Global)</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="mdi:counter"></iconify-icon>
                                    </span>
                                    <input type="number" min="1" name="usage_limit" id="edit_coupon_usage_limit" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Per User Limit</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="solar:user-linear"></iconify-icon>
                                    </span>
                                    <input type="number" min="1" name="usage_limit_per_user" id="edit_coupon_usage_limit_per_user" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Starts At</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="solar:calendar-linear"></iconify-icon>
                                    </span>
                                    <input type="date" name="starts_at" id="edit_coupon_starts_at" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ends At</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="solar:calendar-linear"></iconify-icon>
                                    </span>
                                    <input type="date" name="ends_at" id="edit_coupon_ends_at" class="form-control">
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end mt-4">
                                <input type="hidden" name="is_active" value="0">
                                <div class="form-check form-switch d-flex align-items-center gap-2 p-0 m-0">
                                    <input class="form-check-input m-0 float-none" type="checkbox" name="is_active" value="1" id="edit_coupon_is_active">
                                    <label class="form-check-label m-0" for="edit_coupon_is_active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary-600">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
