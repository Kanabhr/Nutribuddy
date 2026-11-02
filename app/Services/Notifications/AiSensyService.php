<?php

namespace App\Services\Notifications;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSensyService
{
    public function sendOtp(string $phone, string $otp): bool
    {
        $apiKey = (string) config('services.aisensy.api_key');
        $campaignName = (string) config('services.aisensy.otp_campaign');
        $destination = $this->normalizePhone($phone);

        if (! (bool) config('services.aisensy.enabled', true) || $apiKey === '' || $campaignName === '' || $destination === '') {
            return false;
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withOptions(['proxy' => ''])
                ->timeout(20)
                ->post((string) config('services.aisensy.endpoint'), [
                    'apiKey' => $apiKey,
                    'campaignName' => $campaignName,
                    'destination' => $destination,
                    'userName' => 'Customer',
                    'templateParams' => [$otp],
                    'tags' => ['authentication', 'otp'],
                    'attributes' => [],
                ]);

            return $response->successful();
        } catch (\Throwable $exception) {
            Log::error('AiSensy OTP delivery failed.', ['message' => $exception->getMessage()]);

            return false;
        }
    }

    public function sendOrderPaid(Order $order): bool
    {
        return $this->sendCampaign(
            (string) config('services.aisensy.order_paid_campaign'),
            $order,
            [
                $order->customer_name ?: 'Customer',
                $order->order_number,
                'Rs. '.number_format((float) $order->grand_total, 0),
                route('user.orders.detail-page', $order),
            ],
            ['order_paid', 'cashfree']
        );
    }

    private function sendCampaign(string $campaignName, Order $order, array $templateParams, array $tags = []): bool
    {
        if (! (bool) config('services.aisensy.enabled', true)) {
            return false;
        }

        $apiKey = (string) config('services.aisensy.api_key');
        $destination = $this->normalizePhone($order->customer_phone ?: $order->shipping_phone);

        if ($apiKey === '' || $campaignName === '' || $destination === '') {
            Log::warning('AiSensy message skipped because configuration or phone is missing.', [
                'order_id' => $order->id,
                'has_api_key' => $apiKey !== '',
                'has_campaign' => $campaignName !== '',
                'has_destination' => $destination !== '',
            ]);

            return false;
        }

        $payload = [
            'apiKey' => $apiKey,
            'campaignName' => $campaignName,
            'destination' => $destination,
            'userName' => $order->customer_name ?: 'Customer',
            'templateParams' => $templateParams,
            'tags' => $tags,
            'attributes' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
                'payment_status' => $order->payment_status,
            ],
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withOptions(['proxy' => ''])
                ->timeout(20)
                ->post((string) config('services.aisensy.endpoint'), $payload);

            if ($response->successful()) {
                return true;
            }

            Log::error('AiSensy campaign send failed.', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->json() ?: $response->body(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('AiSensy campaign send exception.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            return '91'.$digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return $digits;
        }

        return $digits;
    }
}
