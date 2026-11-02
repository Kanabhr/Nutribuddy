@extends('layouts.admin')
@php
    $title = 'Product Trash';
    $subTitle = 'Ecommerce / Products / Trash';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="card basic-data-table border-0 radius-12 mb-24">
        <div class="card-header bg-base border-bottom py-16 px-24 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <h5 class="card-title mb-0">Trash Products</h5>
                <form action="{{ route('admin.ecommerce.products.trash') }}" method="GET" class="d-flex align-items-center gap-2">
                    <select name="category_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
            <a href="{{ route('admin.ecommerce.products.index') }}" class="btn btn-sm btn-outline-primary-600">
                <iconify-icon icon="lucide:arrow-left"></iconify-icon> Active Products
                <span class="badge bg-primary-600 text-white ms-1">{{ $activeCount ?? 0 }}</span>
            </a>
        </div>
        <div class="card-body p-24">
            <form method="POST" action="{{ route('admin.ecommerce.products.bulk-force-destroy') }}" id="bulkDeleteForm" class="d-none">
                @csrf
                @method('DELETE')
            </form>
            <div class="d-flex justify-content-end mb-16">
                <button type="submit" form="bulkDeleteForm" class="btn btn-sm btn-danger-600 d-inline-flex align-items-center gap-1" id="bulkDeleteBtn" disabled
                    onclick="return confirm('Permanently delete selected products? This cannot be undone.')">
                    <i class="fas fa-trash"></i> Permanent Delete Selected
                </button>
            </div>
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length="10">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" class="form-check-input" id="selectAllProducts" aria-label="Select all products">
                            </th>
                            <th>Image</th>
                            <th>Product Details</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Deleted At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input product-check" name="product_ids[]" value="{{ $product->id }}" form="bulkDeleteForm" aria-label="Select {{ $product->name }}">
                                </td>
                                <td>
                                    @php
                                        $primaryImage = $product->images->where('is_primary', true)->first() ?? $product->images->first();
                                    @endphp
                                    <img src="{{ $primaryImage ? asset('storage/' . $primaryImage->image_path) : asset('assets/images/logo-icon.png') }}"
                                        alt="" class="w-48-px h-48-px radius-8 border flex-shrink-0 object-fit-cover">
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-md fw-semibold text-dark mb-0">{{ $product->name }}</span>
                                        <small class="text-secondary-light">{{ $product->product_type ? ucfirst($product->product_type) : 'Product' }}</small>
                                    </div>
                                </td>
                                <td><span class="text-sm text-secondary-light fw-medium">{{ $product->sku }}</span></td>
                                <td>
                                    @if($product->category)
                                        <span class="badge bg-info-100 text-info-600">{{ $product->category->name }}</span>
                                    @else
                                        <span class="badge bg-secondary-100 text-secondary-600">Uncategorized</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold text-primary-600">INR {{ number_format((float) $product->base_price, 2) }}</div>
                                </td>
                                <td>
                                    <span class="text-sm text-secondary-light">
                                        {{ $product->deleted_at?->format('d M Y, h:i A') }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <form method="POST" action="{{ route('admin.ecommerce.products.restore', $product->id) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1">
                                                <iconify-icon icon="lucide:rotate-ccw"></iconify-icon> Restore
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.ecommerce.products.force-destroy', $product->id) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1"
                                                onclick="return confirm('Permanently delete this product? This cannot be undone.')">
                                                <i class="fas fa-trash"></i> Permanent Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-secondary-light py-32">Trash is empty.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('dataTable')) {
            new DataTable('#dataTable');
        }

        const selectAll = document.getElementById('selectAllProducts');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

        function productChecks() {
            return Array.from(document.querySelectorAll('.product-check'));
        }

        function syncBulkState() {
            const checks = productChecks();
            const checkedCount = checks.filter((check) => check.checked).length;
            bulkDeleteBtn.disabled = checkedCount === 0;
            if (selectAll) {
                selectAll.checked = checks.length > 0 && checkedCount === checks.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < checks.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                productChecks().forEach((check) => {
                    check.checked = selectAll.checked;
                });
                syncBulkState();
            });
        }

        document.addEventListener('change', function(event) {
            if (event.target.classList.contains('product-check')) {
                syncBulkState();
            }
        });

        syncBulkState();
    });
</script>
