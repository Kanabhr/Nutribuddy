<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'monthly_price',
        'quarterly_price',
        'annual_price',
        'level',
        'is_active',
        'features',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'features' => 'array',
        'price' => 'decimal:2',
    ];

    public function dietSubscriptions()
    {
        return $this->hasMany(DietSubscription::class);
    }
}
