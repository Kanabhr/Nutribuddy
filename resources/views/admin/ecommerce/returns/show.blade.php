@extends('layouts.admin')
@php
    $title = 'Return Details';
    $subTitle = 'Ecommerce / Returns / #'.$orderReturn->return_number;
@endphp

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-24">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Return Request Information</h5>
                    <span class="badge {{ $orderReturn->status == 'pending' ? 'bg-warning-focus text-warning-main' : ($orderReturn->status == 'completed' ? 'bg-success-focus text-success-main' : 'bg-info-focus text-info-main') }} px-16 py-4 radius-4">
                        {{ strtoupper($orderReturn->status) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-4 mb-32">
                        <div class="col-md-6">
                            <h6 class="text-secondary-light fw-medium mb-8">Return Number:</h6>
                            <p class="mb-0 text-primary-600 fw-bold">#{{ $orderReturn->return_number }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-secondary-light fw-medium mb-8">Order Associated:</h6>
                            <a href="{{ $orderReturn->order_id ? route('admin.ecommerce.orders.show', $orderReturn->order_id) : '#' }}" class="mb-0 fw-bold text-dark">#{{ $orderReturn->order?->order_number ?? 'N/A' }}</a>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-secondary-light fw-medium mb-8">Refund Amount:</h6>
                            <p class="mb-0 fw-bold">INR {{ number_format($orderReturn->refund_amount, 2) }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-secondary-light fw-medium mb-8">Return Quantity:</h6>
                            <p class="mb-0 fw-bold">{{ $orderReturn->items->sum('quantity') }}</p>
                        </div>
                        <div class="col-12">
                            <h6 class="text-secondary-light fw-medium mb-8">Reason for Return:</h6>
                            <div class="p-16 radius-8 bg-light border">
                                {{ $orderReturn->reason }}
                            </div>
                        </div>
                        @if(!empty($orderReturn->media_paths) && count($orderReturn->media_paths) > 0)
                        <div class="col-12">
                            <h6 class="text-secondary-light fw-medium mb-8">Uploaded Media (Evidence):</h6>
                            <div class="d-flex flex-wrap gap-16 p-16 radius-8 bg-light border">
                                @foreach($orderReturn->media_paths as $media)
                                    @php
                                        $ext = strtolower(pathinfo($media, PATHINFO_EXTENSION));
                                    @endphp
                                    <div class="position-relative">
                                        @if(in_array($ext, ['mp4', 'mov', 'avi']))
                                            <video width="200" height="150" controls class="radius-8 border" style="object-fit: cover;">
                                                <source src="{{ asset('storage/' . $media) }}" type="video/{{ $ext == 'mov' ? 'quicktime' : $ext }}">
                                                Your browser does not support the video tag.
                                            </video>
                                        @else
                                            <a href="{{ asset('storage/' . $media) }}" target="_blank">
                                                <img src="{{ asset('storage/' . $media) }}" alt="Return Media" class="radius-8 border" style="width: 200px; height: 150px; object-fit: cover;">
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @if ($orderReturn->admin_note)
                            <div class="col-12">
                                <h6 class="text-secondary-light fw-medium mb-8">Admin Note:</h6>
                                <div class="p-16 radius-8 bg-light border">
                                    {{ $orderReturn->admin_note }}
                                </div>
                            </div>
                        @endif
                    </div>

                    @php
                        $customerName = $orderReturn->order?->user?->name ?? $orderReturn->order?->customer_name ?? 'Guest Customer';
                        $customerEmail = $orderReturn->order?->user?->email ?? $orderReturn->order?->customer_email ?? 'N/A';
                        $avatarInitial = strtoupper(substr(trim($customerName), 0, 1) ?: 'C');
                    @endphp
                    <h6 class="text-md fw-bold mb-16">Customer Information</h6>
                    <div class="d-flex align-items-center gap-3 p-16 radius-8 border mb-24">
                        <div class="w-48-px h-48-px radius-circle bg-primary-100 d-flex align-items-center justify-content-center text-primary-600 fw-bold">
                            {{ $avatarInitial }}
                        </div>
                        <div>
                            <h6 class="mb-0 fw-medium">{{ $customerName }}</h6>
                            <p class="mb-0 text-sm text-secondary-light">{{ $customerEmail }}</p>
                        </div>
                    </div>

                    <h6 class="text-md fw-bold mb-16">Requested Return Items</h6>
                    <div class="table-responsive mb-24">
                        <table class="table bordered-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Unit Price</th>
                                    <th>Return Qty</th>
                                    <th class="text-end">Suggested Refund</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orderReturn->items as $returnItem)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @php
                                                    $productImage = asset('assets/images/logo-icon.png');
                                                    if ($returnItem->orderItem && $returnItem->orderItem->product && $returnItem->orderItem->product->images->first()) {
                                                        $productImage = asset('storage/' . $returnItem->orderItem->product->images->first()->image_path);
                                                    }
                                                @endphp
                                                <img src="{{ $productImage }}" class="w-40-px h-40-px radius-8" alt="Product Image">
                                                <div class="d-flex flex-column">
                                                    <span class="fw-medium text-dark">{{ $returnItem->product_name }}</span>
                                                    @if($returnItem->sku)
                                                        <small class="text-secondary-light">{{ $returnItem->sku }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>INR {{ number_format($returnItem->unit_price, 2) }}</td>
                                        <td>{{ $returnItem->quantity }}</td>
                                        <td class="text-end fw-bold text-dark">INR {{ number_format($returnItem->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <h6 class="text-md fw-bold mb-16">Items in Original Order</h6>
                    <div class="table-responsive">
                        <table class="table bordered-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Ordered Qty</th>
                                    <th>Returned/Requested Qty</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orderReturn->order->items as $item)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ !empty($item->product->images->first()) ? asset('storage/'.$item->product->images->first()->image_path) : asset('assets/images/logo-icon.png') }}" class="w-40-px h-40-px radius-8">
                                                <div class="d-flex flex-column">
                                                    <span class="fw-medium text-dark">{{ $item->product_name }}</span>
                                                    @php
                                                        $vName = $item->item_snapshot['variant_name'] ?? ($item->productVariant?->name ?? null);
                                                    @endphp
                                                    @if($vName)
                                                        <small class="text-secondary-light" style="font-size: 0.75rem;">{{ $vName }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>INR {{ number_format($item->unit_price, 2) }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>
                                            {{ $item->returnItems->filter(fn ($returnItem) => in_array($returnItem->returnRequest?->status, ['pending', 'approved', 'completed'], true))->sum('quantity') }}
                                        </td>
                                        <td class="text-end fw-bold text-dark">INR {{ number_format($item->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-24 sticky-top" style="top: 24px; z-index: 10;">
                <div class="card-header">
                    <h5 class="card-title mb-0">Moderation / Refund</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.ecommerce.order-returns.update', $orderReturn) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-24">
                            <label class="form-label">Return Status</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" {{ $orderReturn->status == 'pending' ? 'selected' : '' }}>Pending Approval</option>
                                <option value="approved" {{ $orderReturn->status == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ $orderReturn->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="completed" {{ $orderReturn->status == 'completed' ? 'selected' : '' }}>Refund Processed (Completed)</option>
                            </select>
                        </div>

                        <div class="mb-24">
                            <label class="form-label">Refund Amount (INR)</label>
                            <div class="icon-field">
                                <span class="icon"><iconify-icon icon="lucide:indian-rupee"></iconify-icon></span>
                                <input type="number" step="0.01" name="refund_amount" class="form-control" value="{{ $orderReturn->refund_amount }}" required>
                            </div>
                            <small class="text-secondary-light">Suggested refund is based on the order grand total: INR {{ number_format($orderReturn->order?->grand_total ?? 0, 2) }}</small>
                        </div>

                        <div class="mb-24">
                            <label class="form-label">Admin Internal Note</label>
                            <textarea name="admin_note" class="form-control" rows="4" placeholder="Enter notes about the refund or rejection reason...">{{ $orderReturn->admin_note }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary-600 w-100">Update Return Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
