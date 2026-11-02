<?php

namespace App\Console\Commands;

use App\Models\DietSubscription;
use App\Services\Nutrition\DietLifecycleNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireDietSubscriptions extends Command
{
    public function __construct(private readonly DietLifecycleNotificationService $notifications)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diet:expire-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire diet service subscriptions that have passed their expiration date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredCount = 0;

        DietSubscription::with(['user:id,name', 'child:id,name', 'subscriptionPlan:id,name'])
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($subscriptions) use (&$expiredCount): void {
                foreach ($subscriptions as $subscription) {
                    $updated = DietSubscription::query()
                        ->whereKey($subscription->id)
                        ->where('status', 'active')
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now())
                        ->update(['status' => 'expired']);

                    if (! $updated) {
                        continue;
                    }

                    $expiredCount++;
                    $subscription->status = 'expired';
                    $this->notifications->subscriptionExpired($subscription);
                }
            });

        if ($expiredCount > 0) {
            Log::info("Expired {$expiredCount} diet subscriptions.");
            $this->info("Expired {$expiredCount} diet subscriptions.");
        } else {
            $this->info("No diet subscriptions to expire.");
        }
    }
}
