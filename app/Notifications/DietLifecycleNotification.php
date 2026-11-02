<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** @param array<string, int|string|null> $data */
class DietLifecycleNotification extends Notification
{
    use Queueable;

    /** @param array<string, int|string|null> $data */
    public function __construct(private readonly array $data) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, int|string|null> */
    public function toArray(object $notifiable): array
    {
        return $this->data;
    }
}
