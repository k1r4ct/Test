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
class CambioStatoTicket extends Mailable
{
    use Queueable, SerializesModels;

    public $creatoreTicket;
    public $originalTicket;
    /**
     * Create a new message instance.
     */
    public function __construct($creatoreTicket,$originalTicket)
    {
        // Assicurati che $creatoreTicket sia un'istanza di User
        if (!$creatoreTicket instanceof User) {
            throw new \InvalidArgumentException('$creatoreTicket deve essere un\'istanza di User');
        }

        // Assicurati che $ticketMessage sia un'istanza di TicketMessage
        
        if (!$originalTicket instanceof Ticket) {
            throw new \InvalidArgumentException('$ticketMessage deve essere un\'istanza di Ticket');
        }

        $this->creatoreTicket = $creatoreTicket;
        $this->originalTicket=$originalTicket;
        //$this->ticket=$ticket;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cambio Stato Ticket #' . $this->originalTicket->id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $ticket = $this->originalTicket;

        return new Content(
            view: 'emails.cambioStatoTicket',
            with: [
                'nomeUtente' => trim($this->creatoreTicket->name . ' ' . $this->creatoreTicket->cognome),
                'numeroTicket' => $ticket->id,
                'oggettoTicket' => $ticket->subject,
                'statoTicket' => $ticket->status,
                'dataApertura' => $ticket->created_at->format('d/m/Y H:i'),
                'linkTicket' => url('/tickets/' . $ticket->id),
                'nomeCustomer'=> trim(
                    ($this->originalTicket->contract->customer_data->nome && $this->originalTicket->contract->customer_data->cognome !== '' ||
                    $this->originalTicket->contract->customer_data->nome && $this->originalTicket->contract->customer_data->cognome !== NULL) ?
                    $this->originalTicket->contract->customer_data->nome . ' '. $this->originalTicket->contract->customer_data->nome : $this->originalTicket->contract->customer_data->ragione_sociale),
                'contractID'=>$this->originalTicket->contract->id,
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
