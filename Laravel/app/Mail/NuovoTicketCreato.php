<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Ticket;

class NuovoTicketCreato extends Mailable
{
    use Queueable, SerializesModels;

    public $creatoreTicket;
    public $ticket;

    /**
     * Create a new message instance.
     *
     * @param User $creatoreTicket The user who created the ticket
     * @param Ticket $ticket The ticket that was created
     */
    public function __construct(User $creatoreTicket, Ticket $ticket)
    {
        $this->creatoreTicket = $creatoreTicket;
        $this->ticket = $ticket;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket Creato #' . $this->ticket->ticket_number . ' - ' . $this->ticket->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Get customer name from contract relationship
        $nomeCustomer = 'N/A';
        if ($this->ticket->contract && $this->ticket->contract->customer_data) {
            $customerData = $this->ticket->contract->customer_data;
            $nomeCustomer = trim(($customerData->nome ?? '') . ' ' . ($customerData->cognome ?? ''));
            if (empty($nomeCustomer)) {
                $nomeCustomer = $customerData->ragione_sociale ?? 'N/A';
            }
        }

        return new Content(
            view: 'emails.nuovoTicketCreato',
            with: [
                'nomeUtente' => trim(($this->creatoreTicket->name ?? '') . ' ' . ($this->creatoreTicket->cognome ?? '')),
                'numeroTicket' => $this->ticket->ticket_number,
                'titoloTicket' => $this->ticket->title,
                'descrizioneTicket' => $this->ticket->description,
                'dataApertura' => $this->ticket->created_at->format('d/m/Y H:i'),
                'nomeCustomer' => $nomeCustomer,
                'contractID' => $this->ticket->contract_id,
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