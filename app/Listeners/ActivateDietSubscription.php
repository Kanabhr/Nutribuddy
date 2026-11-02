<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\DietSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\Nutrition\NutritionLeadService;

class ActivateDietSubscription
{
    /**
     * Handle the event.
     */
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;

        foreach ($order->items as $item) {
            $product = $item->product;

            if ($product && $product->is_diet_service) {
                // Determine model based on product details (e.g. name or attributes)
                // Defaulting logic here - you can refine this based on exactly how product maps to 'model'
                $model = 'Basic';
                if (stripos($product->name, 'Plus') !== false) {
                    $model = 'Plus';
                } elseif (stripos($product->name, 'Pro') !== false) {
                    $model = 'Pro';
                } elseif (stripos($product->name, 'Premium') !== false) {
                    $model = 'Premium';
                }

                // Check if an exact subscription already exists for this order item to prevent duplicates
                $exists = DietSubscription::where('order_id', $order->id)
                    ->where('product_id', $product->id)
                    ->exists();

                if (!$exists) {
                    // Try to find the child_id from the item snapshot or order metadata if available
                    // For now, we will associate it with the user, and if a child exists, the first child.
                    // (Ideally, the checkout process captures child_id).
                    $childId = null;
                    if (isset($item->item_snapshot['child_id'])) {
                        $childId = $item->item_snapshot['child_id'];
                    } else {
                        $child = $order->user->children()->first();
                        $childId = $child ? $child->id : null;
                    }

                    // Calculate expiry (e.g. 30 days for premium/subscription, 1 year for others depending on business logic)
                    // We'll set a standard 30-day for now, or use product attributes
                    $expiresAt = now()->addDays(30);

                    $subscription = DietSubscription::create([
                        'user_id' => $order->user_id,
                        'child_id' => $childId, // Might be null initially if they need to assign it later
                        'product_id' => $product->id,
                        'order_id' => $order->id,
                        'model' => $model,
                        'status' => 'active',
                        'starts_at' => now(),
                        'expires_at' => $expiresAt,
                    ]);
                    app(NutritionLeadService::class)->createForSubscription($subscription);
                    app(\App\Services\Nutrition\DietLifecycleNotificationService::class)
                        ->subscriptionActivated($subscription);

                    Log::info('Activated Diet Subscription for order: ' . $order->id);
                }
            }
        }
    }
}
