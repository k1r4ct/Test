<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CriticalErrorNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $errorMessage;
    public array $context;
    public string $level;
    public string $occurredAt;

    /**
     * Create a new message instance.
     */
    public function __construct(string $message, array $context = [], string $level = 'critical')
    {
        $this->errorMessage = $message;
        $this->context = $context;
        $this->level = $level;
        $this->occurredAt = now()->format('d/m/Y H:i:s');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $levelLabel = strtoupper($this->level);
        $appName = config('app.name', 'Semprechiaro CRM');
        
        return new Envelope(
            subject: "[{$levelLabel}] {$appName} - Errore di Sistema",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.critical-error-notification',
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
