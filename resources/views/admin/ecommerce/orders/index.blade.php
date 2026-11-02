@extends('layouts.admin')
@php
    $title = 'Orders';
    $subTitle = 'Ecommerce / Orders';
@endphp

@section('content')
    <style>
        .order-money-stack {
            min-width: 170px;
        }

        .order-money-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 12px;
            line-height: 1.35;
        }

        .order-money-label {
            color: #6b7280;
        }

        .order-items-preview {
            min-width: 240px;
            max-width: 360px;
        }
    </style>

    @include('admin.partials.messages')

    <div class="card border-0 radius-12 mb-24">
        <div class="card-body p-24">
            <form method="GET" action="{{ route('admin.ecommerce.orders.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Filter by Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" {{ $selectedStatus === $status ? 'selected' : '' }}>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary" type="submit">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 radius-12 mb-24">
        <div class="card-header bg-base border-bottom py-16 px-24">
            <h5 class="card-title mb-0">Order List</h5>
        </div>
        <div class="card-body p-24">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable">
                    <thead>
                        <tr>
                            <th>Order Details</th>
                            <th>Customer</th>
                            <th>Status/Payment</th>
                            <th>Amount Summary</th>
                            <th>Items Ordered</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @php
                                $grossTotal = (float) $order->subtotal + (float) $order->tax_total + (float) $order->shipping_total;
                            @endphp
                            <tr>
                                <td data-order="{{ optional($order->placed_at ?? $order->created_at)->timestamp ?? 0 }}">
                                    <div class="d-flex flex-column">
                                        <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="text-md fw-bold text-primary-600 hover-text-primary-700">#{{ $order->order_number }}</a>
                                        <small class="text-secondary-light">{{ optional($order->placed_at)->format('d M Y, H:i') ?? $order->created_at->format('d M Y, H:i') }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-md fw-semibold text-dark">{{ $order->customer_name }}</span>
                                        <small class="text-secondary-light">{{ $order->customer_phone }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @php
                                            $statusClass = match(strtolower($order->status)) {
                                                'completed', 'delivered' => 'success',
                                                'shipped', 'processing' => 'info',
                                                'cancelled' => 'danger',
                                                default => 'warning'
                                            };
                                            $paymentClass = strtolower($order->payment_status) == 'paid' ? 'success' : 'warning';
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}-100 text-{{ $statusClass }}-600 w-fit">{{ ucfirst($order->status) }}</span>
                                        <span class="badge bg-{{ $paymentClass }}-100 text-{{ $paymentClass }}-600 w-fit">{{ ucfirst($order->payment_status) }} ({{ strtoupper($order->payment_method) }})</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="order-money-stack d-flex flex-column gap-1">
                                        <div class="order-money-row">
                                            <span class="order-money-label">Items + tax + ship</span>
                                            <span>INR {{ number_format($grossTotal, 2) }}</span>
                                        </div>
                                        @if((float) $order->discount_total > 0)
                                            <div class="order-money-row text-success-600">
                                                <span>Coupon</span>
                                                <span>- INR {{ number_format((float) $order->discount_total, 2) }}</span>
                                            </div>
                                        @endif
                                        @if((int) $order->coins_redeemed > 0 || (float) $order->coin_discount > 0)
                                            <div class="order-money-row text-warning-600">
                                                <span>{{ number_format((int) $order->coins_redeemed) }} coins</span>
                                                <span>- INR {{ number_format((float) $order->coin_discount, 2) }}</span>
                                            </div>
                                        @endif
                                        <div class="pt-1 mt-1 border-top">
                                            <span class="fw-bold text-primary-600">Collect: INR {{ number_format((float) $order->grand_total, 2) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="order-items-preview">
                                    @foreach ($order->items->take(2) as $item)
                                        <div class="mb-2 pb-1 {{ !$loop->last ? 'border-bottom border-secondary-light border-dashed' : '' }}">
                                            <div class="d-flex align-items-start justify-content-between gap-2">
                                                <span class="text-xs fw-bold text-dark" style="line-height: 1.2;">{{ $item->product_name }}</span>
                                                <span class="badge bg-secondary-100 text-secondary-600 text-xs">x{{ $item->quantity }}</span>
                                            </div>
                                            
                                            @php
                                                $vName = $item->item_snapshot['variant_name'] ?? ($item->productVariant?->name ?? null);
                                                $product = $item->product;
                                            @endphp
                                            
                                            @if($vName)
                                                <div class="text-secondary-light" style="font-size: 10px; line-height: 1.2;">{{ $vName }}</div>
                                            @endif
                                            
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                @if($product)
                                                    @if($product->flavor || $product->flavour)
                                                        <span class="badge bg-success-100 text-success-600" style="font-size: 9px; padding: 1px 3px;">{{ $product->flavor ?? $product->flavour }}</span>
                                                    @endif
                                                    @if($product->pack_size)
                                                        <span class="badge bg-info-100 text-info-600" style="font-size: 9px; padding: 1px 3px;">{{ $product->pack_size }}</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                    @if($order->items->count() > 2)
                                        <span class="badge bg-secondary-100 text-secondary-600 text-xs">
                                            +{{ $order->items->count() - 2 }} more item{{ $order->items->count() - 2 > 1 ? 's' : '' }}
                                        </span>
                                    @endif
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1 edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editOrderStatusModal"
                                            data-status="{{ $order->status }}"
                                            data-payment_status="{{ $order->payment_status }}"
                                            data-action="{{ route('admin.ecommerce.orders.update-status', $order) }}">
                                            <iconify-icon icon="lucide:edit"></iconify-icon> Status
                                        </button>
                                        <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="btn btn-sm btn-outline-primary-600 radius-8 d-inline-flex align-items-center gap-1">
                                            <iconify-icon icon="lucide:eye"></iconify-icon> View
                                        </a>
                                        <a href="{{ route('admin.ecommerce.invoices.show', $order) }}" class="btn btn-sm btn-outline-info-600 radius-8 d-inline-flex align-items-center gap-1">
                                            <iconify-icon icon="lucide:file-text"></iconify-icon> Invoice
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Order Status Modal -->
    <div class="modal fade" id="editOrderStatusModal" tabindex="-1" aria-labelledby="editOrderStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editOrderStatusModalLabel">Edit Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editOrderStatusForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Order Status</label>
                            <select name="status" id="edit_order_status" class="form-select" required>
                                @foreach ($statuses as $statusOption)
                                    <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Status</label>
                            <select name="payment_status" id="edit_payment_status" class="form-select" required>
                                @foreach ($paymentStatuses as $pStatusOption)
                                    <option value="{{ $pStatusOption }}">{{ ucfirst($pStatusOption) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('dataTable')) {
                new DataTable('#dataTable', {
                    order: [[0, 'desc']]
                });
            }

            const editModal = document.getElementById('editOrderStatusModal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    const action = button.getAttribute('data-action');
                    const status = button.getAttribute('data-status');
                    const paymentStatus = button.getAttribute('data-payment_status');

                    const form = editModal.querySelector('#editOrderStatusForm');
                    form.setAttribute('action', action);
                    
                    editModal.querySelector('#edit_order_status').value = status;
                    editModal.querySelector('#edit_payment_status').value = paymentStatus;
                });
            }
        });
    </script>
@endsection
