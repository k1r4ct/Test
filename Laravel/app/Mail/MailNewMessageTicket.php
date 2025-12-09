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

    public $destinatario;
    public $ticketMessage;
    public $originalTicket;

    /**
     * Create a new message instance.
     *
     * @param User $destinatario The user who will receive this email
     * @param TicketMessage $ticketMessage The message that was sent
     * @param Ticket $originalTicket The ticket
     */
    public function __construct($destinatario, $ticketMessage, $originalTicket)
    {
        // Validate that $destinatario is a User instance
        if (!$destinatario instanceof User) {
            throw new \InvalidArgumentException('$destinatario deve essere un\'istanza di User');
        }

        // Validate that $ticketMessage is a TicketMessage instance
        if (!$ticketMessage instanceof TicketMessage) {
            throw new \InvalidArgumentException('$ticketMessage deve essere un\'istanza di TicketMessage');
        }

        // Validate that $originalTicket is a Ticket instance
        if (!$originalTicket instanceof Ticket) {
            throw new \InvalidArgumentException('$originalTicket deve essere un\'istanza di Ticket');
        }

        $this->destinatario = $destinatario;
        $this->ticketMessage = $ticketMessage;
        $this->originalTicket = $originalTicket;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuovo Messaggio sul Ticket #' . $this->originalTicket->ticket_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $ticket = $this->originalTicket;

        // Get sender name from the message's user relationship
        $mittente = 'Sistema';
        if ($this->ticketMessage->user) {
            $mittente = trim(($this->ticketMessage->user->name ?? '') . ' ' . ($this->ticketMessage->user->cognome ?? ''));
            if (empty($mittente)) {
                $mittente = $this->ticketMessage->user->email ?? 'Sistema';
            }
        }

        // Get customer name 
        $nomeCustomer = 'N/A';
        if ($ticket->contract && $ticket->contract->customer_data) {
            $customerData = $ticket->contract->customer_data;
            $nomeCustomer = trim(($customerData->nome ?? '') . ' ' . ($customerData->cognome ?? ''));
            if (empty($nomeCustomer)) {
                $nomeCustomer = $customerData->ragione_sociale ?? 'N/A';
            }
        }

        return new Content(
            view: 'emails.nuovoMessaggioTicket',
            with: [
                'nomeUtente' => trim(($this->destinatario->name ?? '') . ' ' . ($this->destinatario->cognome ?? '')),
                'numeroTicket' => $ticket->ticket_number,
                'oggettoTicket' => $ticket->title,
                'statoTicket' => $ticket->status,
                'dataApertura' => $ticket->created_at->format('d/m/Y H:i'),
                'mittente' => $mittente, 
                'dataMessaggio' => $this->ticketMessage->created_at->format('d/m/Y H:i'),
                'testoMessaggio' => $this->ticketMessage->message,
                'linkTicket' => url('/tickets/' . $ticket->id),
                'nomeCustomer' => $nomeCustomer,
                'contractID' => $ticket->contract_id,
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