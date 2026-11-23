{{-- Admin order page: gateway transactions vs manual payment adjustments (point 33). Expects $order with payments loaded. --}}
@php
    use App\Models\OrderPaymentAdjustment;
    use App\Services\Ecommerce\PaymentAdjustmentService;

    $adjustmentService = app(PaymentAdjustmentService::class);
    $adjustments = OrderPaymentAdjustment::where('order_id', $order->id)->orderByDesc('id')->get();
    $refundable = $adjustmentService->refundable($order);
    $canAdjust = (auth('admin')->user()?->can('manage administration') ?? false) && (auth('admin')->user()?->can('manage orders') ?? false);
    $canMarkPaid = in_array($order->payment_status, ['pending', 'failed'], true) && $order->status !== 'cancelled';
    $canRefund = in_array($order->payment_status, ['paid', 'partially_refunded'], true) && $refundable > 0;
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Payment</h5>
    </div>
    <div class="card-body">
        <div class="mb-4">
            <strong class="d-block mb-2">Gateway and COD transactions</strong>
            <div class="table-responsive">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr><th>Provider</th><th>Type</th><th>Status</th><th>Amount</th><th>Gateway reference</th><th>Paid at</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($order->payments as $payment)
                            <tr>
                                <td>{{ strtoupper($payment->provider) }}</td>
                                <td>{{ $payment->transaction_type ?: '-' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $payment->status)) }}</td>
                                <td>INR {{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="text-break">{{ $payment->gateway_payment_id ?: '-' }}</td>
                                <td>{{ optional($payment->paid_at)->format('d M Y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-secondary-light py-3">No gateway transactions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mb-4">
            <strong class="d-block mb-2">Manual adjustments</strong>
            <div class="table-responsive">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr><th>When</th><th>Adjustment</th><th>Amount</th><th>Status change</th><th>By</th><th>Reference</th><th>Reason</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($adjustments as $adjustment)
                            <tr>
                                <td class="text-nowrap">{{ optional($adjustment->created_at)->format('d M Y H:i') }}</td>
                                <td>{{ $adjustment->typeLabel() }}</td>
                                <td>INR {{ number_format((float) $adjustment->amount, 2) }}</td>
                                <td class="text-nowrap">{{ ucfirst(str_replace('_', ' ', $adjustment->from_payment_status)) }} &rarr; {{ ucfirst(str_replace('_', ' ', $adjustment->to_payment_status)) }}</td>
                                <td>{{ $adjustment->adjusted_by_name }}</td>
                                <td class="text-break">{{ $adjustment->reference }}</td>
                                <td>{{ $adjustment->reason }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-secondary-light py-3">No manual adjustments.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($canAdjust)
            @if ($canMarkPaid || $canRefund)
                <form method="POST" action="{{ route('admin.ecommerce.orders.payment-adjustments.store', $order) }}" class="border-top pt-3" id="paymentAdjustmentForm">
                    @csrf
                    <strong class="d-block mb-2">Record a manual adjustment</strong>
                    <p class="text-sm text-secondary-light">
                        This only records a change. It does not move money: refund through the gateway or your bank first, then enter that reference here.
                        Marking an order as paid also awards NB Coins and activates diet plans, the same as a gateway payment.
                    </p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Adjustment</label>
                            <select name="type" id="adjustmentType" class="form-select" required>
                                @if ($canMarkPaid)
                                    <option value="mark_paid" @selected(old('type') === 'mark_paid')>Mark as paid (INR {{ number_format((float) $order->grand_total, 2) }})</option>
                                @endif
                                @if ($canRefund)
                                    <option value="refund" @selected(old('type') === 'refund')>Full refund (INR {{ number_format($refundable, 2) }})</option>
                                    <option value="partial_refund" @selected(old('type') === 'partial_refund')>Partial refund</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-4 d-none" id="adjustmentAmountWrap">
                            <label class="form-label">Refund amount (INR)</label>
                            <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-control" placeholder="Less than {{ number_format($refundable, 2, '.', '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reference</label>
                            <input type="text" name="reference" maxlength="100" value="{{ old('reference') }}" class="form-control" placeholder="Bank UTR, gateway refund id or receipt no." required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" rows="2" maxlength="1000" class="form-control" placeholder="Why is this change needed? (min 10 characters)" required>{{ old('reason') }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="confirm" value="1" id="adjustmentConfirm" required>
                                <label class="form-check-label" for="adjustmentConfirm">I confirm the money has really been received or returned.</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Save adjustment</button>
                        </div>
                    </div>
                    @if ($errors->any())
                        <div class="alert alert-danger mt-3 mb-0">{{ $errors->first() }}</div>
                    @endif
                </form>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var type = document.getElementById('adjustmentType');
                        var wrap = document.getElementById('adjustmentAmountWrap');
                        if (!type || !wrap) return;
                        function toggle() { wrap.classList.toggle('d-none', type.value !== 'partial_refund'); }
                        type.addEventListener('change', toggle);
                        toggle();
                    });
                </script>
            @else
                <p class="text-secondary-light mb-0 border-top pt-3">No manual adjustment is possible for a payment that is {{ str_replace('_', ' ', $order->payment_status) }}.</p>
            @endif
        @endif
    </div>
</div>