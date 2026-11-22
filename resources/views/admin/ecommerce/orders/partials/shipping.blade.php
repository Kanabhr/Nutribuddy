{{-- Admin order page: Shiprocket shipping card. Expects $order. --}}
@php
    use App\Models\OrderShipmentEvent;
    use App\Services\Shipping\ShipmentService;

    $kind = ShipmentService::classify($order->shiprocket_status);
    $pushed = filled($order->shiprocket_order_id);
    $hasAwb = filled($order->awb_code);
    $closed = in_array($kind, ['delivered', 'cancelled'], true);
    $canManage = auth('admin')->user()?->can('manage orders') ?? false;
    $trackingUrl = ShipmentService::trackingUrl($order);
    $labelUrl = \Illuminate\Support\Str::startsWith((string) $order->shiprocket_label_url, ['https://', 'http://']) ? $order->shiprocket_label_url : null;
    $events = OrderShipmentEvent::where('order_id', $order->id)->orderByDesc('occurred_at')->orderByDesc('id')->limit(15)->get();
    $badge = match ($kind) {
        'delivered' => 'success',
        'out_for_delivery', 'in_transit', 'picked_up' => 'info',
        'ndr', 'rto', 'cancelled' => 'danger',
        default => 'warning',
    };
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Shipping (Shiprocket)</h5>
        @if ($order->shiprocket_status)
            <span class="badge bg-{{ $badge }}-100 text-{{ $badge }}-600">{{ $order->shiprocket_status }}</span>
        @endif
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4"><strong>Shiprocket order:</strong> {{ $order->shiprocket_order_id ?: 'Not sent yet' }}</div>
            <div class="col-md-4"><strong>Shipment:</strong> {{ $order->shiprocket_shipment_id ?: 'N/A' }}</div>
            <div class="col-md-4"><strong>Courier:</strong> {{ $order->courier_name ?: 'Not assigned' }}</div>
            <div class="col-md-4">
                <strong>AWB:</strong>
                @if ($hasAwb)
                    <a href="{{ $trackingUrl }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 fw-semibold">{{ $order->awb_code }}</a>
                @else
                    Not assigned
                @endif
            </div>
            <div class="col-md-4">
                <strong>Pickup:</strong>
                {{ $order->shiprocket_pickup_at ? \Illuminate\Support\Carbon::parse($order->shiprocket_pickup_at)->format('d M Y') : 'Not scheduled' }}
            </div>
            <div class="col-md-4">
                <strong>Last update:</strong>
                {{ $order->shiprocket_status_at ? \Illuminate\Support\Carbon::parse($order->shiprocket_status_at)->format('d M Y H:i') : 'N/A' }}
            </div>
            @if ($labelUrl)
                <div class="col-12"><a href="{{ $labelUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary-600">Download label</a></div>
            @endif
        </div>

        @if ($canManage && $order->status !== 'cancelled')
            <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                @if (! $pushed)
                    <form method="POST" action="{{ route('admin.ecommerce.orders.shipping.push', $order) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary">Send to Shiprocket</button>
                    </form>
                @endif

                @if ($pushed && ! $hasAwb && ! $closed)
                    <form method="POST" action="{{ route('admin.ecommerce.orders.shipping.assign-awb', $order) }}" class="d-flex gap-2">
                        @csrf
                        <input type="number" name="courier_id" min="1" class="form-control form-control-sm" style="width: 160px" placeholder="Courier ID (optional)">
                        <button type="submit" class="btn btn-sm btn-primary">Assign courier &amp; AWB</button>
                    </form>
                @endif

                @if ($hasAwb && ! $closed)
                    <form method="POST" action="{{ route('admin.ecommerce.orders.shipping.pickup', $order) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary-600">Schedule pickup</button>
                    </form>
                    <form method="POST" action="{{ route('admin.ecommerce.orders.shipping.label', $order) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary-600">{{ $labelUrl ? 'Regenerate label' : 'Generate label' }}</button>
                    </form>
                @endif

                @if ($hasAwb)
                    <form method="POST" action="{{ route('admin.ecommerce.orders.shipping.track', $order) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Refresh tracking</button>
                    </form>
                @endif

                @if ($pushed && ! $closed)
                    <form method="POST" action="{{ route('admin.ecommerce.orders.shipping.cancel', $order) }}" onsubmit="return confirm('Cancel this shipment on Shiprocket? This cannot be undone.')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger-600">Cancel shipment</button>
                    </form>
                @endif
            </div>

            @if ($hasAwb && ! $closed)
                <form method="POST" action="{{ route('admin.ecommerce.orders.shipping.ndr', $order) }}" class="row g-2 align-items-end mt-3 pt-3 border-top">
                    @csrf
                    <div class="col-12"><strong>Delivery attempt failed (NDR)?</strong>
                        <span class="text-secondary-light text-sm">Ask the courier to try again or send the parcel back.</span></div>
                    <div class="col-md-3">
                        <select name="action" class="form-select form-select-sm" required>
                            <option value="re-attempt">Re-attempt delivery</option>
                            <option value="return">Return to origin</option>
                        </select>
                    </div>
                    <div class="col-md-3"><input type="date" name="deferred_date" min="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm"></div>
                    <div class="col-md-4"><input type="text" name="comments" maxlength="255" class="form-control form-control-sm" placeholder="Comment for the courier"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-warning w-100">Send</button></div>
                </form>
            @endif
        @endif

        <div class="{{ $canManage && $order->status !== 'cancelled' ? 'mt-4' : '' }}">
            <strong class="d-block mb-2">Tracking history</strong>
            @forelse ($events as $event)
                <div class="mb-2 pb-2 border-bottom">
                    <div>
                        <strong>{{ $event->status }}</strong>
                        <span class="text-secondary-light text-sm">
                            {{ optional($event->occurred_at)->format('d M Y H:i') ?? optional($event->created_at)->format('d M Y H:i') }}
                            · {{ $event->source }}
                        </span>
                    </div>
                    @if ($event->activity)<div class="text-sm">{{ $event->activity }}</div>@endif
                    @if ($event->location)<div class="text-sm text-secondary-light">{{ $event->location }}</div>@endif
                </div>
            @empty
                <p class="text-secondary-light mb-0">No tracking updates yet.</p>
            @endforelse
        </div>
    </div>
</div>