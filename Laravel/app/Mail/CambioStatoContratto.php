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
        $this->contract = $contract;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Aggiornamento Contratto #' . $this->contract->id . ' - ' . ($this->contract->status_contract->micro_stato ?? 'Stato aggiornato'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $contratto = $this->contract;
        
        // SEU name (who inserted the contract - email recipient)
        $nomeSeu = 'Utente';
        if ($contratto->UserSeu) {
            if (!empty($contratto->UserSeu->name) && !empty($contratto->UserSeu->cognome)) {
                $nomeSeu = trim($contratto->UserSeu->name . ' ' . $contratto->UserSeu->cognome);
            } elseif (!empty($contratto->UserSeu->ragione_sociale)) {
                $nomeSeu = trim($contratto->UserSeu->ragione_sociale);
            }
        }
        
        // Contraente name (from customer_data - the contract holder)
        $nomeContraente = 'N/A';
        if ($contratto->customer_data) {
            $tempName = trim(($contratto->customer_data->nome ?? '') . ' ' . ($contratto->customer_data->cognome ?? ''));
            if (!empty($tempName)) {
                $nomeContraente = $tempName;
            } elseif (!empty($contratto->customer_data->ragione_sociale)) {
                $nomeContraente = trim($contratto->customer_data->ragione_sociale);
            }
        }

        // Get status info
        $statoContratto = $contratto->status_contract->micro_stato ?? 'N/D';
        $macroStato = '';
        if ($contratto->status_contract && $contratto->status_contract->option_status_contract) {
            // option_status_contract is a Collection, get first item
            $firstOption = $contratto->status_contract->option_status_contract->first();
            $macroStato = $firstOption ? ($firstOption->macro_stato ?? '') : '';
        }

        // Product info
        $nomeProdotto = $contratto->product->descrizione ?? 'N/D';
        
        // Dates
        $dataStipula = $contratto->data_stipula ? \Carbon\Carbon::parse($contratto->data_stipula)->format('d/m/Y') : 'N/D';
        
        return new Content(
            view: 'emails.cambioStatoContratto',
            with: [
                'contrattoId' => $contratto->id,
                'codiceContratto' => $contratto->codice_contratto ?? 'N/D',
                'nomeSeu' => $nomeSeu,
                'nomeContraente' => $nomeContraente,
                'statoContratto' => $statoContratto,
                'macroStato' => $macroStato,
                'nomeProdotto' => $nomeProdotto,
                'dataStipula' => $dataStipula,
                'linkContratto' => url('/clearportal/contratti/' . $contratto->id),
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