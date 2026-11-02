<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiSensyWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $secret = (string) config('services.aisensy.webhook_secret');

        if ($secret !== '' && ! hash_equals($secret, (string) ($request->header('x-aisensy-secret') ?: $request->query('secret')))) {
            return response()->json(['message' => 'Invalid webhook secret.'], 401);
        }

        Log::info('AiSensy webhook received.', [
            'payload' => $request->all(),
            'headers' => [
                'user_agent' => $request->userAgent(),
                'x_aisensy_event' => $request->header('x-aisensy-event'),
            ],
        ]);

        return response()->json(['ok' => true]);
    }
}
