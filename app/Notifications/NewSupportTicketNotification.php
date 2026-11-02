<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewSupportTicketNotification extends Notification
{
    use Queueable;

    private $ticket;

    /**
     * Create a new notification instance.
     */
    public function __construct($ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Support Ticket',
            'message' => 'A new support ticket (' . $this->ticket->ticket_number . ') has been created.',
            'ticket_id' => $this->ticket->id,
            'action_url' => route('admin.ecommerce.support-tickets.index'),
        ];
    }
}
