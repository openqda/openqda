<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserFeedback extends Mailable
{
    use Queueable, SerializesModels;

    protected $data = [];

    protected $ticketId = '';

    /**
     * Create a new message instance.
     */
    public function __construct($data, $ticketId = '')
    {
        $this->data = $data;
        $this->ticketId = $ticketId;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = '\[OpenQDA System\]: User Feedback #'.$this->ticketId;

        return new Envelope(
            from: new Address('no-reply@openqda.org', 'OpenQDA System'),
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = 'emails.user-feedback';

        return new Content(
            // text: $this->text,
            view: $view,
            with: [
                'data' => $this->data,
                'ticketId' => $this->ticketId,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
