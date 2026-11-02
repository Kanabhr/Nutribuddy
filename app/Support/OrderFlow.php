<?php

namespace App\Support;

class OrderFlow
{
    public const ORDER_STATUSES = [
        'pending',
        'confirmed',
        'processing',
        'packed',
        'shipped',
        'delivered',
        'cancelled',
        'returned',
    ];

    public const FULFILLMENT_STATUSES = [
        'unfulfilled',
        'partially_fulfilled',
        'fulfilled',
    ];

    public const PAYMENT_STATUSES = [
        'pending',
        'paid',
        'failed',
        'refunded',
        'partially_refunded',
    ];

    public const RETURN_STATUSES = [
        'pending',
        'approved',
        'rejected',
        'completed',
    ];

    public const RETURN_REASONS = [
        'Defective Product',
        'Wrong Product Sent',
        'Product Damaged on Arrival',
        'Expired Product',
        'Quality not as expected',
        'Changed my mind',
        'Others',
    ];

    public const STATUS_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['packed', 'cancelled'],
        'packed' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => ['returned'],
        'cancelled' => [],
        'returned' => [],
    ];

    public static function canMoveTo(string $from, string $to): bool
    {
        $allowedTransitions = self::STATUS_TRANSITIONS[$from] ?? [];

        return in_array($to, $allowedTransitions, true);
    }

    public static function canAdminMoveTo(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        if ($to === 'delivered' && ! in_array($from, ['cancelled', 'returned'], true)) {
            return true;
        }

        return self::canMoveTo($from, $to);
    }
}
