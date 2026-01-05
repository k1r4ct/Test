<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

class payment_mode extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_pagamento',
        'payment_type',
    ];

    protected $casts = [
        'payment_type' => 'string',
    ];

    // ==================== RELATIONSHIPS ====================

    public function contract()
    {
        return $this->hasMany(contract::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'payment_mode_id');
    }

    // ==================== HELPER METHODS ====================

    public function isInstant()
    {
        return $this->payment_type === 'instant';
    }

    public function isElectronic()
    {
        return $this->payment_type === 'electronic';
    }

    public function isManual()
    {
        return $this->payment_type === 'manual';
    }

    // ==================== SCOPES ====================

    public function scopeInstant($query)
    {
        return $query->where('payment_type', 'instant');
    }

    public function scopeElectronic($query)
    {
        return $query->where('payment_type', 'electronic');
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log payment mode creation
        static::created(function ($paymentMode) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Payment mode created", [
                'payment_mode_id' => $paymentMode->id,
                'tipo_pagamento' => $paymentMode->tipo_pagamento,
                'payment_type' => $paymentMode->payment_type,
                'created_by' => $userName,
            ]);
        });

        // Log payment mode updates
        static::updated(function ($paymentMode) {
            $changes = $paymentMode->getChanges();
            $original = $paymentMode->getOriginal();

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

                SystemLogService::database()->warning("Payment mode updated", [
                    'payment_mode_id' => $paymentMode->id,
                    'tipo_pagamento' => $paymentMode->tipo_pagamento,
                    'changes' => $changesForLog,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log payment mode deletion
        static::deleted(function ($paymentMode) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->warning("Payment mode deleted", [
                'payment_mode_id' => $paymentMode->id,
                'tipo_pagamento' => $paymentMode->tipo_pagamento,
                'payment_type' => $paymentMode->payment_type,
                'deleted_by' => $userName,
            ]);
        });
    }
}