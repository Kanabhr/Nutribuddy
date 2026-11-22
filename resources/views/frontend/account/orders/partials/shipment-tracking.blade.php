{{-- Customer order page: courier, AWB and tracking steps. Expects $order. Hidden until an AWB exists. --}}
@php
    use App\Models\OrderShipmentEvent;
    use App\Services\Shipping\ShipmentService;

    $awb = $order->awb_code;
    $trackingUrl = ShipmentService::trackingUrl($order);
    $shipmentEvents = $awb
        ? OrderShipmentEvent::where('order_id', $order->id)->orderByDesc('occurred_at')->orderByDesc('id')->limit(8)->get()
        : collect();
@endphp

@if ($awb && $order->status !== 'cancelled')
    <div class="orders-card fade-in d2 order-detail-card mb-24">
        <h3 class="card-title order-card-title">Shipment Tracking</h3>
        <div class="order-card-text">
            <strong class="order-card-strong">{{ ShipmentService::customerLabel($order->shiprocket_status) }}</strong>
            @if ($order->courier_name)Courier: {{ $order->courier_name }}<br>@endif
            <strong>Tracking number (AWB):</strong> {{ $awb }}<br>
            @if ($trackingUrl)
                <a href="{{ $trackingUrl }}" target="_blank" rel="noopener noreferrer">Track your package</a>
            @endif
        </div>

        @if ($shipmentEvents->isNotEmpty())
            <div class="order-timeline">
                @foreach ($shipmentEvents as $event)
                    <div class="timeline-item timeline-item-spaced">
                        <div class="timeline-time timeline-time-styled">
                            {{ optional($event->occurred_at)->format('d M Y, h:i A') }}
                        </div>
                        <div class="timeline-content">
                            <strong class="timeline-status-strong">{{ \Illuminate\Support\Str::title(strtolower($event->status)) }}</strong>
                            @if ($event->activity)<p class="timeline-narration">{{ $event->activity }}</p>@endif
                            @if ($event->location)<p class="timeline-narration">{{ $event->location }}</p>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif