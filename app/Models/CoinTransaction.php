<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'type',
        'amount',
        'description',
        'status',
        'reason',
        'remaining',
        'available_at',
        'expires_at',
        'idempotency_key',
    ];

    protected $casts = [
        'available_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
