<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        $statusLabel = $this->statusLabel();

        return new Envelope(
            subject: 'Order ' . $statusLabel . ' - ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.status-updated',
            with: [
                'order' => $this->order,
                'statusLabel' => $this->statusLabel(),
                'headline' => $this->headline(),
                'messageLine' => $this->messageLine(),
            ],
        );
    }

    private function statusLabel(): string
    {
        return match ($this->order->status) {
            'confirmed' => 'confirmed',
            'processing' => 'processing',
            'packed' => 'packed',
            'shipped' => 'shipped',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'returned' => 'returned',
            default => 'updated',
        };
    }

    private function headline(): string
    {
        return match ($this->order->status) {
            'confirmed' => 'Your order has been confirmed.',
            'processing' => 'Your order is now being processed.',
            'packed' => 'Your order has been packed.',
            'shipped' => 'Your order is on the way.',
            'delivered' => 'Your order has been delivered.',
            'cancelled' => 'Your order has been cancelled.',
            'returned' => 'Your order has been marked as returned.',
            default => 'Your order status has been updated.',
        };
    }

    private function messageLine(): string
    {
        return match ($this->order->status) {
            'confirmed' => 'Our team has accepted your order and will begin preparing it shortly.',
            'processing' => 'We are preparing your items for shipment.',
            'packed' => 'Your package is packed and awaiting dispatch.',
            'shipped' => 'Your package has left our facility and is moving toward delivery.',
            'delivered' => 'We hope everything reached you safely.',
            'cancelled' => 'If this cancellation was unexpected, please contact support.',
            'returned' => 'Your return workflow has been completed successfully.',
            default => 'Please check your account for the latest order details.',
        };
    }
}
