<?php

namespace App\Services\Shipping;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

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
            throw new Exception("Shiprocket credentials are not configured in .env (SHIPROCKET_EMAIL, SHIPROCKET_PASSWORD).");
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
            'body' => $response->body()
        ]);

        throw new Exception("Failed to authenticate with Shiprocket API.");
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
                'name' => $item->product_name . ($item->variant_name ? " - {$item->variant_name}" : ""),
                'sku' => $item->product_sku ?? 'SKU-' . $item->product_id,
                'units' => $item->quantity,
                'selling_price' => $item->unit_price,
                'discount' => '',
                'tax' => '',
                'hsn' => ''
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
            'pickup_location' => 'work', // Must match the location name on Shiprocket dashboard
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

        if (!$response->successful()) {
            Log::error('Shiprocket Create Order Failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            throw new Exception("Failed to push order to Shiprocket. Check logs for details.");
        }

        return $response->json();
    }
}
