<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewContactLeadNotification extends Notification
{
    use Queueable;

    private $lead;

    /**
     * Create a new notification instance.
     */
    public function __construct($lead)
    {
        $this->lead = $lead;
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
            'title' => 'New Contact Lead',
            'message' => $this->lead->name . ' has submitted a new contact inquiry.',
            'lead_id' => $this->lead->id,
            'action_url' => route('admin.ecommerce.contact-leads.index'),
        ];
    }
}
