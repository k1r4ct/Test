<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketChangeLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ManageArchivedTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:manage-archived';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestisce automaticamente lo spostamento e l\'eliminazione di ticket archiviati e cancellati';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Inizio gestione ticket archiviati e cancellati...');

        $movedToDeleted = $this->moveArchivedToDeleted();
        $permanentlyDeleted = $this->deletePermanently();

        $this->info("Completato! {$movedToDeleted} ticket spostati in cancellati, {$permanentlyDeleted} ticket eliminati definitivamente.");

        return Command::SUCCESS;
    }

    /**
     * Sposta i ticket archiviati da più di 10 giorni in deleted
     *
     * @return int
     */
    private function moveArchivedToDeleted()
    {
        $tenDaysAgo = Carbon::now()->subDays(10);

        // Trova ticket con status 'closed' (archiviati) da più di 10 giorni
        $archivedTickets = Ticket::where('status', 'closed')
            ->where('updated_at', '<=', $tenDaysAgo)
            ->get();

        $movedCount = 0;

        foreach ($archivedTickets as $ticket) {
            $daysArchived = Carbon::now()->diffInDays($ticket->updated_at);

            // Salva lo stato precedente e aggiorna a deleted
            $previousStatus = $ticket->status;
            $ticket->previous_status = $previousStatus;
            $ticket->status = 'deleted';
            $ticket->save();

            // Logga il cambio di stato
            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => null, // Sistema automatico
                'previous_status' => $previousStatus,
                'new_status' => 'deleted',
                'change_type' => 'status'
            ]);

            $this->line("Ticket #{$ticket->ticket_number}: archiviato da {$daysArchived} giorni → spostato in cancellati");
            $movedCount++;
        }

        return $movedCount;
    }

    /**
     * Elimina definitivamente i ticket cancellati da più di 40 giorni
     *
     * @return int
     */
    private function deletePermanently()
    {
        $fortyDaysAgo = Carbon::now()->subDays(40);

        // Trova ticket con status 'deleted' da più di 40 giorni
        $deletedTickets = Ticket::where('status', 'deleted')
            ->where('updated_at', '<=', $fortyDaysAgo)
            ->get();

        $deletedCount = 0;

        foreach ($deletedTickets as $ticket) {
            $daysDeleted = Carbon::now()->diffInDays($ticket->updated_at);
            $ticketNumber = $ticket->ticket_number;
            $contractId = $ticket->contract_id;

            // Elimina fisicamente gli allegati
            $this->deleteTicketAttachments($ticket);

            // Elimina il ticket (cascade delete eliminerà automaticamente messaggi e log)
            $ticket->delete();

            $this->line("Ticket #{$ticketNumber}: cancellato da {$daysDeleted} giorni → eliminato definitivamente dal DB");
            $deletedCount++;

            // Opzionale: pulisci la directory del ticket se vuota
            $this->cleanupEmptyDirectory($contractId, $ticket->id);
        }

        return $deletedCount;
    }

    /**
     * Elimina fisicamente tutti gli allegati di un ticket
     *
     * @param Ticket $ticket
     * @return void
     */
    private function deleteTicketAttachments($ticket)
    {
        $attachments = TicketAttachment::where('ticket_id', $ticket->id)->get();

        foreach ($attachments as $attachment) {
            // Elimina il file fisico
            if (Storage::exists($attachment->file_path)) {
                Storage::delete($attachment->file_path);
                $this->line("  - Allegato eliminato: {$attachment->original_name}");
            }

            // Elimina il record dal database
            $attachment->delete();
        }
    }

    /**
     * Pulisci directory vuote dopo l'eliminazione
     *
     * @param int $contractId
     * @param int $ticketId
     * @return void
     */
    private function cleanupEmptyDirectory($contractId, $ticketId)
    {
        $ticketPath = "contracts/{$contractId}/tickets/{$ticketId}";

        if (Storage::exists($ticketPath)) {
            $files = Storage::allFiles($ticketPath);

            // Se la directory è vuota, eliminala
            if (empty($files)) {
                Storage::deleteDirectory($ticketPath);
                $this->line("  - Directory vuota eliminata: {$ticketPath}");
            }
        }
    }
}
