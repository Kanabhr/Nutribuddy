<?php

namespace App\Services\Shipping;

use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ShiprocketService
{
    private string $baseUrl;

    private string $email;

    private string $password;

    public function __construct()
    {
        $this->baseUrl = config('services.shiprocket.base_url', 'https://apiv2.shiprocket.in');
        $this->email = (string) (config('services.shiprocket.email') ?? '');
        $this->password = (string) (config('services.shiprocket.password') ?? '');
    }

    /**
     * Get a valid Shiprocket Bearer Token
     */
    private function getToken(): string
    {
        $cacheKey = 'shiprocket_token';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        if (empty($this->email) || empty($this->password)) {
            throw new Exception('Shiprocket credentials are not configured in .env (SHIPROCKET_EMAIL, SHIPROCKET_PASSWORD).');
        }

        $response = Http::post("{$this->baseUrl}/v1/external/auth/login", [
            'email' => $this->email,
            'password' => $this->password,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $token = $data['token'] ?? null;

            if ($token) {
                // Token is valid for 10 days, cache for 9 days to be safe
                Cache::put($cacheKey, $token, now()->addDays(9));

                return $token;
            }
        }

        Log::error('Shiprocket Authentication Failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new Exception('Failed to authenticate with Shiprocket API.');
    }

    /**
     * Authenticated call for the shipment actions below. Retries once with a fresh token on HTTP 401.
     *
     * @param  array<string, mixed>  $payload  Sent as JSON, or as the query string for GET.
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload = [], bool $retryAuth = true): array
    {
        $method = strtoupper($method);
        $url = rtrim($this->baseUrl, '/').'/v1/external/'.ltrim($path, '/');

        $response = Http::withToken($this->getToken())
            ->acceptJson()
            ->timeout(20)
            ->send($method, $url, $method === 'GET' ? ['query' => $payload] : ['json' => $payload]);

        if ($response->status() === 401 && $retryAuth) {
            Cache::forget('shiprocket_token');

            return $this->request($method, $path, $payload, false);
        }

        if ($response->failed()) {
            Log::error('Shiprocket request failed', [
                'path' => $path,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            throw new RuntimeException($this->errorMessage($response->json(), $response->status()));
        }

        return $response->json() ?? [];
    }

    private function errorMessage(mixed $json, int $status): string
    {
        $parts = [];

        if (is_array($json)) {
            if (isset($json['message']) && is_string($json['message'])) {
                $parts[] = $json['message'];
            }
            if (isset($json['errors']) && is_array($json['errors'])) {
                $first = collect($json['errors'])->flatten()->first();
                if (is_string($first)) {
                    $parts[] = $first;
                }
            }
        }

        return $parts !== []
            ? 'Shiprocket: '.implode(' ', $parts)
            : "Shiprocket request failed (HTTP {$status}).";
    }

    /**
     * Create an order on Shiprocket
     */
    public function createOrder(Order $order): array
    {
        $token = $this->getToken();

        // Convert Nutribuddy's 'cod' / 'razorpay' / 'cashfree' to Shiprocket's Prepaid / COD
        $isCod = strtolower($order->payment_method) === 'cod' || strtolower($order->payment_method) === 'cash_on_delivery';
        $paymentMethod = $isCod ? 'COD' : 'Prepaid';

        $orderItems = [];
        foreach ($order->items as $item) {
            $orderItems[] = [
                // product_name already includes the variant name ("Product - Variant").
                'name' => $item->product_name,
                // order_items stores the SKU in `sku` (the old code read a non-existent product_sku column).
                'sku' => $item->sku ?: 'SKU-'.$item->product_id,
                'units' => $item->quantity,
                'selling_price' => $item->unit_price,
                'discount' => '',
                'tax' => '',
                'hsn' => '',
            ];
        }

        $nameParts = explode(' ', $order->customer_name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '.';

        $shippingNameParts = explode(' ', $order->shipping_name, 2);
        $shippingFirstName = $shippingNameParts[0];
        $shippingLastName = $shippingNameParts[1] ?? '.';

        $payload = [
            'order_id' => $order->order_number,
            'order_date' => $order->created_at->format('Y-m-d H:i'),
            // Must match the pickup location name on the Shiprocket dashboard. Set SHIPROCKET_PICKUP_LOCATION in .env.
            'pickup_location' => config('services.shiprocket.pickup_location', 'work'),
            'channel_id' => '',
            'comment' => $order->customer_note ?? '',
            'billing_customer_name' => $firstName,
            'billing_last_name' => $lastName,
            'billing_address' => $order->shipping_address_line_1,
            'billing_address_2' => $order->shipping_address_line_2 ?? '',
            'billing_city' => $order->shipping_city,
            'billing_pincode' => $order->shipping_postal_code,
            'billing_state' => $order->shipping_state,
            'billing_country' => $order->shipping_country ?? 'India',
            'billing_email' => $order->customer_email,
            'billing_phone' => $order->customer_phone,

            // Shipping same as billing in our DB
            'shipping_is_billing' => true,
            'shipping_customer_name' => $shippingFirstName,
            'shipping_last_name' => $shippingLastName,
            'shipping_address' => $order->shipping_address_line_1,
            'shipping_address_2' => $order->shipping_address_line_2 ?? '',
            'shipping_city' => $order->shipping_city,
            'shipping_pincode' => $order->shipping_postal_code,
            'shipping_country' => $order->shipping_country ?? 'India',
            'shipping_state' => $order->shipping_state,
            'shipping_email' => $order->customer_email,
            'shipping_phone' => $order->shipping_phone,

            'order_items' => $orderItems,

            'payment_method' => $paymentMethod,
            'shipping_charges' => $order->shipping_total,
            'giftwrap_charges' => 0,
            'transaction_charges' => 0,
            'total_discount' => $order->discount_total + $order->coin_discount,
            'sub_total' => $order->grand_total,

            // Hardcoded defaults. Admin will edit on Shiprocket dashboard.
            'length' => 10,
            'breadth' => 10,
            'height' => 10,
            'weight' => 0.5,
        ];

        $response = Http::withToken($token)->post("{$this->baseUrl}/v1/external/orders/create/adhoc", $payload);

        if (! $response->successful()) {
            Log::error('Shiprocket Create Order Failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            throw new Exception('Failed to push order to Shiprocket. Check logs for details.');
        }

        return $response->json();
    }

    /**
     * Assign a courier and AWB to the order's shipment. Pass a courier id to force a courier, or null to let Shiprocket pick.
     *
     * @return array{awb_code: string, courier_name: ?string, courier_company_id: mixed}
     */
    public function assignAwb(Order $order, ?int $courierId = null): array
    {
        $payload = ['shipment_id' => (int) $order->shiprocket_shipment_id];
        if ($courierId) {
            $payload['courier_id'] = $courierId;
        }

        $json = $this->request('post', 'courier/assign/awb', $payload);
        $awb = data_get($json, 'response.data.awb_code');

        if ((int) data_get($json, 'awb_assign_status', 0) !== 1 || blank($awb)) {
            $reason = data_get($json, 'message');
            if (! is_string($reason) || $reason === '') {
                $reason = data_get($json, 'response.data.awb_assign_error');
            }

            throw new RuntimeException('Shiprocket: '.(is_string($reason) && $reason !== '' ? $reason : 'AWB could not be assigned.'));
        }

        return [
            'awb_code' => (string) $awb,
            'courier_name' => data_get($json, 'response.data.courier_name'),
            'courier_company_id' => data_get($json, 'response.data.courier_company_id'),
        ];
    }

    /** @return array{pickup_status: mixed, pickup_scheduled_date: mixed} */
    public function generatePickup(Order $order): array
    {
        $json = $this->request('post', 'courier/generate/pickup', [
            'shipment_id' => [(int) $order->shiprocket_shipment_id],
        ]);

        if (array_key_exists('pickup_status', $json) && (int) $json['pickup_status'] !== 1) {
            $reason = data_get($json, 'message');

            throw new RuntimeException('Shiprocket: '.(is_string($reason) && $reason !== '' ? $reason : 'Pickup could not be scheduled.'));
        }

        return [
            'pickup_status' => data_get($json, 'pickup_status'),
            'pickup_scheduled_date' => data_get($json, 'response.pickup_scheduled_date') ?? data_get($json, 'pickup_scheduled_date'),
        ];
    }

    public function generateLabel(Order $order): string
    {
        $json = $this->request('post', 'courier/generate/label', [
            'shipment_id' => [(int) $order->shiprocket_shipment_id],
        ]);

        $url = data_get($json, 'label_url');

        if ((int) data_get($json, 'label_created', 0) !== 1 || ! is_string($url) || $url === '') {
            $reason = data_get($json, 'message');

            throw new RuntimeException('Shiprocket: '.(is_string($reason) && $reason !== '' ? $reason : 'Label could not be generated. Assign an AWB first.'));
        }

        return $url;
    }

    /**
     * Fetch tracking for the order's AWB.
     *
     * @return array{status: ?string, etd: mixed, activities: list<array<string, mixed>>, error: mixed}
     */
    public function track(Order $order): array
    {
        $json = $this->request('get', 'courier/track/awb/'.rawurlencode((string) $order->awb_code));
        $data = data_get($json, 'tracking_data', []);

        $status = data_get($data, 'shipment_track.0.current_status');
        $activities = data_get($data, 'shipment_track_activities', []);

        return [
            'status' => is_string($status) && $status !== '' ? $status : null,
            'etd' => data_get($data, 'etd'),
            'activities' => is_array($activities) ? array_values(array_filter($activities, 'is_array')) : [],
            'error' => data_get($data, 'error'),
        ];
    }

    /** Cancel the order on Shiprocket (works only before the courier has picked it up). */
    public function cancelOrder(Order $order): void
    {
        $this->request('post', 'orders/cancel', ['ids' => [(int) $order->shiprocket_order_id]]);
    }

    /**
     * Act on an NDR (failed delivery attempt): action is "re-attempt" or "return".
     *
     * @return array<string, mixed>
     */
    public function ndrAction(Order $order, string $action, ?string $comments = null, ?string $deferredDate = null): array
    {
        $payload = ['action' => $action];
        if ($comments) {
            $payload['comments'] = $comments;
        }
        if ($deferredDate) {
            $payload['deferred_date'] = $deferredDate;
        }

        return $this->request('post', 'ndr/'.rawurlencode((string) $order->awb_code).'/action', $payload);
    }
}
