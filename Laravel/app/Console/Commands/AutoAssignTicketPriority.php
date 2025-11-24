<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\TicketChangeLog;
use Carbon\Carbon;

class AutoAssignTicketPriority extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:auto-assign-priority';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assegna automaticamente la priorità ai ticket in base ai giorni di apertura';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Inizio assegnamento automatico priorità ticket...');

        // Recupera tutti i ticket attivi (new, waiting) che non sono risolti, chiusi o cancellati
        $activeTickets = Ticket::whereIn('status', ['new', 'waiting'])
            ->get();

        $updatedCount = 0;
        $now = Carbon::now();

        foreach ($activeTickets as $ticket) {
            $daysOpen = $now->diffInDays($ticket->created_at);
            $newPriority = null;

            // Determina la nuova priorità in base ai giorni di apertura
            if ($daysOpen >= 6) {
                $newPriority = 'high';
            } elseif ($daysOpen >= 4) {
                $newPriority = 'medium';
            } elseif ($daysOpen >= 2) {
                $newPriority = 'low';
            }

            // Aggiorna solo se la priorità è cambiata e non è null
            if ($newPriority && $ticket->priority !== $newPriority) {
                $previousPriority = $ticket->priority;
                $ticket->priority = $newPriority;
                $ticket->save();

                // Logga il cambio di priorità
                TicketChangeLog::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => null, // Sistema automatico, non un utente specifico
                    'previous_priority' => $previousPriority,
                    'new_priority' => $newPriority,
                    'change_type' => 'priority'
                ]);

                $this->line("Ticket #{$ticket->ticket_number}: {$previousPriority} → {$newPriority} (aperto da {$daysOpen} giorni)");
                $updatedCount++;
            }
        }

        $this->info("Completato! {$updatedCount} ticket aggiornati.");

        return Command::SUCCESS;
    }
}
