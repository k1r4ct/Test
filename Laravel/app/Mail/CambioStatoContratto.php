<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;



class CambioStatoContratto extends Mailable
{
    use Queueable, SerializesModels;

    public $contract;
    /**
     * Create a new message instance.
     */
    public function __construct($contract)
    {
        
        $this->contract=$contract;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'contratto andato in ko',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $contratto=$this->contract;
        return new Content(
            view: 'emails.cambioStatoContratto',
            with: [
                'contrattoId'=>$contratto->id,
                'nomeUtente'=> trim(($contratto->user->name && $contratto->user->cognome !== '' ? $contratto->user->name ." ".$contratto->user->cognome : $contratto->user->ragione_sociale )),
                /* 'cognomeUtente'=>$contratto->User->cognome,
                'ragSoc'=>$contratto->User->ragione_sociale, */
                'stato'=>$contratto->status_contract->micro_stato
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
