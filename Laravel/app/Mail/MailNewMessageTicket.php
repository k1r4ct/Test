<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\Ticket;

class MailNewMessageTicket extends Mailable
{
    use Queueable, SerializesModels;

    public $creatoreTicket;
    public $ticketMessage;
    public $originalTicket;

    public function __construct($creatoreTicket, $ticketMessage,$originalTicket)
    {
        // Assicurati che $creatoreTicket sia un'istanza di User
        if (!$creatoreTicket instanceof User) {
            throw new \InvalidArgumentException('$creatoreTicket deve essere un\'istanza di User');
        }

        // Assicurati che $ticketMessage sia un'istanza di TicketMessage
        if (!$ticketMessage instanceof TicketMessage) {
            throw new \InvalidArgumentException('$ticketMessage deve essere un\'istanza di TicketMessage');
        }
        if (!$originalTicket instanceof Ticket) {
            throw new \InvalidArgumentException('$ticketMessage deve essere un\'istanza di Ticket');
        }

        $this->creatoreTicket = $creatoreTicket;
        $this->ticketMessage = $ticketMessage;
        $this->originalTicket=$originalTicket;
        //$this->ticket=$ticket;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'il tuo Ticket ha avuto risposta id Ticket #' . $this->ticketMessage->ticket->id,
        );
    }

    public function content(): Content
    {
        $ticket = $this->ticketMessage->ticket;

        return new Content(
            view: 'emails.nuovoMessaggioTicket',
            with: [
                'nomeUtente' => trim($this->creatoreTicket->name . ' ' . $this->creatoreTicket->cognome),
                'numeroTicket' => $ticket->id,
                'oggettoTicket' => $ticket->subject,
                'statoTicket' => $ticket->status,
                'dataApertura' => $ticket->created_at->format('d/m/Y H:i'),
                'mittente' => $this->ticketMessage->user_name,
                'dataMessaggio' => $this->ticketMessage->created_at->format('d/m/Y H:i'),
                'testoMessaggio' => $this->ticketMessage->message,
                'linkTicket' => url('/tickets/' . $ticket->id),
                'nomeCustomer'=> trim(
                    ($this->originalTicket->contract->customer_data->nome && $this->originalTicket->contract->customer_data->cognome !== '' ||
                    $this->originalTicket->contract->customer_data->nome && $this->originalTicket->contract->customer_data->cognome !== NULL) ?
                    $this->originalTicket->contract->customer_data->nome . ' '. $this->originalTicket->contract->customer_data->nome : $this->originalTicket->contract->customer_data->ragione_sociale),
                'contractID'=>$this->originalTicket->contract->id,
            ]

        );
    }

    public function attachments(): array
    {
        return [];
    }
}
