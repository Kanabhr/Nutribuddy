<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Customer notification built from an editable template. Sent from inside the queued job. */
class OrderNotificationMail extends Mailable
{
    public function __construct(public string $subjectText, public string $bodyHtml) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectText);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->bodyHtml);
    }
}