<?php

namespace App\Models;

use App\Models\Nutrition\Child;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'child_id',
        'cashfree_subscription_id',
        'cf_subscription_id',
        'status',
        'current_period_start',
        'current_period_end',
        'grace_until',
        'cancel_at_period_end',
        'cancelled_at',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'grace_until' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancel_at_period_end' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'subscription_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['ACTIVE', 'active'], true)
            && ($this->current_period_end === null || $this->current_period_end->isFuture());
    }
}
