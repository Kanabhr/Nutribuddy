<?php

namespace App\Mail;

use App\Models\OrderReturn;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReturnStatusUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public OrderReturn $orderReturn)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Return update - ' . $this->orderReturn->return_number . ' is ' . strtoupper($this->orderReturn->status),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.returns.status-updated',
            with: ['orderReturn' => $this->orderReturn],
        );
    }
}
