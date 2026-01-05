<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\TicketChangeLog;
use App\Models\contract;
use App\Models\notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TicketController extends Controller
{
    public function getTickets()
    {
        try {
            $user = Auth::user();
            $userRole = $user->role->id;

            if (!in_array($userRole, [1, 4, 5, 6, 9, 10])) {
                return response()->json([
                    "response" => "error", 
                    "status" => "403", 
                    "message" => "Access denied"
                ]);
            }

            $query = Ticket::with([
                'contract.customer_data',
                'contract.product',
                'createdBy.role',
                'assignedTo.role',
                'messages.user.role'
            ])
            ->withCount('attachments');

            if (!in_array($userRole, [1, 6])) {
                $query->where('status', '!=', Ticket::STATUS_DELETED);
            }

            $tickets = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                "response" => "ok", 
                "status" => "200", 
                "body" => ["risposta" => $tickets]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting tickets: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500", 
                "message" => "Server error: ".$e->getMessage()
            ]);
        }
    }

    public function createTicket(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'priority' => 'nullable|in:low,medium,high,unassigned',
                'contract_id' => 'required|exists:contracts,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "response" => "error",
                    "status" => "400", 
                    "errors" => $validator->errors()
                ]);
            }

            $user = Auth::user();
            $contract = contract::findOrFail($request->contract_id);
            
            if (!in_array($user->role->id, [1, 2, 4, 5])) {
                if ($contract->created_by_user_id != $user->id) {
                    return response()->json([
                        "response" => "error",
                        "status" => "403", 
                        "message" => "Access denied"
                    ]);
                }
            }

            $restrictedRoles = [2, 3];
            
            if (in_array($user->role->id, $restrictedRoles)) {
                $existingTicket = Ticket::where('contract_id', $request->contract_id)
                    ->whereNotIn('status', [Ticket::STATUS_DELETED, Ticket::STATUS_CLOSED])
                    ->first();
                    
                if ($existingTicket) {
                    return response()->json([
                        "response" => "error",
                        "status" => "409", 
                        "message" => "Un ticket esiste già per questo contratto. ID Ticket: " . $existingTicket->ticket_number,
                        "existing_ticket_id" => $existingTicket->id,
                        "existing_ticket_number" => $existingTicket->ticket_number
                    ]);
                }
            }

            $ticket = Ticket::create([
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority ?? Ticket::PRIORITY_UNASSIGNED,
                'contract_id' => $request->contract_id,
                'created_by_user_id' => $user->id,
            ]);

            $ticket->load([
                'contract.customer_data',
                'contract.product',
                'createdBy.role'
            ]);

            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => 'Ticket creato',
                'message_type' => 'status_change'
            ]);

            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'previous_status' => null,
                'new_status' => Ticket::STATUS_NEW,
                'previous_priority' => null,
                'new_priority' => $ticket->priority,
                'change_type' => TicketChangeLog::CHANGE_TYPE_BOTH
            ]);

            $this->notifyTicketParticipants($ticket, 'new_ticket');

            try {
                Mail::to($user->email)->send(new \App\Mail\NuovoTicketCreato($user, $ticket));
                Log::info("NuovoTicketCreato email sent to: " . $user->email . " for ticket #" . $ticket->ticket_number);
            } catch (\Exception $mailException) {
                Log::error('Error sending NuovoTicketCreato email: ' . $mailException->getMessage());
            }

            return response()->json([
                "response" => "ok",
                "status" => "200", 
                "message" => "Ticket created successfully",
                "body" => ["risposta" => $ticket]
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating ticket: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500", 
                "message" => "Server error"
            ]);
        }
    }

    public function updateTicketStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ticket_id' => 'required|exists:tickets,id',
                'status' => 'required|in:new,waiting,resolved,closed,deleted'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "response" => "error",
                    "status" => "400", 
                    "errors" => $validator->errors()
                ]);
            }

            $user = Auth::user();
            $userRole = $user->role->id;
            $ticket = Ticket::with(['createdBy', 'assignedTo','contract.customer_data'])->findOrFail($request->ticket_id);
            $mailCreatoreTicket = $ticket->createdBy->email;
            $userCreatoreTicket = $ticket->createdBy;

            $oldStatus = $ticket->status;
            $newStatus = $request->status;

            if ($oldStatus !== Ticket::STATUS_NEW && $newStatus === Ticket::STATUS_NEW && !in_array($userRole, [1, 6])) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Only administrators can move tickets back to 'New'"
                ]);
            }

            if ($newStatus === Ticket::STATUS_DELETED && !in_array($userRole, [1, 6])) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Only administrators can delete tickets"
                ]);
            }

            if (!$this->canManageTicket($user, $ticket)) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Access denied"
                ]);
            }

            $updateData = [
                'previous_status' => $oldStatus,
                'status' => $newStatus,
                'assigned_to_user_id' => $user->id
            ];

            $updateData = $this->handleStatusTimestamps($updateData, $oldStatus, $newStatus);

            $ticket->update($updateData);

            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'previous_status' => $oldStatus,
                'new_status' => $newStatus,
                'previous_priority' => null,
                'new_priority' => null,
                'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
            ]);

            $statusLabels = Ticket::getStatusOptions();
            $statusLabel = $statusLabels[$newStatus] ?? $newStatus;
            $oldStatusLabel = $statusLabels[$oldStatus] ?? $oldStatus;
            
            Log::info("Ticket status changed to: " . $statusLabel);
            
            if ($newStatus === Ticket::STATUS_RESOLVED) {
                Mail::to($mailCreatoreTicket)->send(new \App\Mail\CambioStatoTicket($userCreatoreTicket, $ticket));
            }
            
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => "Stato cambiato da '{$oldStatusLabel}' a '{$statusLabel}'",
                'message_type' => 'status_change'
            ]);

            $this->notifyTicketParticipants($ticket, 'status_changed');

            return response()->json([
                "response" => "ok",
                "status" => "200", 
                "message" => "Status updated successfully"
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating ticket status: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500", 
                "message" => "Server error"
            ]);
        }
    }

    private function handleStatusTimestamps(array $updateData, string $oldStatus, string $newStatus): array
    {
        $now = now();

        // Moving TO resolved status - set resolved_at
        if ($newStatus === Ticket::STATUS_RESOLVED && $oldStatus !== Ticket::STATUS_RESOLVED) {
            $updateData['resolved_at'] = $now;
        }

        // Moving TO closed status - set closed_at
        if ($newStatus === Ticket::STATUS_CLOSED && $oldStatus !== Ticket::STATUS_CLOSED) {
            $updateData['closed_at'] = $now;
        }

        // Moving TO deleted status - set deleted_at
        if ($newStatus === Ticket::STATUS_DELETED && $oldStatus !== Ticket::STATUS_DELETED) {
            $updateData['deleted_at'] = $now;
        }

        // Restoring FROM closed or deleted - set restored_at and clear timestamps
        if (in_array($oldStatus, [Ticket::STATUS_CLOSED, Ticket::STATUS_DELETED]) 
            && !in_array($newStatus, [Ticket::STATUS_CLOSED, Ticket::STATUS_DELETED])) {
            $updateData['restored_at'] = $now;
            $updateData['closed_at'] = null;
            $updateData['deleted_at'] = null;
            if (in_array($newStatus, [Ticket::STATUS_NEW, Ticket::STATUS_WAITING])) {
                $updateData['resolved_at'] = null;
            }
        }

        // If moving backwards from resolved to new/waiting, clear resolved_at
        if ($oldStatus === Ticket::STATUS_RESOLVED 
            && in_array($newStatus, [Ticket::STATUS_NEW, Ticket::STATUS_WAITING])) {
            $updateData['resolved_at'] = null;
        }

        return $updateData;
    }

    public function updateTicketPriority(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ticket_id' => 'required|exists:tickets,id',
                'priority' => 'required|in:low,medium,high,unassigned'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "response" => "error",
                    "status" => "400", 
                    "errors" => $validator->errors()
                ]);
            }

            $user = Auth::user();
            $ticket = Ticket::findOrFail($request->ticket_id);

            $oldPriority = $ticket->priority;
            $newPriority = $request->priority;

            $ticket->update([
                'priority' => $newPriority
            ]);

            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'previous_status' => null,
                'new_status' => null,
                'previous_priority' => $oldPriority,
                'new_priority' => $newPriority,
                'change_type' => TicketChangeLog::CHANGE_TYPE_PRIORITY
            ]);

            $priorityLabels = Ticket::getPriorityOptions();
            $oldLabel = $priorityLabels[$oldPriority] ?? $oldPriority;
            $newLabel = $priorityLabels[$newPriority] ?? $newPriority;

            return response()->json([
                "response" => "ok",
                "status" => "200", 
                "message" => "Priority updated successfully",
                "old_priority" => $oldLabel,
                "new_priority" => $newLabel
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating ticket priority: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500", 
                "message" => "Server error"
            ]);
        }
    }

    public function closeTicket(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ticket_id' => 'required|exists:tickets,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "response" => "error",
                    "status" => "400", 
                    "errors" => $validator->errors()
                ]);
            }

            $user = Auth::user();
            $userRole = $user->role->id;
            $ticket = Ticket::findOrFail($request->ticket_id);

            $isAdmin = in_array($userRole, [1, 6]);
            $isAssigned = $ticket->assigned_to_user_id == $user->id;

            if (!$isAdmin && !$isAssigned) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Only administrators or assigned users can close tickets"
                ]);
            }

            if ($ticket->status !== Ticket::STATUS_RESOLVED) {
                return response()->json([
                    "response" => "error",
                    "status" => "400", 
                    "message" => "Only resolved tickets can be closed"
                ]);
            }

            $oldStatus = $ticket->status;

            $ticket->update([
                'previous_status' => $oldStatus,
                'status' => Ticket::STATUS_CLOSED,
                'closed_at' => now()
            ]);

            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'previous_status' => $oldStatus,
                'new_status' => Ticket::STATUS_CLOSED,
                'previous_priority' => null,
                'new_priority' => null,
                'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
            ]);

            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => 'Ticket archiviato e chiuso',
                'message_type' => 'status_change'
            ]);

            return response()->json([
                "response" => "ok",
                "status" => "200", 
                "message" => "Ticket closed successfully"
            ]);

        } catch (\Exception $e) {
            Log::error('Error closing ticket: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500", 
                "message" => "Server error"
            ]);
        }
    }

    public function bulkDeleteTickets(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ticket_ids' => 'required|array|min:1',
                'ticket_ids.*' => 'exists:tickets,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "response" => "error",
                    "status" => "400", 
                    "errors" => $validator->errors()
                ]);
            }

            $user = Auth::user();
            $userRole = $user->role->id;

            if (!in_array($userRole, [1, 6])) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Only administrators can delete tickets"
                ]);
            }

            $tickets = Ticket::whereIn('id', $request->ticket_ids)->get();
            $deletedCount = 0;

            foreach ($tickets as $ticket) {
                $oldStatus = $ticket->status;
                
                $ticket->update([
                    'previous_status' => $oldStatus,
                    'status' => Ticket::STATUS_DELETED,
                    'deleted_at' => now(),
                    'assigned_to_user_id' => $user->id
                ]);

                TicketChangeLog::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'previous_status' => $oldStatus,
                    'new_status' => Ticket::STATUS_DELETED,
                    'previous_priority' => null,
                    'new_priority' => null,
                    'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
                ]);

                TicketMessage::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'message' => "Ticket cancellato dall'amministratore",
                    'message_type' => 'status_change'
                ]);

                $deletedCount++;
            }

            return response()->json([
                "response" => "ok",
                "status" => "200", 
                "message" => "{$deletedCount} ticket(s) deleted successfully",
                "deleted_count" => $deletedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulk deleting tickets: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500", 
                "message" => "Server error"
            ]);
        }
    }

    public function getTicketMessages($ticketId)
    {
        try {
            $user = Auth::user();
            $ticket = Ticket::findOrFail($ticketId);

            if (!$this->canAccessTicket($user, $ticket)) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Access denied"
                ]);
            }

            $messages = TicketMessage::with(['user.role'])
                ->where('ticket_id', $ticketId)
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                "response" => "ok",
                "status" => "200", 
                "body" => ["risposta" => $messages]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting ticket messages: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500", 
                "message" => "Server error"
            ]);
        }
    }

    public function sendTicketMessage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ticket_id' => 'required|exists:tickets,id',
                'message' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "response" => "error",
                    "status" => "400", 
                    "errors" => $validator->errors()
                ]);
            }

            $user = Auth::user();
            $ticket = Ticket::with(['createdBy', 'assignedTo', 'contract.customer_data'])->findOrFail($request->ticket_id);
            
            if (!$this->canAccessTicket($user, $ticket)) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Access denied"
                ]);
            }

            $message = TicketMessage::create([
                'ticket_id' => $request->ticket_id,
                'user_id' => $user->id,
                'message' => $request->message,
                'message_type' => 'text'
            ]);

            $this->notifyTicketParticipants($ticket, 'new_message');

            $message->load(['user.role', 'ticket']);
            
            $this->sendMessageNotificationEmail($user, $ticket, $message);
            
            return response()->json([
                "response" => "ok",
                "status" => "200", 
                "message" => "Message sent successfully",
                "body" => ["risposta" => $message]
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending ticket message: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500", 
                "message" => "Server error"
            ]);
        }
    }

    private function sendMessageNotificationEmail($sender, $ticket, $message)
    {
        try {
            $ticketCreatorId = $ticket->created_by_user_id;
            $senderId = $sender->id;

            if ($senderId == $ticketCreatorId) {
                if ($ticket->assignedTo && $ticket->assigned_to_user_id) {
                    Mail::to($ticket->assignedTo->email)->send(
                        new \App\Mail\MailNewMessageTicket($ticket->assignedTo, $message, $ticket)
                    );
                    Log::info("MailNewMessageTicket sent to assigned backoffice: " . $ticket->assignedTo->email . " for ticket #" . $ticket->ticket_number);
                } else {
                    Log::info("No assigned backoffice for ticket #" . $ticket->ticket_number . " - email not sent (ticket status: " . $ticket->status . ")");
                }
            } else {
                if ($ticket->createdBy && $ticket->createdBy->email) {
                    Mail::to($ticket->createdBy->email)->send(
                        new \App\Mail\MailNewMessageTicket($ticket->createdBy, $message, $ticket)
                    );
                    Log::info("MailNewMessageTicket sent to ticket creator: " . $ticket->createdBy->email . " for ticket #" . $ticket->ticket_number);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error sending message notification email: ' . $e->getMessage());
        }
    }

    public function getTicketChangeLogs($ticketId)
    {
        try {
            $user = Auth::user();
            $ticket = Ticket::findOrFail($ticketId);

            if (!$this->canAccessTicket($user, $ticket)) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Access denied"
                ]);
            }

            $logs = TicketChangeLog::with(['user'])
                ->where('ticket_id', $ticketId)
                ->orderBy('created_at', 'desc')
                ->get();

            $statusLabels = Ticket::getStatusOptions();
            $priorityLabels = Ticket::getPriorityOptions();
            
            $formattedLogs = $logs->map(function($log) use ($statusLabels, $priorityLabels) {
                $data = [
                    'id' => $log->id,
                    'user_name' => $log->user ? $log->user->nome . ' ' . $log->user->cognome : 'Sistema',
                    'change_type' => $log->change_type,
                    'changed_at' => $log->created_at->format('d/m/Y H:i')
                ];

                if ($log->previous_status || $log->new_status) {
                    $data['previous_status'] = $statusLabels[$log->previous_status] ?? $log->previous_status;
                    $data['new_status'] = $statusLabels[$log->new_status] ?? $log->new_status;
                }

                if ($log->previous_priority || $log->new_priority) {
                    $data['previous_priority'] = $priorityLabels[$log->previous_priority] ?? $log->previous_priority;
                    $data['new_priority'] = $priorityLabels[$log->new_priority] ?? $log->new_priority;
                }

                return $data;
            });

            return response()->json([
                "response" => "ok",
                "status" => "200", 
                "body" => ["risposta" => $formattedLogs]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting ticket change logs: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500", 
                "message" => "Server error"
            ]);
        }
    }

    private function canAccessTicket($user, $ticket)
    {
        $userRole = $user->role->id;

        if (in_array($userRole, [1, 4, 5, 6, 9, 10])) {
            return true;
        }

        return $ticket->created_by_user_id == $user->id 
            || $ticket->contract->created_by_user_id == $user->id;
    }

    private function canManageTicket($user, $ticket)
    {
        $userRole = $user->role->id;

        if (in_array($userRole, [1, 4, 5, 6, 9, 10])) {
            return true;
        }

        return false;
    }

    private function notifyTicketParticipants($ticket, $event)
    {
        try {
            $userIds = collect([
                $ticket->created_by_user_id,
                $ticket->assigned_to_user_id,
                $ticket->contract->created_by_user_id
            ])->filter()->unique()->toArray();

            $currentUserId = Auth::id();

            // Map event to Italian notification messages
            $eventMessages = [
                'new_ticket' => 'Nuovo ticket creato',
                'new_message' => 'Nuovo messaggio nel ticket',
                'status_changed' => 'Stato del ticket modificato',
            ];

            $notificationMessage = $eventMessages[$event] ?? 'Aggiornamento ticket';

            foreach ($userIds as $userId) {
                // Don't notify the user who triggered the event
                if ($userId == $currentUserId) {
                    continue;
                }

                notification::create([
                    'from_user_id' => $currentUserId,
                    'to_user_id' => $userId,
                    'reparto' => 'ticket',
                    'notifica' => $notificationMessage . ' #' . $ticket->ticket_number,
                    'visualizzato' => false,
                    'notifica_html' => '<strong>' . $notificationMessage . '</strong><br>Ticket #' . $ticket->ticket_number,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending ticket notifications: ' . $e->getMessage());
        }
    }

    public function getTicketByContractId($contractId)
    {
        try {
            $user = Auth::user();
            $ticket = Ticket::where('contract_id', $contractId)
                        ->whereNotIn('status', [Ticket::STATUS_DELETED, Ticket::STATUS_CLOSED])
                        ->with(['messages.user', 'contract'])
                        ->first();
            
            if ($ticket && $this->canAccessTicket($user, $ticket)) {
                return response()->json([
                    "response" => "ok",
                    "status" => "200",
                    "body" => ["ticket" => $ticket]
                ]);
            }
            
            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => ["ticket" => null]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting ticket by contract: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500"
            ]);
        }
    }

    public function restoreTicket(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ticket_id' => 'required|exists:tickets,id',
                'force_replace' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "response" => "error",
                    "status" => "400", 
                    "errors" => $validator->errors()
                ]);
            }

            $user = Auth::user();
            $userRole = $user->role->id;

            if (!in_array($userRole, [1, 6])) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Only administrators can restore tickets"
                ]);
            }

            $ticket = Ticket::findOrFail($request->ticket_id);
            
            if (!in_array($ticket->status, [Ticket::STATUS_CLOSED, Ticket::STATUS_DELETED])) {
                return response()->json([
                    "response" => "error",
                    "status" => "400", 
                    "message" => "Can only restore closed or deleted tickets"
                ]);
            }

            $activeTicket = Ticket::where('contract_id', $ticket->contract_id)
                                  ->whereNotIn('status', [Ticket::STATUS_DELETED, Ticket::STATUS_CLOSED])
                                  ->first();

            if ($activeTicket && !$request->force_replace) {
                return response()->json([
                    "response" => "error",
                    "status" => "409", 
                    "message" => "An active ticket exists for this contract",
                    "active_ticket" => $activeTicket
                ]);
            }

            if ($activeTicket && $request->force_replace) {
                $activeTicket->update([
                    'status' => Ticket::STATUS_DELETED,
                    'previous_status' => $activeTicket->status,
                    'deleted_at' => now()
                ]);

                TicketChangeLog::create([
                    'ticket_id' => $activeTicket->id,
                    'user_id' => $user->id,
                    'previous_status' => $activeTicket->status,
                    'new_status' => Ticket::STATUS_DELETED,
                    'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
                ]);
            }

            $oldStatus = $ticket->status;
            
            $ticket->update([
                'status' => Ticket::STATUS_NEW,
                'previous_status' => $oldStatus,
                'assigned_to_user_id' => null,
                'restored_at' => now(),
                'resolved_at' => null,
                'closed_at' => null,
                'deleted_at' => null
            ]);

            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'previous_status' => $oldStatus,
                'new_status' => Ticket::STATUS_NEW,
                'previous_priority' => null,
                'new_priority' => null,
                'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
            ]);

            $statusLabels = Ticket::getStatusOptions();
            $oldStatusLabel = $statusLabels[$oldStatus] ?? $oldStatus;
            
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => "Ticket ripristinato da stato '{$oldStatusLabel}' a 'Nuovo'",
                'message_type' => 'status_change'
            ]);

            $this->notifyTicketParticipants($ticket, 'status_changed');

            return response()->json([
                "response" => "ok",
                "status" => "200", 
                "message" => "Ticket restored successfully",
                "body" => ["risposta" => $ticket]
            ]);

        } catch (\Exception $e) {
            Log::error('Error restoring ticket: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500", 
                "message" => "Server error"
            ]);
        }
    }

    public function getAllTicketsByContractId($contractId)
    {
        try {
            $user = Auth::user();
            $userRole = $user->role->id;

            if (!in_array($userRole, [1, 6])) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Only administrators can view all tickets"
                ]);
            }

            $tickets = Ticket::where('contract_id', $contractId)
                            ->with(['messages.user', 'createdBy', 'assignedTo'])
                            ->orderBy('created_at', 'desc')
                            ->get();

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => ["tickets" => $tickets]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting all tickets by contract: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    public function deleteTicketByContractId(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'contract_id' => 'required|exists:contracts,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "response" => "error",
                    "status" => "400", 
                    "errors" => $validator->errors()
                ]);
            }

            $user = Auth::user();
            $userRole = $user->role->id;

            if (!in_array($userRole, [1, 6])) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Only administrators can delete tickets"
                ]);
            }

            $ticket = Ticket::where('contract_id', $request->contract_id)
                           ->whereNotIn('status', [Ticket::STATUS_DELETED, Ticket::STATUS_CLOSED])
                           ->first();

            if (!$ticket) {
                return response()->json([
                    "response" => "error",
                    "status" => "404", 
                    "message" => "No active ticket found for this contract"
                ]);
            }

            $oldStatus = $ticket->status;

            $ticket->update([
                'status' => Ticket::STATUS_DELETED,
                'previous_status' => $oldStatus,
                'deleted_at' => now()
            ]);

            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'previous_status' => $oldStatus,
                'new_status' => Ticket::STATUS_DELETED,
                'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
            ]);

            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => "Ticket cancellato dall'amministratore",
                'message_type' => 'status_change'
            ]);

            return response()->json([
                "response" => "ok",
                "status" => "200", 
                "message" => "Ticket deleted successfully"
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting ticket by contract: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    public function restoreLastTicketByContractId(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'contract_id' => 'required|exists:contracts,id',
                'force_replace' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "response" => "error",
                    "status" => "400", 
                    "errors" => $validator->errors()
                ]);
            }

            $user = Auth::user();
            $userRole = $user->role->id;

            if (!in_array($userRole, [1, 6])) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Only administrators can restore tickets"
                ]);
            }

            $lastTicket = Ticket::where('contract_id', $request->contract_id)
                               ->whereIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_DELETED])
                               ->orderBy('updated_at', 'desc')
                               ->first();

            if (!$lastTicket) {
                return response()->json([
                    "response" => "error",
                    "status" => "404", 
                    "message" => "No closed or deleted tickets found for this contract"
                ]);
            }

            $activeTicket = Ticket::where('contract_id', $request->contract_id)
                                  ->whereNotIn('status', [Ticket::STATUS_DELETED, Ticket::STATUS_CLOSED])
                                  ->first();

            if ($activeTicket && !$request->force_replace) {
                return response()->json([
                    "response" => "error",
                    "status" => "409", 
                    "message" => "An active ticket exists for this contract",
                    "active_ticket" => $activeTicket
                ]);
            }

            if ($activeTicket && $request->force_replace) {
                $activeTicket->update([
                    'status' => Ticket::STATUS_DELETED,
                    'previous_status' => $activeTicket->status,
                    'deleted_at' => now()
                ]);

                TicketChangeLog::create([
                    'ticket_id' => $activeTicket->id,
                    'user_id' => $user->id,
                    'previous_status' => $activeTicket->status,
                    'new_status' => Ticket::STATUS_DELETED,
                    'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
                ]);
            }

            $oldStatus = $lastTicket->status;
            $lastTicket->update([
                'status' => Ticket::STATUS_NEW,
                'previous_status' => $oldStatus,
                'assigned_to_user_id' => null,
                'restored_at' => now(),
                'resolved_at' => null,
                'closed_at' => null,
                'deleted_at' => null
            ]);

            TicketChangeLog::create([
                'ticket_id' => $lastTicket->id,
                'user_id' => $user->id,
                'previous_status' => $oldStatus,
                'new_status' => Ticket::STATUS_NEW,
                'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
            ]);

            TicketMessage::create([
                'ticket_id' => $lastTicket->id,
                'user_id' => $user->id,
                'message' => "Ultimo ticket ripristinato a stato 'Nuovo'",
                'message_type' => 'status_change'
            ]);

            return response()->json([
                "response" => "ok",
                "status" => "200", 
                "message" => "Last ticket restored successfully",
                "body" => ["risposta" => $lastTicket]
            ]);

        } catch (\Exception $e) {
            Log::error('Error restoring last ticket: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    public function uploadAttachments(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ticket_id' => 'required|exists:tickets,id',
                'message_id' => 'nullable|exists:ticket_messages,id',
                'attachments' => 'required|array',
                'attachments.*' => 'required|file|max:10240'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "response" => "error",
                    "status" => "400", 
                    "errors" => $validator->errors()
                ]);
            }

            $user = Auth::user();
            $ticket = Ticket::findOrFail($request->ticket_id);

            if (!$this->canAccessTicket($user, $ticket)) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Access denied"
                ]);
            }

            $attachments = [];
            $contractId = $ticket->contract_id;

            DB::beginTransaction();

            foreach ($request->file('attachments') as $file) {
                if (!$this->validateAttachment($file)) {
                    DB::rollBack();
                    return response()->json([
                        "response" => "error",
                        "status" => "400", 
                        "message" => "Invalid file type or size"
                    ]);
                }

                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $uniqueName = Str::random(32) . '_' . time() . '.' . $extension;

                $yearMonth = date('Y-m');
                $relativePath = "contracts/{$contractId}/tickets/{$ticket->id}/{$yearMonth}";
                $fullPath = storage_path("app/{$relativePath}");

                if (!File::exists($fullPath)) {
                    File::makeDirectory($fullPath, 0755, true);
                }

                $file->move($fullPath, $uniqueName);
                $filePath = "{$relativePath}/{$uniqueName}";

                $fileHash = hash_file('sha256', "{$fullPath}/{$uniqueName}");

                $attachment = TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'ticket_message_id' => $request->message_id,
                    'user_id' => $user->id,
                    'file_name' => $uniqueName,
                    'original_name' => $originalName,
                    'file_path' => $filePath,
                    'file_size' => filesize("{$fullPath}/{$uniqueName}"),
                    'mime_type' => $file->getClientMimeType(),
                    'hash' => $fileHash
                ]);

                $attachments[] = $attachment;
            }

            if ($request->message_id) {
                TicketMessage::where('id', $request->message_id)
                    ->update(['has_attachments' => true]);
            }

            DB::commit();

            return response()->json([
                "response" => "ok",
                "status" => "200", 
                "message" => "Attachments uploaded successfully",
                "body" => ["attachments" => $attachments]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error uploading attachments: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error: " . $e->getMessage()
            ]);
        }
    }

    public function getTicketAttachments($ticketId)
    {
        try {
            $user = Auth::user();
            $ticket = Ticket::findOrFail($ticketId);

            if (!$this->canAccessTicket($user, $ticket)) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Access denied"
                ]);
            }

            $attachments = TicketAttachment::where('ticket_id', $ticketId)
                ->with('user:id,name,cognome,email')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => ["attachments" => $attachments]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting ticket attachments: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    public function downloadAttachment($attachmentId)
    {
        try {
            $attachment = TicketAttachment::findOrFail($attachmentId);
            $user = Auth::user();
            $ticket = $attachment->ticket;

            if (!$this->canAccessTicket($user, $ticket)) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Access denied"
                ]);
            }

            $fullPath = storage_path('app/' . $attachment->file_path);

            if (!file_exists($fullPath)) {
                return response()->json([
                    "response" => "error",
                    "status" => "404",
                    "message" => "File not found"
                ]);
            }

            return response()->download($fullPath, $attachment->original_name);

        } catch (\Exception $e) {
            Log::error('Error downloading attachment: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    public function deleteAttachment($attachmentId)
    {
        try {
            $attachment = TicketAttachment::findOrFail($attachmentId);
            $user = Auth::user();
            $userRole = $user->role->id;

            if (!in_array($userRole, [1, 6]) && $attachment->user_id != $user->id) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Access denied"
                ]);
            }

            DB::beginTransaction();

            $fullPath = storage_path('app/' . $attachment->file_path);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            if ($attachment->ticket_message_id) {
                $remainingAttachments = TicketAttachment::where('ticket_message_id', $attachment->ticket_message_id)
                    ->where('id', '!=', $attachment->id)
                    ->count();
                
                if ($remainingAttachments == 0) {
                    TicketMessage::where('id', $attachment->ticket_message_id)
                        ->update(['has_attachments' => false]);
                }
            }

            $attachment->delete();

            DB::commit();

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "message" => "Attachment deleted successfully"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting attachment: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    protected function validateAttachment($file)
    {
        $maxSize = 10 * 1024 * 1024;

        $blockedExtensions = [
            'exe', 'bat', 'cmd', 'sh', 'php', 'js', 
            'jar', 'app', 'deb', 'rpm', 'dmg', 'pkg',
            'com', 'scr', 'vbs', 'msi', 'dll'
        ];

        $extension = strtolower($file->getClientOriginalExtension());

        if ($file->getSize() > $maxSize) {
            return false;
        }

        if (in_array($extension, $blockedExtensions)) {
            return false;
        }

        return true;
    }
}