<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class SystemMessage extends Mailable
{
    use Queueable, SerializesModels;

    public $messageContent;
    private $replyToAddress;

    public function __construct(string $messageContent, string $replyToAddress)
    {
        $this->messageContent = $messageContent;
        $this->replyToAddress = $replyToAddress;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME')),
            replyTo: [new Address($this->replyToAddress)],
            subject: 'System Message'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.system-message',
        );
    }
}
