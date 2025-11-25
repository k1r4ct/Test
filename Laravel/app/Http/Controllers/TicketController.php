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
    /**
     * Get tickets based on user role
     * Only Admin, BackOffice, and Web Operators can access the ticket board
     * Only Admin can see deleted tickets
     */
    public function getTickets()
    {
        try {
            $user = Auth::user();
            $userRole = $user->role->id;

            // Only Admin (1,6), BackOffice (5,10), and Web Operators (4,9) can access
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

            // Only Admin (1,6) can see deleted tickets
            // BackOffice and WebOps cannot see deleted tickets
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

    /**
     * Create new ticket
     * Can be called from the board (Admin/BackOffice/WebOp) or from contract page (SEU)
     */
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
            
            // Check if user has access to this contract
            // Admin, BackOffice, WebOp can create for any contract
            // SEU can only create for contracts they created
            if (!in_array($user->role->id, [1, 2, 4, 5])) {
                if ($contract->created_by_user_id != $user->id) {
                    return response()->json([
                        "response" => "error",
                        "status" => "403", 
                        "message" => "Access denied"
                    ]);
                }
            }

            // Check for existing tickets on this contract
            // SEU cannot create multiple tickets per contract
            $restrictedRoles = [2, 3];
            
            if (in_array($user->role->id, $restrictedRoles)) {
                $existingTicket = Ticket::where('contract_id', $request->contract_id)
                    ->where('status', '!=', Ticket::STATUS_DELETED)
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
            // Admin and BackOffice can create multiple tickets for the same contract

            $ticket = Ticket::create([
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority ?? Ticket::PRIORITY_UNASSIGNED,
                'contract_id' => $request->contract_id,
                'created_by_user_id' => $user->id,
            ]);

            // Load relationships for response and email
            $ticket->load([
                'contract.customer_data',
                'contract.product',
                'createdBy.role'
            ]);

            // Create initial system message
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => 'Ticket creato',
                'message_type' => 'status_change'
            ]);

            // Log ticket creation in changes log
            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'previous_status' => null,
                'new_status' => Ticket::STATUS_NEW,
                'previous_priority' => null,
                'new_priority' => $ticket->priority,
                'change_type' => TicketChangeLog::CHANGE_TYPE_BOTH
            ]);

            // Send notification
            $this->notifyTicketParticipants($ticket, 'new_ticket');

            // Send confirmation email to ticket creator
            try {
                Mail::to($user->email)->send(new \App\Mail\NuovoTicketCreato($user, $ticket));
                Log::info("NuovoTicketCreato email sent to: " . $user->email . " for ticket #" . $ticket->ticket_number);
            } catch (\Exception $mailException) {
                Log::error('Error sending NuovoTicketCreato email: ' . $mailException->getMessage());
                // Don't fail the ticket creation if email fails
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

    /**
     * Update ticket status
     * Special rules:
     * - Only admin can move tickets back to 'new'
     * - Only admin can move tickets to 'deleted'
     * - Sets closed_at when moving to 'closed'
     * - Sets deleted_at when moving to 'deleted'
     * - Clears timestamps when restoring
     */
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

            // Only Admin (1,6) can move tickets back to 'new'
            if ($oldStatus !== Ticket::STATUS_NEW && $newStatus === Ticket::STATUS_NEW && !in_array($userRole, [1, 6])) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Only administrators can move tickets back to 'New'"
                ]);
            }

            // Only Admin (1,6) can delete tickets
            if ($newStatus === Ticket::STATUS_DELETED && !in_array($userRole, [1, 6])) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Only administrators can delete tickets"
                ]);
            }

            // Check general permissions
            if (!$this->canManageTicket($user, $ticket)) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Access denied"
                ]);
            }

            // Prepare update data
            $updateData = [
                'previous_status' => $oldStatus,
                'status' => $newStatus,
                'assigned_to_user_id' => $user->id
            ];

            // Handle timestamp updates based on status change
            $updateData = $this->handleStatusTimestamps($updateData, $oldStatus, $newStatus);

            // Update ticket
            $ticket->update($updateData);

            // Log the status change
            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'previous_status' => $oldStatus,
                'new_status' => $newStatus,
                'previous_priority' => null,
                'new_priority' => null,
                'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
            ]);

            // Create system message about status change
            $statusLabels = Ticket::getStatusOptions();
            $statusLabel = $statusLabels[$newStatus] ?? $newStatus;
            $oldStatusLabel = $statusLabels[$oldStatus] ?? $oldStatus;
            
            Log::info("Ticket status changed to: " . $statusLabel);
            
            // Send email when ticket is resolved
            if ($newStatus === Ticket::STATUS_RESOLVED) {
                Mail::to($mailCreatoreTicket)->send(new \App\Mail\CambioStatoTicket($userCreatoreTicket, $ticket));
            }
            
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => "Stato cambiato da '{$oldStatusLabel}' a '{$statusLabel}'",
                'message_type' => 'status_change'
            ]);

            // Send notification
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

    /**
     * Handle timestamp updates based on status transitions
     * 
     * @param array $updateData Current update data
     * @param string $oldStatus Previous status
     * @param string $newStatus New status
     * @return array Updated data with timestamps
     */
    private function handleStatusTimestamps(array $updateData, string $oldStatus, string $newStatus): array
    {
        $now = now();

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
        }

        return $updateData;
    }

    /**
     * Update ticket priority
     * Priority changes are NOT shown to clients in the chat
     */
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

            // Update ticket priority
            $ticket->update([
                'priority' => $newPriority
            ]);

            // Log the priority change (NOT in ticket_messages, only in change log)
            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'previous_status' => null,
                'new_status' => null,
                'previous_priority' => $oldPriority,
                'new_priority' => $newPriority,
                'change_type' => TicketChangeLog::CHANGE_TYPE_PRIORITY
            ]);

            // Get priority labels for response
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

    /**
     * Close a ticket (move from resolved to closed)
     * Only admin or assigned backoffice can close tickets
     * Sets closed_at timestamp
     */
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

            // Only admin or assigned user can close ticket
            $isAdmin = in_array($userRole, [1, 6]);
            $isAssigned = $ticket->assigned_to_user_id == $user->id;

            if (!$isAdmin && !$isAssigned) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Only administrators or assigned users can close tickets"
                ]);
            }

            // Check if ticket is in resolved status
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
                'closed_at' => now()  // Set closed_at timestamp
            ]);

            // Log the closure
            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'previous_status' => $oldStatus,
                'new_status' => Ticket::STATUS_CLOSED,
                'previous_priority' => null,
                'new_priority' => null,
                'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
            ]);

            // Log the closure in messages
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

    /**
     * Bulk delete tickets (move to deleted status)
     * Only admin can perform this action
     * Sets deleted_at timestamp for each ticket
     */
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

            // Only Admin (1,6) can delete tickets
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
                    'deleted_at' => now(),  // Set deleted_at timestamp
                    'assigned_to_user_id' => $user->id
                ]);

                // Log the deletion
                TicketChangeLog::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'previous_status' => $oldStatus,
                    'new_status' => Ticket::STATUS_DELETED,
                    'previous_priority' => null,
                    'new_priority' => null,
                    'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
                ]);

                // Log the deletion in messages
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

    /**
     * Get messages for a specific ticket
     */
    public function getTicketMessages($ticketId)
    {
        try {
            $user = Auth::user();
            $ticket = Ticket::findOrFail($ticketId);

            // Check permissions
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

    /**
     * Send message to ticket
     */
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
            
            // Check permissions
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

            // Send notification
            $this->notifyTicketParticipants($ticket, 'new_message');

            $message->load(['user.role', 'ticket']);
            
            // Send email notification using intelligent logic
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

    /**
     * Send email notification for new message with intelligent logic
     * 
     * Rules:
     * - Never send email to the message sender themselves
     * - If sender is the ticket creator (SEU): notify assigned backoffice (if any)
     * - If sender is NOT the ticket creator (backoffice): notify the ticket creator
     * 
     * @param \App\Models\User $sender The user who sent the message
     * @param \App\Models\Ticket $ticket The ticket
     * @param \App\Models\TicketMessage $message The message that was sent
     */
    private function sendMessageNotificationEmail($sender, $ticket, $message)
    {
        try {
            $ticketCreatorId = $ticket->created_by_user_id;
            $senderId = $sender->id;

            if ($senderId == $ticketCreatorId) {
                // Sender is the ticket creator (SEU/Advisor)
                // Notify the assigned backoffice operator if one exists
                if ($ticket->assignedTo && $ticket->assigned_to_user_id) {
                    Mail::to($ticket->assignedTo->email)->send(
                        new \App\Mail\MailNewMessageTicket($ticket->assignedTo, $message, $ticket)
                    );
                    Log::info("MailNewMessageTicket sent to assigned backoffice: " . $ticket->assignedTo->email . " for ticket #" . $ticket->ticket_number);
                } else {
                    // No one assigned yet - ticket is still 'new'
                    // Don't send email - backoffice will see it in the board
                    Log::info("No assigned backoffice for ticket #" . $ticket->ticket_number . " - email not sent (ticket status: " . $ticket->status . ")");
                }
            } else {
                // Notify the ticket creator
                if ($ticket->createdBy && $ticket->createdBy->email) {
                    Mail::to($ticket->createdBy->email)->send(
                        new \App\Mail\MailNewMessageTicket($ticket->createdBy, $message, $ticket)
                    );
                    Log::info("MailNewMessageTicket sent to ticket creator: " . $ticket->createdBy->email . " for ticket #" . $ticket->ticket_number);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error sending message notification email: ' . $e->getMessage());
            // Don't fail the message send if email fails
        }
    }

    /**
     * Get complete change history for a ticket
     * Returns all status and priority changes
     */
    public function getTicketChangeLogs($ticketId)
    {
        try {
            $user = Auth::user();
            $ticket = Ticket::findOrFail($ticketId);

            // Check permissions
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

            // Format logs with labels
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

    /**
     * Check if user can access ticket
     * Admin and BackOffice can access all tickets
     * SEU can access tickets they created or for their contracts
     */
    private function canAccessTicket($user, $ticket)
    {
        $userRole = $user->role->id;

        // Admin, BackOffice, and Web Operators can access all tickets
        if (in_array($userRole, [1, 4, 5, 6, 9, 10])) {
            return true;
        }

        // SEU can access if they created the ticket or own the contract
        return $ticket->created_by_user_id == $user->id 
            || $ticket->contract->created_by_user_id == $user->id;
    }

    /**
     * Check if user can manage ticket (change status, etc.)
     * Admin and BackOffice can manage all tickets
     */
    private function canManageTicket($user, $ticket)
    {
        $userRole = $user->role->id;

        // Admin, BackOffice, and Web Operators can manage all tickets
        if (in_array($userRole, [1, 4, 5, 6, 9, 10])) {
            return true;
        }

        // SEU cannot manage tickets from the board
        return false;
    }

    /**
     * Notify ticket participants about updates
     */
    private function notifyTicketParticipants($ticket, $event)
    {
        try {
            // Get all unique users involved with this ticket
            $userIds = collect([
                $ticket->created_by_user_id,
                $ticket->assigned_to_user_id,
                $ticket->contract->created_by_user_id
            ])->filter()->unique()->toArray();

            // Get current user to exclude from notifications
            $currentUserId = Auth::id();

            foreach ($userIds as $userId) {
                // Don't notify the user who triggered the action
                if ($userId == $currentUserId) {
                    continue;
                }

                notification::create([
                    'user_id' => $userId,
                    'type' => 'ticket_' . $event,
                    'data' => json_encode([
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'title' => $ticket->title,
                        'event' => $event
                    ]),
                    'read' => false
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending notifications: ' . $e->getMessage());
        }
    }

    /**
     * Get active ticket by contract ID
     * Used to check if an active ticket exists for a contract
     * Excludes closed and deleted tickets to allow new ticket creation
     */
    public function getTicketByContractId($contractId)
    {
        try {
            $user = Auth::user();
            // Exclude both deleted AND closed tickets
            // This allows creating a new ticket once the previous one is closed
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

    /**
     * Restore a closed or deleted ticket
     * Only administrators can perform this action
     * Sets restored_at timestamp and clears closed_at/deleted_at
     */
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

            // Only Admin (1,6) can restore tickets
            if (!in_array($userRole, [1, 6])) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Only administrators can restore tickets"
                ]);
            }

            $ticket = Ticket::findOrFail($request->ticket_id);
            
            // Can only restore closed or deleted tickets
            if (!in_array($ticket->status, [Ticket::STATUS_CLOSED, Ticket::STATUS_DELETED])) {
                return response()->json([
                    "response" => "error",
                    "status" => "400", 
                    "message" => "Can only restore closed or deleted tickets"
                ]);
            }

            // Check if there's an active ticket for this contract
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

            // If force_replace, delete the active ticket
            if ($activeTicket && $request->force_replace) {
                $activeTicket->update([
                    'status' => Ticket::STATUS_DELETED,
                    'previous_status' => $activeTicket->status,
                    'deleted_at' => now()
                ]);

                // Log the deletion
                TicketChangeLog::create([
                    'ticket_id' => $activeTicket->id,
                    'user_id' => $user->id,
                    'previous_status' => $activeTicket->status,
                    'new_status' => Ticket::STATUS_DELETED,
                    'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
                ]);
            }

            $oldStatus = $ticket->status;
            
            // Restore to 'new' status
            $ticket->update([
                'status' => Ticket::STATUS_NEW,
                'previous_status' => $oldStatus,
                'assigned_to_user_id' => null,
                'restored_at' => now(),    // Set restored_at timestamp
                'closed_at' => null,       // Clear closed_at
                'deleted_at' => null       // Clear deleted_at
            ]);

            // Log the restoration
            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'previous_status' => $oldStatus,
                'new_status' => Ticket::STATUS_NEW,
                'previous_priority' => null,
                'new_priority' => null,
                'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
            ]);

            // Add system message
            $statusLabels = Ticket::getStatusOptions();
            $oldStatusLabel = $statusLabels[$oldStatus] ?? $oldStatus;
            
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => "Ticket ripristinato da stato '{$oldStatusLabel}' a 'Nuovo'",
                'message_type' => 'status_change'
            ]);

            // Send notification
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

    /**
     * Get all tickets for a specific contract
     * Admin only - includes closed and deleted tickets
     */
    public function getAllTicketsByContractId($contractId)
    {
        try {
            $user = Auth::user();
            $userRole = $user->role->id;

            // Only Admin can see all tickets including deleted
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

    /**
     * Delete active ticket for a contract
     * Admin only - sets deleted_at timestamp
     */
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

            // Only Admin can delete tickets
            if (!in_array($userRole, [1, 6])) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Only administrators can delete tickets"
                ]);
            }

            // Find active ticket
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
                'deleted_at' => now()  // Set deleted_at timestamp
            ]);

            // Log the deletion
            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'previous_status' => $oldStatus,
                'new_status' => Ticket::STATUS_DELETED,
                'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
            ]);

            // Add system message
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

    /**
     * Restore the last closed/deleted ticket for a contract
     * Admin only - sets restored_at and clears closed_at/deleted_at
     */
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

            // Only Admin can restore tickets
            if (!in_array($userRole, [1, 6])) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Only administrators can restore tickets"
                ]);
            }

            // Find the last closed/deleted ticket
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

            // Check for active ticket
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

            // If force_replace, delete the active ticket
            if ($activeTicket && $request->force_replace) {
                $activeTicket->update([
                    'status' => Ticket::STATUS_DELETED,
                    'previous_status' => $activeTicket->status,
                    'deleted_at' => now()
                ]);

                // Log the deletion
                TicketChangeLog::create([
                    'ticket_id' => $activeTicket->id,
                    'user_id' => $user->id,
                    'previous_status' => $activeTicket->status,
                    'new_status' => Ticket::STATUS_DELETED,
                    'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
                ]);
            }

            // Restore the last ticket to 'new' status
            $oldStatus = $lastTicket->status;
            $lastTicket->update([
                'status' => Ticket::STATUS_NEW,
                'previous_status' => $oldStatus,
                'assigned_to_user_id' => null,
                'restored_at' => now(),    // Set restored_at timestamp
                'closed_at' => null,       // Clear closed_at
                'deleted_at' => null       // Clear deleted_at
            ]);

            // Log the restoration
            TicketChangeLog::create([
                'ticket_id' => $lastTicket->id,
                'user_id' => $user->id,
                'previous_status' => $oldStatus,
                'new_status' => Ticket::STATUS_NEW,
                'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS
            ]);

            // Add system message
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

    /**
     * Upload attachments for a ticket message
     */
    public function uploadAttachments(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ticket_id' => 'required|exists:tickets,id',
                'message_id' => 'nullable|exists:ticket_messages,id',
                'attachments' => 'required|array',
                'attachments.*' => 'required|file|max:10240' // 10MB max per file
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

            // Check permissions using your existing method
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
                // Validate file type and size
                if (!$this->validateAttachment($file)) {
                    DB::rollBack();
                    return response()->json([
                        "response" => "error",
                        "status" => "400", 
                        "message" => "Invalid file type or size"
                    ]);
                }

                // Generate unique filename
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $uniqueName = Str::random(32) . '_' . time() . '.' . $extension;

                // Create directory structure
                $yearMonth = date('Y-m');
                $relativePath = "contracts/{$contractId}/tickets/{$ticket->id}/{$yearMonth}";
                $fullPath = storage_path("app/{$relativePath}");

                // Create directories if they don't exist
                if (!File::exists($fullPath)) {
                    File::makeDirectory($fullPath, 0755, true);
                }

                // Move file
                $file->move($fullPath, $uniqueName);
                $filePath = "{$relativePath}/{$uniqueName}";

                // Calculate hash
                $fileHash = hash_file('sha256', "{$fullPath}/{$uniqueName}");

                // Save to database
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

            // Update message flag if message_id provided
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

    /**
     * Get attachments for a ticket
     */
    public function getTicketAttachments($ticketId)
    {
        try {
            $user = Auth::user();
            $ticket = Ticket::findOrFail($ticketId);

            // Check permissions using your existing method
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

    /**
     * Download an attachment
     */
    public function downloadAttachment($attachmentId)
    {
        try {
            $attachment = TicketAttachment::findOrFail($attachmentId);
            $user = Auth::user();
            $ticket = $attachment->ticket;

            // Check permissions using your existing method
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

    /**
     * Delete an attachment
     */
    public function deleteAttachment($attachmentId)
    {
        try {
            $attachment = TicketAttachment::findOrFail($attachmentId);
            $user = Auth::user();
            $userRole = $user->role->id;

            // Only admin or the uploader can delete
            if (!in_array($userRole, [1, 6]) && $attachment->user_id != $user->id) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Access denied"
                ]);
            }

            DB::beginTransaction();

            // Delete physical file
            $fullPath = storage_path('app/' . $attachment->file_path);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // Update message flag if this was the last attachment for the message
            if ($attachment->ticket_message_id) {
                $remainingAttachments = TicketAttachment::where('ticket_message_id', $attachment->ticket_message_id)
                    ->where('id', '!=', $attachment->id)
                    ->count();
                
                if ($remainingAttachments == 0) {
                    TicketMessage::where('id', $attachment->ticket_message_id)
                        ->update(['has_attachments' => false]);
                }
            }

            // Delete database record
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

    /**
     * Validate attachment file
     */
    protected function validateAttachment($file)
    {
        // Maximum file size: 10MB
        $maxSize = 10 * 1024 * 1024;

        // Blocked extensions for security
        $blockedExtensions = [
            'exe', 'bat', 'cmd', 'sh', 'php', 'js', 
            'jar', 'app', 'deb', 'rpm', 'dmg', 'pkg',
            'com', 'scr', 'vbs', 'msi', 'dll'
        ];

        $extension = strtolower($file->getClientOriginalExtension());

        // Check file size
        if ($file->getSize() > $maxSize) {
            return false;
        }

        // Check if extension is blocked
        if (in_array($extension, $blockedExtensions)) {
            return false;
        }

        return true;
    }
}