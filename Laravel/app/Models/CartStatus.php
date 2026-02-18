<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsDatabaseOperations;

class CartStatus extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $fillable = [
        'status_name',
        'description',
    ];

    // ==================== RELATIONSHIPS ====================

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log cart status creation
        static::created(function ($status) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->info("Cart status created", [
                'cart_status_id' => $status->id,
                'status_name' => $status->status_name,
                'description' => $status->description,
                'created_by' => $userName,
            ]);
        });

        // Log cart status updates
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

                SystemLogService::ecommerce()->warning("Cart status updated", [
                    'cart_status_id' => $status->id,
                    'status_name' => $status->status_name,
                    'changes' => $changesForLog,
                    'affected_cart_items_count' => $status->cartItems()->count(),
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log cart status deletion
        static::deleted(function ($status) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->warning("Cart status deleted", [
                'cart_status_id' => $status->id,
                'status_name' => $status->status_name,
                'deleted_by' => $userName,
            ]);
        });
    }

    // ==================== SCOPES ====================

    public function scopeByName($query, string $name)
    {
        return $query->where('status_name', $name);
    }

    // ==================== STATIC HELPER METHODS ====================

    public static function findByName(string $name): ?self
    {
        return static::where('status_name', $name)->first();
    }

    public static function getActiveStatus(): ?self
    {
        return static::findByName('attivo');
    }

    public static function getPendingStatus(): ?self
    {
        return static::findByName('in_attesa_di_pagamento');
    }

    public static function getCompletedStatus(): ?self
    {
        return static::findByName('completato');
    }
}