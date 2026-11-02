<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Nutrition\Child;

class DietSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'child_id',
        'subscription_plan_id',
        'model',
        'status',
        'payment_status',
        'razorpay_order_id',
        'razorpay_payment_id',
        'amount_paid',
        'plans_remaining',
        'starts_at',
        'expires_at',
        'cancelled_at',
        'renewed_from_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'amount_paid' => 'decimal:2',
            'plans_remaining' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(DietSubscription::class, 'renewed_from_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(DietSubscription::class, 'renewed_from_id');
    }

    public function nutritionLead(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(NutritionLead::class);
    }
}
