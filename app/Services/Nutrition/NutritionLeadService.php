<?php

namespace App\Services\Nutrition;

use App\Models\DietSubscription;
use App\Models\NutritionLead;

class NutritionLeadService
{
    public function createForSubscription(DietSubscription $subscription): NutritionLead
    {
        return NutritionLead::firstOrCreate(
            ['diet_subscription_id' => $subscription->id],
            ['user_id' => $subscription->user_id, 'child_id' => $subscription->child_id, 'status' => 'new']
        );
    }
}
