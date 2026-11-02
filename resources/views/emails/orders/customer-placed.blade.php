<p>Hello {{ $order->customer_name }},</p>
<p>Your order <strong>{{ $order->order_number }}</strong> has been placed successfully.</p>
<p><strong>Order Items:</strong></p>
<table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
    <thead>
        <tr>
            <th style="border-bottom: 1px solid #ccc; text-align: left; padding: 5px;">Item</th>
            <th style="border-bottom: 1px solid #ccc; text-align: center; padding: 5px;">Qty</th>
            <th style="border-bottom: 1px solid #ccc; text-align: right; padding: 5px;">Price</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
            @php
                $vName = $item->item_snapshot['variant_name'] ?? ($item->productVariant?->name ?? null);
            @endphp
            <tr>
                <td style="border-bottom: 1px solid #eee; padding: 5px;">
                    <div>{{ $item->product_name }}</div>
                    @if($vName)
                        <div style="font-size: 12px; color: #666;">Variant: {{ $vName }}</div>
                    @endif
                </td>
                <td style="border-bottom: 1px solid #eee; text-align: center; padding: 5px;">{{ $item->quantity }}</td>
                <td style="border-bottom: 1px solid #eee; text-align: right; padding: 5px;">INR {{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<p>Order total: <strong>INR {{ number_format($order->grand_total, 2) }}</strong></p>
<p>You can track this order in your user panel.</p>
