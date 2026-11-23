<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One tracking step for an order's shipment (webhook scan, tracking refresh or admin action). */
class OrderShipmentEvent extends Model
{
    protected $fillable = [
        'order_id',
        'awb_code',
        'status',
        'activity',
        'location',
        'source',
        'occurred_at',
        'event_hash',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
