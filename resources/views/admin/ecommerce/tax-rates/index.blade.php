@extends('layouts.admin')
@php
    $title = 'Tax Rates';
    $subTitle = 'Ecommerce / Tax Rates';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="card border-0 radius-12 mb-24">
        <div class="card-header bg-base border-bottom py-16 px-24">
            <h5 class="card-title mb-0">Create Tax Rate</h5>
        </div>
        <div class="card-body p-24">
            <form method="POST" action="{{ route('admin.ecommerce.tax-rates.store') }}" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Name</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:type"></iconify-icon>
                        </span>
                        <input type="text" name="name" class="form-control" placeholder="GST 18%" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Code</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:hash"></iconify-icon>
                        </span>
                        <input type="text" name="code" class="form-control" placeholder="GST18" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Rate (%)</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:percent"></iconify-icon>
                        </span>
                        <input type="number" step="0.01" min="0" max="100" name="rate" class="form-control" placeholder="18.00" required>
                    </div>
                </div>

                <div class="col-md-4 d-flex flex-column justify-content-end gap-2 pb-2">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch d-flex align-items-center gap-2 p-0 m-0">
                        <input class="form-check-input m-0 float-none" type="checkbox" value="1" name="is_active" id="create_tax_is_active" checked>
                        <label class="form-check-label m-0" for="create_tax_is_active">Active</label>
                    </div>
                    <input type="hidden" name="show_in_checkout" value="0">
                    <div class="form-check form-switch d-flex align-items-center gap-2 p-0 m-0 mt-1">
                        <input class="form-check-input m-0 float-none" type="checkbox" value="1" name="show_in_checkout" id="create_tax_show" checked>
                        <label class="form-check-label m-0" for="create_tax_show">Show GST in Checkout Summary</label>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary-600">Create Tax Rate</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card basic-data-table border-0 radius-12 mb-24">
        <div class="card-header bg-base border-bottom py-16 px-24">
            <h5 class="card-title mb-0">Tax Rate List</h5>
        </div>
        <div class="card-body p-24">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tax Name</th>
                            <th>Code</th>
                            <th>Rate Breakdown</th>
                            <th>Status/Sort</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($taxRates as $taxRate)
                            <tr>
                                <td><span class="text-sm text-secondary-light">{{ $taxRate->id }}</span></td>
                                <td><span class="text-md fw-semibold text-dark">{{ $taxRate->name }}</span></td>
                                <td><span class="text-sm text-secondary-light fw-medium">{{ $taxRate->code }}</span></td>
                                <td>
                                    <span class="fw-bold text-primary-600">{{ number_format($taxRate->rate, 2) }}% Total</span>
                                    <div class="text-xs text-secondary-light mt-1">
                                        <span class="d-block text-success-700 fw-medium">Same State: {{ number_format($taxRate->rate / 2, 2) }}% CGST + {{ number_format($taxRate->rate / 2, 2) }}% SGST</span>
                                        <span class="d-block text-info-700 fw-medium">Other State: {{ number_format($taxRate->rate, 2) }}% IGST</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @if($taxRate->is_active)
                                            <span class="badge bg-success-100 text-success-600 w-fit">Active</span>
                                        @else
                                            <span class="badge bg-danger-100 text-danger-600 w-fit">Inactive</span>
                                        @endif

                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1 edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editTaxModal"
                                            data-id="{{ $taxRate->id }}"
                                            data-name="{{ $taxRate->name }}"
                                            data-code="{{ $taxRate->code }}"
                                            data-rate="{{ $taxRate->rate }}"
                                            data-sort_order="{{ $taxRate->sort_order }}"
                                            data-is_active="{{ $taxRate->is_active }}"
                                            data-show_in_checkout="{{ $taxRate->show_in_checkout }}"
                                            data-description="{{ $taxRate->description }}"
                                            data-action="{{ route('admin.ecommerce.tax-rates.update', $taxRate) }}">
                                            <iconify-icon icon="lucide:edit"></iconify-icon> Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.ecommerce.tax-rates.destroy', $taxRate) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1" onclick="return confirm('Delete this tax rate?')">
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

    <!-- Edit Tax Modal -->
    <div class="modal fade" id="editTaxModal" tabindex="-1" aria-labelledby="editTaxModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTaxModalLabel">Edit Tax Rate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editTaxForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Name</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="lucide:type"></iconify-icon>
                                    </span>
                                    <input type="text" name="name" id="edit_tax_name" class="form-control" placeholder="GST 18%" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Code</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="lucide:hash"></iconify-icon>
                                    </span>
                                    <input type="text" name="code" id="edit_tax_code" class="form-control" placeholder="GST18" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rate (%)</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="lucide:percent"></iconify-icon>
                                    </span>
                                    <input type="number" step="0.01" min="0" max="100" name="rate" id="edit_tax_rate" class="form-control" placeholder="18.00" required>
                                </div>
                            </div>

                            <div class="col-md-6 d-flex flex-column justify-content-end gap-2 pb-2">
                                <input type="hidden" name="is_active" value="0">
                                <div class="form-check form-switch d-flex align-items-center gap-2 p-0 m-0">
                                    <input class="form-check-input m-0 float-none" type="checkbox" name="is_active" value="1" id="edit_tax_is_active">
                                    <label class="form-check-label m-0" for="edit_tax_is_active">Active</label>
                                </div>
                                <input type="hidden" name="show_in_checkout" value="0">
                                <div class="form-check form-switch d-flex align-items-center gap-2 p-0 m-0 mt-1">
                                    <input class="form-check-input m-0 float-none" type="checkbox" name="show_in_checkout" value="1" id="edit_tax_show_in_checkout">
                                    <label class="form-check-label m-0" for="edit_tax_show_in_checkout">Show GST in Checkout</label>
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

            const editModal = document.getElementById('editTaxModal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    // Extract info from data-* attributes
                    const action = button.getAttribute('data-action');
                    const name = button.getAttribute('data-name');
                    const code = button.getAttribute('data-code');
                    const rate = button.getAttribute('data-rate');
                    const sortOrder = button.getAttribute('data-sort_order');
                    const isActive = button.getAttribute('data-is_active');
                    const showInCheckout = button.getAttribute('data-show_in_checkout');


                    // Update the modal's content.
                    const form = editModal.querySelector('#editTaxForm');
                    form.setAttribute('action', action);
                    
                    editModal.querySelector('#edit_tax_name').value = name;
                    editModal.querySelector('#edit_tax_code').value = code;
                    editModal.querySelector('#edit_tax_rate').value = rate;


                    editModal.querySelector('#edit_tax_is_active').checked = isActive === '1';
                    editModal.querySelector('#edit_tax_show_in_checkout').checked = showInCheckout === '1';
                });
            }
        });
    </script>
@endsection
