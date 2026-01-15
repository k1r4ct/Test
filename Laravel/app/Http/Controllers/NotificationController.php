<?php

namespace App\Http\Controllers;

use App\Models\notification;
use App\Models\Ticket;
use App\Models\contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\SystemLogService;

class NotificationController extends Controller
{
    /**
     * Get notifications for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 20);
            $onlyUnread = $request->get('only_unread', false);

            $query = notification::with(['fromUser'])
                ->where('to_user_id', $user->id)
                ->orderBy('created_at', 'desc');

            if ($onlyUnread) {
                $query->where('visualizzato', false);
            }

            $notifications = $query->paginate($perPage);

            // Transform notifications to include entity data
            $notifications->getCollection()->transform(function ($notification) {
                return $this->transformNotification($notification);
            });

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "notifications" => $notifications,
                    "unread_count" => notification::where('to_user_id', Auth::id())
                        ->where('visualizzato', false)
                        ->count()
                ]
            ]);

        } catch (\Exception $e) {
            SystemLogService::application()->error('Error getting notifications', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    /**
     * Get unread notification count for badge
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount()
    {
        try {
            $count = notification::where('to_user_id', Auth::id())
                ->where('visualizzato', false)
                ->count();

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "count" => $count
                ]
            ]);

        } catch (\Exception $e) {
            SystemLogService::application()->error('Error getting unread count', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    /**
     * Get recent notifications (for dropdown)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recent(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);
            $limit = min($limit, 50); // Max 50 notifications

            $notifications = notification::with(['fromUser'])
                ->where('to_user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            // Transform notifications
            $notifications = $notifications->map(function ($notification) {
                return $this->transformNotification($notification);
            });

            $unreadCount = notification::where('to_user_id', Auth::id())
                ->where('visualizzato', false)
                ->count();

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "notifications" => $notifications,
                    "unread_count" => $unreadCount
                ]
            ]);

        } catch (\Exception $e) {
            SystemLogService::application()->error('Error getting recent notifications', [
                'user_id' => Auth::id(),
                'limit' => $limit ?? 10,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    /**
     * Mark a single notification as read
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        try {
            $notification = notification::where('id', $id)
                ->where('to_user_id', Auth::id())
                ->first();

            if (!$notification) {
                return response()->json([
                    "response" => "error",
                    "status" => "404",
                    "message" => "Notification not found"
                ]);
            }

            $notification->update(['visualizzato' => true]);

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "message" => "Notification marked as read",
                "body" => [
                    "notification" => $this->transformNotification($notification)
                ]
            ]);

        } catch (\Exception $e) {
            SystemLogService::application()->error('Error marking notification as read', [
                'user_id' => Auth::id(),
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    /**
     * Mark all notifications as read
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        try {
            $updated = notification::where('to_user_id', Auth::id())
                ->where('visualizzato', false)
                ->update(['visualizzato' => true]);

            SystemLogService::userActivity()->info('All notifications marked as read', [
                'user_id' => Auth::id(),
                'updated_count' => $updated,
            ]);

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "message" => "All notifications marked as read",
                "body" => [
                    "updated_count" => $updated
                ]
            ]);

        } catch (\Exception $e) {
            SystemLogService::application()->error('Error marking all notifications as read', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    /**
     * Delete a notification
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $notification = notification::where('id', $id)
                ->where('to_user_id', Auth::id())
                ->first();

            if (!$notification) {
                return response()->json([
                    "response" => "error",
                    "status" => "404",
                    "message" => "Notification not found"
                ]);
            }

            $notification->delete();

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "message" => "Notification deleted"
            ]);

        } catch (\Exception $e) {
            SystemLogService::application()->error('Error deleting notification', [
                'user_id' => Auth::id(),
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    /**
     * Delete all read notifications
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAllRead()
    {
        try {
            $deleted = notification::where('to_user_id', Auth::id())
                ->where('visualizzato', true)
                ->delete();

            SystemLogService::userActivity()->info('Read notifications deleted', [
                'user_id' => Auth::id(),
                'deleted_count' => $deleted,
            ]);

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "message" => "Read notifications deleted",
                "body" => [
                    "deleted_count" => $deleted
                ]
            ]);

        } catch (\Exception $e) {
            SystemLogService::application()->error('Error deleting read notifications', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    /**
     * Get notification detail with entity data
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $notification = notification::with(['fromUser'])
                ->where('id', $id)
                ->where('to_user_id', Auth::id())
                ->first();

            if (!$notification) {
                return response()->json([
                    "response" => "error",
                    "status" => "404",
                    "message" => "Notification not found"
                ]);
            }

            // Mark as read when viewed
            if (!$notification->visualizzato) {
                $notification->update(['visualizzato' => true]);
            }

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "notification" => $this->transformNotification($notification, true)
                ]
            ]);

        } catch (\Exception $e) {
            SystemLogService::application()->error('Error getting notification', [
                'user_id' => Auth::id(),
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    /**
     * Transform notification for API response
     * 
     * @param notification $notification
     * @param bool $includeFullEntity
     * @return array
     */
    private function transformNotification(notification $notification, bool $includeFullEntity = false): array
    {
        $data = [
            'id' => $notification->id,
            'from_user_id' => $notification->from_user_id,
            'from_user_name' => $this->getUserName($notification->fromUser),
            'reparto' => $notification->reparto,
            'notifica' => $notification->notifica,
            'notifica_html' => $notification->notifica_html,
            'visualizzato' => $notification->visualizzato,
            'type' => $notification->type,
            'type_label' => $notification->type_label ?? $this->getTypeLabel($notification->type),
            'icon' => $notification->icon ?? $this->getTypeIcon($notification->type),
            'entity_type' => $notification->entity_type,
            'entity_id' => $notification->entity_id,
            'created_at' => $notification->created_at,
            'created_at_human' => $notification->created_at ? $notification->created_at->diffForHumans() : null,
            'updated_at' => $notification->updated_at,
        ];

        // Add action URL based on entity type
        $data['action_url'] = $this->getActionUrl($notification);

        // Include full entity data if requested
        if ($includeFullEntity && $notification->entity_id) {
            if ($notification->entity_type === 'ticket') {
                $ticket = Ticket::with(['contract.customer_data', 'createdBy', 'assignedTo'])
                    ->find($notification->entity_id);
                if ($ticket) {
                    $data['entity'] = [
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'title' => $ticket->title,
                        'status' => $ticket->status,
                        'contract_id' => $ticket->contract_id,
                        'customer_name' => $ticket->customer_name,
                    ];
                }
            } elseif ($notification->entity_type === 'contract') {
                $contract = contract::with(['customer_data', 'status_contract', 'product'])
                    ->find($notification->entity_id);
                if ($contract) {
                    $data['entity'] = [
                        'id' => $contract->id,
                        'codice_contratto' => $contract->codice_contratto,
                        'status' => $contract->status_contract ? $contract->status_contract->micro_stato : null,
                        'customer_name' => $this->getCustomerName($contract->customer_data),
                        'product_name' => $contract->product ? $contract->product->descrizione : null,
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Get user display name
     */
    private function getUserName($user): string
    {
        if (!$user) {
            return 'Sistema';
        }

        if ($user->name && $user->cognome) {
            return $user->name . ' ' . $user->cognome;
        }

        if (!empty($user->ragione_sociale)) {
            return $user->ragione_sociale;
        }

        return $user->email ?? 'Utente';
    }

    /**
     * Get customer display name
     */
    private function getCustomerName($customer): string
    {
        if (!$customer) {
            return 'N/A';
        }

        if ($customer->nome && $customer->cognome) {
            return $customer->nome . ' ' . $customer->cognome;
        }

        if (!empty($customer->ragione_sociale)) {
            return $customer->ragione_sociale;
        }

        return 'N/A';
    }

    /**
     * Get action URL for notification click
     */
    private function getActionUrl(notification $notification): ?string
    {
        if (!$notification->entity_type || !$notification->entity_id) {
            return null;
        }

        switch ($notification->entity_type) {
            case 'ticket':
                // For ticket notifications, we'll return a special URL that frontend can handle
                // This could open a modal or navigate to ticket management
                return "/ticket/{$notification->entity_id}";
            
            case 'contract':
                // For contract notifications, navigate to contracts page
                return "/contratti/{$notification->entity_id}";
            
            default:
                return null;
        }
    }

    /**
     * Get type label for notification type
     */
    private function getTypeLabel(?string $type): string
    {
        $labels = [
            'ticket_new' => 'Nuovo Ticket',
            'ticket_assigned' => 'Ticket Assegnato',
            'ticket_message' => 'Nuovo Messaggio',
            'ticket_waiting' => 'Ticket in Lavorazione',
            'ticket_resolved' => 'Ticket Risolto',
            'ticket_closed' => 'Ticket Chiuso',
            'contract_status' => 'Cambio Stato Contratto',
        ];

        return $labels[$type] ?? 'Notifica';
    }

    /**
     * Get icon for notification type
     */
    private function getTypeIcon(?string $type): string
    {
        $icons = [
            'ticket_new' => 'confirmation_number',
            'ticket_assigned' => 'assignment_ind',
            'ticket_message' => 'chat',
            'ticket_waiting' => 'hourglass_empty',
            'ticket_resolved' => 'check_circle',
            'ticket_closed' => 'archive',
            'contract_status' => 'description',
        ];

        return $icons[$type] ?? 'notifications';
    }
}