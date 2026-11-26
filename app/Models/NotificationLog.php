<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['order_id', 'event', 'channel', 'dedupe_key', 'status', 'recipient', 'error'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}