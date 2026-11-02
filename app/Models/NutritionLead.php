<?php

namespace App\Models;

use App\Models\Nutrition\Child;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NutritionLead extends Model
{
    protected $fillable = ['diet_subscription_id', 'user_id', 'child_id', 'assigned_to', 'status', 'assigned_at', 'completed_at'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function subscription(): BelongsTo { return $this->belongsTo(DietSubscription::class, 'diet_subscription_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function child(): BelongsTo { return $this->belongsTo(Child::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
}
