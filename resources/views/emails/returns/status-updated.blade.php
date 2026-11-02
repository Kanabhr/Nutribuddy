<p>Hello {{ $orderReturn->order?->customer_name ?? 'Customer' }},</p>
<p>Your return request <strong>{{ $orderReturn->return_number }}</strong> is now <strong>{{ strtoupper($orderReturn->status) }}</strong>.</p>
@if($orderReturn->admin_note)
    <p>Note: {{ $orderReturn->admin_note }}</p>
@endif
@if($orderReturn->refund_amount > 0)
    <p>Refund amount: INR {{ number_format($orderReturn->refund_amount, 2) }}</p>
@endif
