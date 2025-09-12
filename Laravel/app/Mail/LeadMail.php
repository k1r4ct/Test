<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $leadData;

    public function __construct($leadData)
    {
        $this->leadData = $leadData;
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lead-mail',
            with: [
                'nameInvitante' => $this->leadData['Invitante'] ?? null,
                'nameInvitato' => $this->leadData['amico'] ?? null,
                'mail_cliente_sc'=>$this->leadData['email_cliente_sc'] ?? null,
                
            ]
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
