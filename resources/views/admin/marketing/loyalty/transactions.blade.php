@extends('layouts.admin')
@php
    $title = 'Coin Transactions';
    $subTitle = 'Monitor all NB Coins earning and spending history';
@endphp

@section('content')
<div class="card basic-data-table">
    <div class="card-header">
        <h5 class="card-title mb-0">Transaction History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table bordered-table mb-0" id="dataTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Order Ref</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $trx)
                    <tr>
                        <td>{{ $trx->created_at->format('d M Y, h:i A') }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="w-32-px h-32-px bg-primary-100 text-primary-600 rounded-circle d-flex align-items-center justify-content-center fw-bold">
                                    {{ substr($trx->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $trx->user->name ?? 'Unknown User' }}</div>
                                    <small class="text-secondary-light">{{ $trx->user->email ?? '' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($trx->type === 'earned')
                                <span class="badge bg-success-100 text-success-600">EARNED</span>
                            @else
                                <span class="badge bg-danger-100 text-danger-600">SPENT</span>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold {{ $trx->type === 'earned' ? 'text-success-600' : 'text-danger-600' }}">
                                {{ $trx->type === 'earned' ? '+' : '-' }}{{ $trx->amount }}
                            </span>
                        </td>
                        <td>{{ $trx->description }}</td>
                        <td>
                            @if($trx->order)
                                <a href="{{ route('admin.ecommerce.orders.show', $trx->order) }}" class="text-primary-600 fw-medium">
                                    #{{ $trx->order->order_number }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('dataTable')) {
            // We use simple bootstrap pagination for this view as provided by $transactions->links()
            // but the table can still benefit from dataTable styles
        }
    });
</script>
@endpush
