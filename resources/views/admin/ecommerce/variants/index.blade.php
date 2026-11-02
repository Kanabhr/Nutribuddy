@extends('layouts.admin')
@php
    $title = 'Product Variants';
    $subTitle = 'Ecommerce / Variants';
@endphp

@section('content')
    @include('admin.partials.messages')

    <!-- <div class="card mb-24">
            <div class="card-header">
                <h5 class="card-title mb-0">Create Variant</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.ecommerce.variants.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Product</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Select Product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Variant Name</label>
                        <div class="icon-field">
                            <span class="icon">
                                <iconify-icon icon="lucide:type"></iconify-icon>
                            </span>
                            <input type="text" name="name" class="form-control" placeholder="e.g. 500g" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">SKU</label>
                        <div class="icon-field">
                            <span class="icon">
                                <iconify-icon icon="ant-design:barcode-outlined"></iconify-icon>
                            </span>
                            <input type="text" name="sku" class="form-control" placeholder="Auto generated if empty">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Price (INR)</label>
                        <div class="icon-field">
                            <span class="icon">
                                <iconify-icon icon="lucide:indian-rupee"></iconify-icon>
                            </span>
                            <input type="number" name="price" class="form-control" min="0" step="0.01" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Attributes (JSON)</label>
                        <div class="icon-field">
                            <span class="icon">
                                <iconify-icon icon="lucide:settings-2"></iconify-icon>
                            </span>
                            <input type="text" name="attributes" class="form-control" placeholder='{"flavor":"Mango"}'>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Stock Qty</label>
                        <div class="icon-field">
                            <span class="icon">
                                <iconify-icon icon="solar:box-linear"></iconify-icon>
                            </span>
                            <input type="number" name="stock_qty" class="form-control" min="0" value="0">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-3">
                        <div class="form-check form-switch mb-8">
                            <input type="hidden" name="is_default" value="0">
                            <input class="form-check-input" type="checkbox" value="1" name="is_default">
                            <label class="form-check-label">Default</label>
                        </div>
                        <div class="form-check form-switch mb-8">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" value="1" name="is_active" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                        <div class="form-check form-switch mb-8">
                            <input type="hidden" name="is_in_stock" value="0">
                            <input class="form-check-input" type="checkbox" value="1" name="is_in_stock" checked>
                            <label class="form-check-label">InStock</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary-600">Create Variant</button>
                    </div>
                </form>
            </div>
        </div> -->

    <div class="card basic-data-table">
        <div class="card-header">
            <h5 class="card-title mb-0">Variant List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>Variant Details</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Inventory</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($variants as $variant)
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-md fw-semibold text-dark">{{ $variant->name }}</span>
                                        <small class="text-secondary-light">Product:
                                            {{ $variant->product?->name ?? '—' }}</small>
                                    </div>
                                </td>
                                <td><span class="text-sm text-secondary-light fw-medium">{{ $variant->sku }}</span></td>
                                <td><span class="fw-bold text-primary-600">INR
                                        {{ number_format((float) $variant->price, 2) }}</span></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span
                                            class="text-sm fw-bold {{ ($variant->inventory?->stock_qty ?? 0) > 5 ? 'text-success-main' : 'text-danger-main' }}">
                                            Stock: {{ $variant->inventory?->stock_qty ?? 0 }}
                                        </span>
                                        @if($variant->inventory?->is_in_stock)
                                            <small class="text-secondary-light">In Stock</small>
                                        @else
                                            <small class="text-danger-main">Out of Stock</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @if($variant->is_active)
                                            <span class="badge bg-success-100 text-success-600 w-fit">Active</span>
                                        @else
                                            <span class="badge bg-danger-100 text-danger-600 w-fit">Inactive</span>
                                        @endif
                                        @if($variant->is_default)
                                            <span class="badge bg-info-100 text-info-600 w-fit">Default</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1 edit-btn"
                                            data-bs-toggle="modal" data-bs-target="#editVariantModal"
                                            data-id="{{ $variant->id }}" data-name="{{ $variant->name }}"
                                            data-sku="{{ $variant->sku }}" data-price="{{ $variant->price }}"
                                            data-attributes='{{ $variant->attributes ? json_encode($variant->attributes) : "" }}'
                                            data-is_default="{{ $variant->is_default }}"
                                            data-is_active="{{ $variant->is_active }}"
                                            data-stock_qty="{{ $variant->inventory?->stock_qty ?? 0 }}"
                                            data-low_stock_threshold="{{ $variant->inventory?->low_stock_threshold ?? 5 }}"
                                            data-track_stock="{{ $variant->inventory?->track_stock ?? 1 }}"
                                            data-is_in_stock="{{ $variant->inventory?->is_in_stock ?? 1 }}"
                                            data-action="{{ route('admin.ecommerce.variants.update', $variant) }}">
                                            <iconify-icon icon="lucide:edit"></iconify-icon> Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.ecommerce.variants.destroy', $variant) }}"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1"
                                                onclick="return confirm('Delete this variant?')">
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

    <!-- Edit Variant Modal -->
    <div class="modal fade" id="editVariantModal" tabindex="-1" aria-labelledby="editVariantModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editVariantModalLabel">Edit Product Variant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editVariantForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Variant Name</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="lucide:type"></iconify-icon>
                                    </span>
                                    <input type="text" name="name" id="edit_variant_name" class="form-control"
                                        placeholder="e.g. 500g" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SKU</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="ant-design:barcode-outlined"></iconify-icon>
                                    </span>
                                    <input type="text" name="sku" id="edit_variant_sku" class="form-control"
                                        placeholder="Auto generated if empty">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Price (INR)</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="lucide:indian-rupee"></iconify-icon>
                                    </span>
                                    <input type="number" step="0.01" min="0" name="price" id="edit_variant_price"
                                        class="form-control" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Attributes (JSON)</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="lucide:settings-2"></iconify-icon>
                                    </span>
                                    <input type="text" name="attributes" id="edit_variant_attributes" class="form-control">
                                </div>
                            </div>

                            <hr class="my-3">
                            <h6>Stock Management</h6>

                            <div class="col-md-6">
                                <label class="form-label">Current Stock</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="solar:box-linear"></iconify-icon>
                                    </span>
                                    <input type="number" name="stock_qty" id="edit_variant_stock_qty" class="form-control"
                                        min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Low Stock Threshold</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="solar:bell-bing-linear"></iconify-icon>
                                    </span>
                                    <input type="number" name="low_stock_threshold" id="edit_variant_low_stock_threshold"
                                        class="form-control" min="0">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-check form-switch mb-8">
                                    <input type="hidden" name="is_default" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1"
                                        id="edit_variant_is_default">
                                    <label class="form-check-label" for="edit_variant_is_default">Default Variant</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch mb-8">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                        id="edit_variant_is_active">
                                    <label class="form-check-label" for="edit_variant_is_active">Active</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch mb-8">
                                    <input type="hidden" name="track_stock" value="0">
                                    <input class="form-check-input" type="checkbox" name="track_stock" value="1"
                                        id="edit_variant_track_stock">
                                    <label class="form-check-label" for="edit_variant_track_stock">Track Inventory</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch mb-8">
                                    <input type="hidden" name="is_in_stock" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_in_stock" value="1"
                                        id="edit_variant_is_in_stock">
                                    <label class="form-check-label" for="edit_variant_is_in_stock">In Stock</label>
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
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize DataTable
            if (document.getElementById('dataTable')) {
                new DataTable('#dataTable');
            }

            const editModal = document.getElementById('editVariantModal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;

                    // Extract info from data-* attributes
                    const action = button.getAttribute('data-action');
                    const name = button.getAttribute('data-name');
                    const sku = button.getAttribute('data-sku');
                    const price = button.getAttribute('data-price');
                    const attributes = button.getAttribute('data-attributes');
                    const isDefault = button.getAttribute('data-is_default');
                    const isActive = button.getAttribute('data-is_active');
                    const stockQty = button.getAttribute('data-stock_qty');
                    const lowStockThreshold = button.getAttribute('data-low_stock_threshold');
                    const trackStock = button.getAttribute('data-track_stock');
                    const isInStock = button.getAttribute('data-is_in_stock');

                    // Update the modal's content.
                    const form = editModal.querySelector('#editVariantForm');
                    form.setAttribute('action', action);

                    editModal.querySelector('#edit_variant_name').value = name;
                    editModal.querySelector('#edit_variant_sku').value = sku;
                    editModal.querySelector('#edit_variant_price').value = price;
                    editModal.querySelector('#edit_variant_attributes').value = attributes;

                    editModal.querySelector('#edit_variant_is_default').checked = isDefault == '1';
                    editModal.querySelector('#edit_variant_is_active').checked = isActive == '1';

                    editModal.querySelector('#edit_variant_stock_qty').value = stockQty;
                    editModal.querySelector('#edit_variant_low_stock_threshold').value = lowStockThreshold;
                    editModal.querySelector('#edit_variant_track_stock').checked = trackStock == '1';
                    editModal.querySelector('#edit_variant_is_in_stock').checked = isInStock == '1';
                });
            }
        });
    </script>
@endsection
