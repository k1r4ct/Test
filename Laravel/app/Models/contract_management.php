<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use App\Traits\LogsDatabaseOperations;

class contract_management extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $fillable = [
        'user_id',
        'macro_product_id',
    ];

    // ==================== RELATIONSHIPS ====================

    public function user()
    {
        return $this->belongsTo(user::class);
    }

    public function macro_product()
    {
        return $this->belongsTo(macro_product::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log association creation
        static::created(function ($contractManagement) {
            $contractManagement->load(['user', 'macro_product']);
            
            $userName = $contractManagement->user 
                ? $contractManagement->user->name . ' ' . $contractManagement->user->cognome 
                : 'User #' . $contractManagement->user_id;
            
            $productName = $contractManagement->macro_product 
                ? $contractManagement->macro_product->nome 
                : 'MacroProduct #' . $contractManagement->macro_product_id;

            SystemLogService::database()->info("Contract management association created", [
                'contract_management_id' => $contractManagement->id,
                'user_id' => $contractManagement->user_id,
                'user_name' => $userName,
                'macro_product_id' => $contractManagement->macro_product_id,
                'macro_product_name' => $productName,
            ]);
        });

        // Log association deletion
        static::deleted(function ($contractManagement) {
            SystemLogService::database()->warning("Contract management association deleted", [
                'contract_management_id' => $contractManagement->id,
                'user_id' => $contractManagement->user_id,
                'macro_product_id' => $contractManagement->macro_product_id,
            ]);
        });
    }
}