<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Shipping\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Receives Shiprocket tracking updates.
 * Shiprocket sends the token you configure as the "x-api-key" header (there is no signature).
 * The URL must not contain "shiprocket", "kartrocket", "sr" or "kr", so it is /webhooks/courier-tracking.
 */
class CourierTrackingWebhookController extends Controller
{
    public function __invoke(Request $request, ShipmentService $shipments): JsonResponse
    {
        $token = (string) config('services.shiprocket.webhook_token');

        // Fail closed: without a configured token nobody can write tracking data.
        if ($token === '') {
            return response()->json(['message' => 'Webhook is not configured.'], 503);
        }

        if (! hash_equals($token, (string) $request->header('x-api-key'))) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        try {
            $payload = $request->json()->all() ?: $request->all();
            $shipments->handleWebhook(is_array($payload) ? $payload : []);
        } catch (Throwable $e) {
            Log::error('Courier tracking webhook failed.', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Could not process the update.'], 500);
        }

        // Unknown shipments and return shipments are acknowledged too, so Shiprocket does not keep retrying.
        return response()->json(['ok' => true]);
    }
}
