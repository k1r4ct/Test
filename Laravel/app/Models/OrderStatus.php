<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsDatabaseOperations;

class OrderStatus extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $fillable = [
        'status_name',
        'description',
    ];

    // ==================== RELATIONSHIPS ====================

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log status creation
        static::created(function ($status) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->info("Order status created", [
                'order_status_id' => $status->id,
                'status_name' => $status->status_name,
                'description' => $status->description,
                'created_by' => $userName,
            ]);
        });

        // Log status updates
        static::updated(function ($status) {
            $changes = $status->getChanges();
            $original = $status->getOriginal();

            $changesForLog = [];
            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $changesForLog[$field] = [
                        'old' => $original[$field] ?? null,
                        'new' => $newValue,
                    ];
                }
            }

            if (!empty($changesForLog)) {
                $userName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';

                SystemLogService::ecommerce()->warning("Order status updated", [
                    'order_status_id' => $status->id,
                    'status_name' => $status->status_name,
                    'changes' => $changesForLog,
                    'affected_orders_count' => $status->orders()->count(),
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log status deletion
        static::deleted(function ($status) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->warning("Order status deleted", [
                'order_status_id' => $status->id,
                'status_name' => $status->status_name,
                'deleted_by' => $userName,
            ]);
        });
    }
}