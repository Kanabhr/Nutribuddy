<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One manual payment adjustment. Append-only: rows can be created but never edited or deleted through Eloquent.
 * Gateway-confirmed transactions stay in the payments table.
 */
class OrderPaymentAdjustment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'type',
        'amount',
        'from_payment_status',
        'to_payment_status',
        'reason',
        'reference',
        'adjusted_by',
        'adjusted_by_name',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Returning false cancels the operation, which keeps the ledger permanent.
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'mark_paid' => 'Marked as paid',
            'refund' => 'Full refund',
            'partial_refund' => 'Partial refund',
            default => ucfirst(str_replace('_', ' ', (string) $this->type)),
        };
    }
}