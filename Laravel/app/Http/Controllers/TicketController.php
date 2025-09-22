<?php
// File: Laravel/app/Http/Controllers/TicketController.php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
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
     * Admins and BackOffice see all tickets
     * Advisors see only tickets for their contracts
     */
    public function getTickets()
    {
        try {
            $user = Auth::user();
            $userRole = $user->role->id;

            $query = Ticket::with([
                'contract.customer_data',
                'contract.product',
                'createdBy.role',
                'assignedTo.role',
                'messages.user.role'
            ]);

            // Filter based on user role
            if ($userRole == 1 || $userRole == 5) {
                // Admin (1) or BackOffice (5) - see all tickets
                $tickets = $query->orderBy('created_at', 'desc')->get();
            } else if ($userRole == 2 || $userRole == 4) {
                // Advisor (2) or Operatore web (4) - see only their contract tickets
                $contractIds = contract::where('associato_a_user_id', $user->id)
                    ->pluck('id')
                    ->toArray();
                
                $tickets = $query->whereIn('contract_id', $contractIds)
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                // Other roles don't have access
                return response()->json([
                    "response" => "error", 
                    "status" => "403", 
                    "message" => "Access denied"
                ]);
            }

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
                "message" => "Server error"
            ]);
        }
    }

    /**
     * Create a new ticket
     */
    public function createTicket(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'priority' => 'required|in:low,medium,high',
                'contract_id' => 'required|exists:contracts,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "response" => "error",
                    "status" => "400", 
                    "message" => "Validation failed",
                    "errors" => $validator->errors()
                ]);
            }

            $user = Auth::user();

            // Check if user can create tickets for this contract
            if (!$this->canAccessContract($user, $request->contract_id)) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Access denied to this contract"
                ]);
            }

            $ticket = Ticket::create([
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority,
                'contract_id' => $request->contract_id,
                'created_by_user_id' => $user->id,
                'status' => 'new'
            ]);

            // Create initial message with the description
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $request->description,
                'message_type' => 'text'
            ]);

            // Send notification to backoffice users
            $this->notifyBackofficeUsers($ticket, 'new_ticket');

            // Load relationships for response
            $ticket->load([
                'contract.customer_data',
                'contract.product',
                'createdBy.role'
            ]);

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
     */
    public function updateTicketStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ticket_id' => 'required|exists:tickets,id',
                'status' => 'required|in:new,in-progress,waiting,resolved'
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
            if (!$this->canManageTicket($user, $ticket)) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Access denied"
                ]);
            }

            $oldStatus = $ticket->status;
            $ticket->update([
                'status' => $request->status,
                'assigned_to_user_id' => $user->id // Assign to current user when status changes
            ]);

            // Create system message about status change
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => "Stato cambiato da '{$oldStatus}' a '{$request->status}'",
                'message_type' => 'text'
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

            // Update ticket status to in-progress if it's new
            if ($ticket->status === 'new') {
                $ticket->update([
                    'status' => 'in-progress',
                    'assigned_to_user_id' => $user->id
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
     * Get tickets for a specific contract (for advisor interface)
     */
    public function getTicketsByContract($contractId)
    {
        try {
            $user = Auth::user();

            // Check if user can access this contract
            if (!$this->canAccessContract($user, $contractId)) {
                return response()->json([
                    "response" => "error",
                    "status" => "403", 
                    "message" => "Access denied"
                ]);
            }

            $tickets = Ticket::with([
                'createdBy.role',
                'assignedTo.role',
                'messages' => function($query) {
                    $query->latest()->limit(1);
                }
            ])
            ->where('contract_id', $contractId)
            ->orderBy('created_at', 'desc')
            ->get();

            return response()->json([
                "response" => "ok",
                "status" => "200", 
                "body" => ["risposta" => $tickets]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting tickets by contract: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500", 
                "message" => "Server error"
            ]);
        }
    }

    /**
     * Ticket statistics for dashboard and get API
     */
    public function getTicketStats()
    {
        try {
            $user = Auth::user();
            $userRole = $user->role->id;

            $query = Ticket::query();

            // Filter based on user role
            if ($userRole == 2 || $userRole == 4) {
                // Advisors see only their tickets
                $contractIds = contract::where('associato_a_user_id', $user->id)->pluck('id');
                $query->whereIn('contract_id', $contractIds);
            }

            $stats = [
                'total' => $query->count(),
                'new' => (clone $query)->where('status', 'new')->count(),
                'in_progress' => (clone $query)->where('status', 'in-progress')->count(),
                'waiting' => (clone $query)->where('status', 'waiting')->count(),
                'resolved' => (clone $query)->where('status', 'resolved')->count(),
                'high_priority' => (clone $query)->where('priority', 'high')->count(),
            ];

            return response()->json([
                "response" => "ok",
                "status" => "200", 
                "body" => ["risposta" => $stats]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting ticket stats: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500", 
                "message" => "Server error"
            ]);
        }
    }

    // Helper methods
    private function canAccessContract($user, $contractId)
    {
        $userRole = $user->role->id;
        
        // Admin and BackOfficers can access all contracts
        if ($userRole == 1 || $userRole == 5) {
            return true;
        }
        
        // Advisors can access only their contracts
        if ($userRole == 2 || $userRole == 4) {
            return contract::where('id', $contractId)
                ->where('associato_a_user_id', $user->id)
                ->exists();
        }
        
        return false;
    }

    private function canAccessTicket($user, $ticket)
    {
        return $this->canAccessContract($user, $ticket->contract_id);
    }

    private function canManageTicket($user, $ticket)
    {
        $userRole = $user->role->id;
        
        // Only Admins and BackOfficers can manage all tickets
        if ($userRole == 1 || $userRole == 5) {
            return true;
        }
        
        return false;
    }

    private function notifyBackofficeUsers($ticket, $type)
    {
        $backofficeUsers = \App\Models\User::whereHas('role', function($query) {
            $query->whereIn('id', [1, 5]); // Admin and BackOffice
        })->get();

        foreach ($backofficeUsers as $user) {
            notification::create([
                'from_user_id' => Auth::id(),
                'to_user_id' => $user->id,
                'reparto' => 'Ticket',
                'notifica' => "Nuovo ticket #{$ticket->ticket_number}: {$ticket->title}",
                'visualizzato' => 0
            ]);
        }
    }

    private function notifyTicketParticipants($ticket, $type)
    {
        $participantIds = TicketMessage::where('ticket_id', $ticket->id)
            ->distinct()
            ->pluck('user_id')
            ->push($ticket->created_by_user_id)
            ->unique()
            ->filter(function($id) {
                return $id != Auth::id(); // Don't notify current user
            });

        foreach ($participantIds as $userId) {
            notification::create([
                'from_user_id' => Auth::id(),
                'to_user_id' => $userId,
                'reparto' => 'Ticket',
                'notifica' => "Aggiornamento ticket #{$ticket->ticket_number}",
                'visualizzato' => 0
            ]);
        }
    }
}