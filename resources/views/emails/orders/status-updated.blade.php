<p>Hello {{ $order->customer_name }},</p>
<p><strong>{{ $headline }}</strong></p>
<p>Your order <strong>{{ $order->order_number }}</strong> is now <strong>{{ strtoupper($statusLabel) }}</strong>.</p>
<p>{{ $messageLine }}</p>
<p>Fulfillment: {{ strtoupper($order->fulfillment_status) }}</p>
@if ($order->payment_method === 'cod')
<p>Payment method: CASH ON DELIVERY.</p>
@endif
