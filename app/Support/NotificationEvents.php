<?php

namespace App\Support;

/** Customer notification events (point 26) and the values templates may use. */
class NotificationEvents
{
    public const PLACED = 'order_placed';
    public const CONFIRMED = 'order_confirmed';
    public const SHIPPED = 'order_shipped';
    public const OUT_FOR_DELIVERY = 'out_for_delivery';
    public const DELIVERED = 'order_delivered';
    public const REFUNDED = 'order_refunded';

    /**
     * Order placed has no email here: the existing order confirmation email
     * (OrderPlacedCustomerMail) is still sent at checkout.
     *
     * @return array<string, array{label: string, channels: list<string>}>
     */
    public static function all(): array
    {
        return [
            self::PLACED => ['label' => 'Order placed', 'channels' => ['whatsapp']],
            self::CONFIRMED => ['label' => 'Order confirmed', 'channels' => ['email', 'whatsapp']],
            self::SHIPPED => ['label' => 'Order shipped (with tracking)', 'channels' => ['email', 'whatsapp']],
            self::OUT_FOR_DELIVERY => ['label' => 'Out for delivery', 'channels' => ['email', 'whatsapp']],
            self::DELIVERED => ['label' => 'Order delivered', 'channels' => ['email', 'whatsapp']],
            self::REFUNDED => ['label' => 'Refund processed', 'channels' => ['email', 'whatsapp']],
        ];
    }

    public static function isKnown(string $event): bool
    {
        return array_key_exists($event, self::all());
    }

    /** Order status that triggers a notification, or null when that status sends none. */
    public static function forOrderStatus(?string $status): ?string
    {
        return match ($status) {
            'confirmed' => self::CONFIRMED,
            'shipped' => self::SHIPPED,
            'delivered' => self::DELIVERED,
            default => null,
        };
    }

    /** @return array<string, string> placeholder key => what it holds */
    public static function placeholders(): array
    {
        return [
            'customer_name' => 'Customer name',
            'order_number' => 'Order number',
            'order_total' => 'Order total, for example Rs. 1,250.00',
            'order_date' => 'Date the order was placed',
            'order_link' => 'Link to the order page',
            'courier' => 'Courier name (empty until assigned)',
            'awb' => 'AWB / tracking number (empty until assigned)',
            'tracking_url' => 'Tracking link (empty until an AWB exists)',
            'tracking_line' => 'Ready-made tracking text: courier, AWB and link',
            'refund_amount' => 'Amount of the latest refund',
        ];
    }
}