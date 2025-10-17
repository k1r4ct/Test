<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketChangeLog;
use App\Models\contract;
use App\Models\notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
            ]);

            // Only Admin (1,6) can see deleted tickets
            // BackOffice and WebOps cannot see deleted tickets
            if (!in_array($userRole, [1, 6])) {
                $query->where('status', '!=', 'deleted');
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
            if (!in_array($user->role->id, [1, 4, 5, 6, 9, 10])) {
                if ($contract->created_by_user_id != $user->id) {
                    return response()->json([
                        "response" => "error",
                        "status" => "403", 
                        "message" => "Access denied"
                    ]);
                }
            }

            $ticket = Ticket::create([
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority ?? 'unassigned',
                'contract_id' => $request->contract_id,
                'created_by_user_id' => $user->id,
            ]);

            // Load relationships for response
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
                'new_status' => 'new',
                'previous_priority' => null,
                'new_priority' => $ticket->priority,
                'change_type' => 'both'
            ]);

            // Send notification
            $this->notifyTicketParticipants($ticket, 'new_ticket');

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
            $ticket = Ticket::findOrFail($request->ticket_id);

            $oldStatus = $ticket->status;
            $newStatus = $request->status;

            // Only Admin (1,6) can move tickets back to 'new'
            if ($oldStatus !== 'new' && $newStatus === 'new' && !in_array($userRole, [1, 6])) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Only administrators can move tickets back to 'New'"
                ]);
            }

            // Only Admin (1,6) can delete tickets
            if ($newStatus === 'deleted' && !in_array($userRole, [1, 6])) {
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

            // Update ticket status WITH previous_status
            $ticket->update([
                'previous_status' => $oldStatus,
                'status' => $newStatus,
                'assigned_to_user_id' => $user->id
            ]);

            // Log the status change
            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'previous_status' => $oldStatus,
                'new_status' => $newStatus,
                'previous_priority' => null,
                'new_priority' => null,
                'change_type' => 'status'
            ]);

            // Create system message about status change
            $statusLabels = Ticket::getStatusOptions();
            $statusLabel = $statusLabels[$newStatus] ?? $newStatus;
            $oldStatusLabel = $statusLabels[$oldStatus] ?? $oldStatus;
            
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
                'change_type' => 'priority'
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
            if ($ticket->status !== 'resolved') {
                return response()->json([
                    "response" => "error",
                    "status" => "400", 
                    "message" => "Only resolved tickets can be closed"
                ]);
            }

            $oldStatus = $ticket->status;

            $ticket->update([
                'previous_status' => $oldStatus,
                'status' => 'closed'
            ]);

            // Log the closure
            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'previous_status' => $oldStatus,
                'new_status' => 'closed',
                'previous_priority' => null,
                'new_priority' => null,
                'change_type' => 'status'
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
                    'status' => 'deleted',
                    'assigned_to_user_id' => $user->id
                ]);

                // Log the deletion
                TicketChangeLog::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'previous_status' => $oldStatus,
                    'new_status' => 'deleted',
                    'previous_priority' => null,
                    'new_priority' => null,
                    'change_type' => 'status'
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
            $ticket = Ticket::findOrFail($request->ticket_id);

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

            // Update ticket status to waiting if it's new WITH previous_status
            if ($ticket->status === 'new') {
                $oldStatus = $ticket->status;
                
                $ticket->update([
                    'previous_status' => $oldStatus,
                    'status' => 'waiting',
                    'assigned_to_user_id' => $user->id
                ]);

                // Log the automatic status change
                TicketChangeLog::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'previous_status' => $oldStatus,
                    'new_status' => 'waiting',
                    'previous_priority' => null,
                    'new_priority' => null,
                    'change_type' => 'status'
                ]);
            }

            // Send notification
            $this->notifyTicketParticipants($ticket, 'new_message');

            $message->load(['user.role']);

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
     * Get complete change history for a ticket
     * Returns all status and priority changes
     * OPTIONAL: Use this to show full history to administrators
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
                    'user_name' => $log->user->nome . ' ' . $log->user->cognome,
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
}