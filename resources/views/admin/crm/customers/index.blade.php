@extends('layouts.admin')
@php
    $title = 'Customers';
    $subTitle = 'Ecommerce / Customers';
@endphp

@section('content')
    @include('admin.partials.messages')



    <div class="card basic-data-table">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Customer List</h5>
        </div>
        <div class="card-body">
            <!-- Filter Form -->
            <form method="GET" action="{{ route('admin.ecommerce.customers.index') }}" class="row g-3 mb-4">
                <div class="col-md-3">
                    <input type="text" name="city" class="form-control" placeholder="Filter by City" value="{{ request('city') }}">
                </div>
                <div class="col-md-3">
                    <input type="text" name="state" class="form-control" placeholder="Filter by State" value="{{ request('state') }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary-600">Filter</button>
                    <a href="{{ route('admin.ecommerce.customers.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>Customer Details</th>
                            <th>Contact Info</th>
                            <th>Location</th>
                            <th>NB Coins</th>
                            <th>Status</th>
                            <th>Joined At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-md fw-semibold text-dark">{{ $customer->name }}</span>
                                        <small class="text-secondary-light">ID: #{{ $customer->id }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-sm text-dark fw-medium">{{ $customer->email }}</span>
                                        <small class="text-secondary-light">{{ $customer->phone ?? 'No Phone' }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        @if($customer->customerAddresses->isNotEmpty())
                                            @php $address = $customer->customerAddresses->first(); @endphp
                                            <span class="text-sm text-dark fw-medium">{{ $address->city ?? 'N/A' }}</span>
                                            <small class="text-secondary-light">{{ $address->state ?? 'N/A' }}</small>
                                        @else
                                            <span class="text-sm text-secondary-light">No Address</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1 fw-bold text-warning-main">
                                        <iconify-icon icon="solar:medal-ribbon-star-bold" class="text-xl"></iconify-icon>
                                        {{ number_format($customer->coins_balance ?? 0) }}
                                    </div>
                                </td>
                                <td>
                                    @if ($customer->is_active)
                                        <span class="badge bg-success-100 text-success-600 px-2 fw-medium">Active</span>
                                    @else
                                        <span class="badge bg-danger-100 text-danger-600 px-2 fw-medium">Inactive</span>
                                    @endif
                                </td>
                                <td><span class="text-sm text-secondary-light fw-medium">{{ optional($customer->created_at)->format('d M Y') ?? 'N/A' }}</span></td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1 edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editCustomerModal"
                                            data-id="{{ $customer->id }}"
                                            data-name="{{ $customer->name }}"
                                            data-email="{{ $customer->email }}"
                                            data-phone="{{ $customer->phone ?? '' }}"
                                            data-is_active="{{ $customer->is_active }}"
                                            data-action="{{ route('admin.ecommerce.customers.update', $customer) }}">
                                            <iconify-icon icon="lucide:edit"></iconify-icon> Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.ecommerce.customers.destroy', $customer) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1" onclick="return confirm('Delete this customer account?')">
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

    <!-- Edit Customer Modal -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editCustomerForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Name</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="solar:user-broken"></iconify-icon>
                                    </span>
                                    <input type="text" name="name" id="edit_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="mdi:email-outline"></iconify-icon>
                                    </span>
                                    <input type="email" name="email" id="edit_email" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="mdi:phone-outline"></iconify-icon>
                                    </span>
                                    <input type="text" name="phone" id="edit_phone" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">New Password (optional)</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="mdi:lock-outline"></iconify-icon>
                                    </span>
                                    <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                                </div>
                            </div>
                            <div class="col-12 mt-4">
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_is_active">
                                    <label class="form-check-label" for="edit_is_active">Active Account</label>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            if (document.getElementById('dataTable')) {
                new DataTable('#dataTable');
            }

            const editModal = document.getElementById('editCustomerModal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    const action = button.getAttribute('data-action');
                    const name = button.getAttribute('data-name');
                    const email = button.getAttribute('data-email');
                    const phone = button.getAttribute('data-phone');
                    const isActive = button.getAttribute('data-is_active');

                    const form = editModal.querySelector('#editCustomerForm');
                    form.setAttribute('action', action);
                    
                    editModal.querySelector('#edit_name').value = name;
                    editModal.querySelector('#edit_email').value = email;
                    editModal.querySelector('#edit_phone').value = phone;
                    editModal.querySelector('#edit_is_active').checked = isActive === '1';
                });
            }
        });
    </script>
@endsection
