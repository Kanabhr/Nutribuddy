<?php

namespace App\Http\Controllers\Frontend\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreNewsletterSubscriberRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;

class NewsletterSubscriberController extends Controller
{
    public function store(StoreNewsletterSubscriberRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $subscriber = NewsletterSubscriber::updateOrCreate(
            ['email' => strtolower($validated['email'])],
            [
                'status' => 'subscribed',
                'source' => $validated['source'] ?? 'website',
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]
        );

        return response()->json([
            'message' => $subscriber->wasRecentlyCreated
                ? 'Thanks for subscribing.'
                : 'You are subscribed again.',
        ]);
    }
}
