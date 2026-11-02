@extends('layouts.admin')
@php
    $title = 'Products';
    $subTitle = 'Ecommerce / Products';
@endphp

@section('content')
    @include('admin.partials.messages')



    <div class="card basic-data-table border-0 radius-12 mb-24">
        <div class="card-header bg-base border-bottom py-16 px-24 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <h5 class="card-title mb-0">Product List</h5>
                <form action="{{ route('admin.ecommerce.products.index') }}" method="GET" class="d-flex align-items-center gap-2">
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
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.ecommerce.products.trash') }}" class="btn btn-sm btn-outline-danger-600">
                    <i class="fas fa-trash"></i> Trash
                    @if(($trashCount ?? 0) > 0)
                        <span class="badge bg-danger-600 text-white ms-1">{{ $trashCount }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.ecommerce.products.create') }}" class="btn btn-sm btn-primary-600"><i class="ri-add-line"></i> Add New Product</a>
            </div>
        </div>
        <div class="card-body p-24">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Details</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status/Stock</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>
                                    @php
                                        $primaryImage = $product->images->where('is_primary', true)->first() ?? $product->images->first();
                                    @endphp
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $primaryImage ? asset('storage/' . $primaryImage->image_path) : asset('assets/images/logo-icon.png') }}" 
                                            alt="" class="w-48-px h-48-px radius-8 border flex-shrink-0 object-fit-cover">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-md fw-semibold text-dark mb-0">{{ $product->name }}</span>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($product->is_variant_enabled)
                                                <span class="badge text-xs bg-info-100 text-info-600 px-2 fw-medium">Variants ({{ $product->variants->count() }})</span>
                                            @endif
                                            @if($product->is_featured)
                                                <span class="badge text-xs bg-warning-100 text-warning-600 px-2 fw-medium">Featured</span>
                                            @endif
                                        </div>
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
                                    <div class="fw-bold text-primary-600">INR {{ number_format((float) $product->display_price, 0) }}</div>
                                    @if($product->display_compare_price > $product->display_price)
                                        <small class="text-decoration-line-through text-secondary-light">INR {{ number_format((float) $product->display_compare_price, 0) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @if($product->is_active)
                                            <span class="badge bg-success-100 text-success-600 w-fit">Active</span>
                                        @else
                                            <span class="badge bg-danger-100 text-danger-600 w-fit">Inactive</span>
                                        @endif
                                        <small class="text-xs {{ ($product->inventory?->stock_qty ?? 0) > 10 ? 'text-success-main' : 'text-danger-main' }} fw-bold">
                                            Stock: {{ $product->inventory?->stock_qty ?? 0 }}
                                        </small>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <a href="{{ route('admin.ecommerce.products.edit', $product) }}" class="btn btn-sm btn-outline-primary-600 radius-8 d-inline-flex align-items-center gap-1">
                                            <iconify-icon icon="lucide:edit"></iconify-icon> Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.ecommerce.products.destroy', $product) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1" onclick="return confirm('Move this product to trash?')">
                                                <i class="fas fa-trash"></i> Delete
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
@endsection

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('dataTable')) {
            new DataTable('#dataTable');
        }
    });
</script>
