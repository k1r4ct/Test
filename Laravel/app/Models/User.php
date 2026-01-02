<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPassword;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'qualification_id',
        'codice',
        'name',
        'cognome',
        'ragione_sociale',
        'email',
        'pec',
        'password',
        'codice_fiscale',
        'partita_iva',
        'telefono',
        'cellulare',
        'indirizzo',
        'citta',
        'cap',
        'provincia',
        'nazione',
        'stato_user',
        'punti_valore_maturati',
        'punti_carriera_maturati',
        'user_id_padre',
        'punti_bonus',
        'punti_spesi',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'punti_valore_maturati' => 'integer',
        'punti_carriera_maturati' => 'integer',
        'punti_bonus' => 'integer',
        'punti_spesi' => 'integer',
    ];

    /**
     * Appends - Virtual attributes
     */
    protected $appends = ['pv_bloccati', 'pv_disponibili', 'pv_totali'];

    /**
     * Sensitive fields to mask in logs
     */
    protected static $sensitiveFields = ['codice_fiscale', 'partita_iva', 'telefono', 'cellulare'];

    /**
     * Critical fields that warrant warning level logging
     */
    protected static $criticalFields = [
        'role_id', 
        'qualification_id', 
        'stato_user', 
        'punti_valore_maturati', 
        'punti_carriera_maturati',
        'punti_bonus',
        'punti_spesi',
        'user_id_padre',
    ];

    /**
     * Fields to exclude from logging
     */
    protected static $excludeFromLog = ['password', 'remember_token'];

    // ==================== RELATIONSHIPS ====================

    public function Role()
    {
        return $this->belongsTo(Role::class);
    }

    public function qualification()
    {
        return $this->belongsTo(qualification::class);
    }

    public function lead()
    {
        return $this->hasMany(lead::class, 'assegnato_a');
    }

    public function contract()
    {
        return $this->hasMany(contract::class);
    }

    public function survey()
    {
        return $this->hasMany(survey::class);
    }

    public function log()
    {
        return $this->hasMany(Log::class);
    }

    public function teamMembers()
    {
        return $this->hasMany(User::class, 'user_id_padre');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'user_id_padre');
    }

    public function contract_management()
    {
        return $this->hasMany(contract_management::class, "user_id");
    }

    public function Notification()
    {
        return $this->hasMany(notification::class);
    }

    // ==================== E-COMMERCE RELATIONSHIPS ====================

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log user creation
        static::created(function ($user) {
            $operatorName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            $user->load(['Role', 'qualification']);

            SystemLogService::database()->info("User created", [
                'user_id' => $user->id,
                'codice' => $user->codice,
                'name' => $user->name,
                'cognome' => $user->cognome,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role_name' => $user->Role?->descrizione,
                'qualification_id' => $user->qualification_id,
                'qualification_name' => $user->qualification?->descrizione,
                'stato_user' => $user->stato_user,
                'user_id_padre' => $user->user_id_padre,
                'created_by' => $operatorName,
            ]);
        });

        // Log user updates
        static::updated(function ($user) {
            $changes = $user->getChanges();
            $original = $user->getOriginal();

            $changesForLog = [];
            $hasCriticalChanges = false;

            foreach ($changes as $field => $newValue) {
                // Skip excluded fields
                if (in_array($field, static::$excludeFromLog) || $field === 'updated_at') {
                    continue;
                }

                $oldValue = $original[$field] ?? null;

                // Mask sensitive fields
                if (in_array($field, static::$sensitiveFields)) {
                    $oldValue = static::maskSensitiveField($oldValue);
                    $newValue = static::maskSensitiveField($newValue);
                }

                $changesForLog[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];

                // Check for critical changes
                if (in_array($field, static::$criticalFields)) {
                    $hasCriticalChanges = true;
                }
            }

            if (!empty($changesForLog)) {
                $operatorName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';

                // Use warning for critical changes (role, qualification, points)
                $level = $hasCriticalChanges ? 'warning' : 'info';

                $logData = [
                    'user_id' => $user->id,
                    'codice' => $user->codice,
                    'user_name' => $user->name . ' ' . $user->cognome,
                    'email' => $user->email,
                    'changes' => $changesForLog,
                    'critical_change' => $hasCriticalChanges,
                    'updated_by' => $operatorName,
                ];

                // Add role/qualification names if changed
                if (isset($changesForLog['role_id'])) {
                    $logData['new_role_name'] = $user->Role?->descrizione;
                }
                if (isset($changesForLog['qualification_id'])) {
                    $logData['new_qualification_name'] = $user->qualification?->descrizione;
                }

                SystemLogService::database()->{$level}("User updated", $logData);
            }
        });

        // Log user deletion
        static::deleted(function ($user) {
            $operatorName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->warning("User deleted", [
                'user_id' => $user->id,
                'codice' => $user->codice,
                'name' => $user->name,
                'cognome' => $user->cognome,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'contracts_count' => $user->contract()->count(),
                'team_members_count' => $user->teamMembers()->count(),
                'deleted_by' => $operatorName,
            ]);
        });
    }

    // ==================== HELPER METHODS ====================

    /**
     * Mask sensitive field for logging (show only last 4 chars)
     */
    protected static function maskSensitiveField($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $length = strlen($value);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4) . substr($value, -4);
    }

    // ==================== E-COMMERCE COMPUTED ATTRIBUTES ====================

    public function getPvBloccatiAttribute()
    {
        return $this->cartItems()
                    ->whereHas('cartStatus', function ($q) {
                        $q->whereIn('status_name', ['attivo', 'in_attesa_di_pagamento']);
                    })
                    ->sum('pv_bloccati');
    }

    public function getPvTotaliAttribute()
    {
        return ($this->punti_valore_maturati ?? 0) + ($this->punti_bonus ?? 0);
    }

    public function getPvDisponibiliAttribute()
    {
        return $this->pv_totali - $this->pv_bloccati;
    }

    // ==================== E-COMMERCE METHODS ====================

    public function hasEnoughPv($pvAmount)
    {
        return $this->pv_disponibili >= $pvAmount;
    }

    public function blockPv($articleId, $quantity)
    {
        $article = Article::find($articleId);
        
        if (!$article) {
            throw new \Exception('Article not found');
        }
        
        if (!$article->available) {
            throw new \Exception('Article not available');
        }
        
        $pvToBlock = $article->pv_price * $quantity;
        
        if (!$this->hasEnoughPv($pvToBlock)) {
            throw new \Exception('Insufficient PV balance. Available: ' . $this->pv_disponibili . ' PV, Required: ' . $pvToBlock . ' PV');
        }
        
        $activeStatus = CartStatus::where('status_name', 'attivo')->first();
        
        if (!$activeStatus) {
            throw new \Exception('Active cart status not found. Please run seeder.');
        }
        
        $cartItem = $this->cartItems()
                         ->where('article_id', $articleId)
                         ->where('cart_status_id', $activeStatus->id)
                         ->first();
        
        if ($cartItem) {
            $cartItem->quantity += $quantity;
            $cartItem->pv_bloccati += $pvToBlock;
            $cartItem->save();
            
            return $cartItem;
        } else {
            return CartItem::create([
                'user_id' => $this->id,
                'article_id' => $articleId,
                'quantity' => $quantity,
                'pv_bloccati' => $pvToBlock,
                'cart_status_id' => $activeStatus->id,
            ]);
        }
    }

    public function releasePv($cartItemId)
    {
        $cartItem = CartItem::where('id', $cartItemId)
                             ->where('user_id', $this->id)
                             ->first();
        
        if ($cartItem) {
            $cartItem->delete();
            return true;
        }
        
        return false;
    }

    public function updateCartItemQuantity($cartItemId, $newQuantity)
    {
        $cartItem = CartItem::where('id', $cartItemId)
                             ->where('user_id', $this->id)
                             ->first();
        
        if (!$cartItem) {
            throw new \Exception('Cart item not found');
        }
        
        if ($newQuantity <= 0) {
            return $this->releasePv($cartItemId);
        }
        
        $article = $cartItem->article;
        $newPvTotal = $article->pv_price * $newQuantity;
        $pvDifference = $newPvTotal - $cartItem->pv_bloccati;
        
        if ($pvDifference > 0 && !$this->hasEnoughPv($pvDifference)) {
            throw new \Exception('Insufficient PV balance for quantity update');
        }
        
        $cartItem->quantity = $newQuantity;
        $cartItem->pv_bloccati = $newPvTotal;
        $cartItem->save();
        
        return $cartItem;
    }

    public function getActiveCart()
    {
        $activeStatus = CartStatus::where('status_name', 'attivo')->first();
        
        return $this->cartItems()
                    ->where('cart_status_id', $activeStatus->id)
                    ->with(['article.thumbnail', 'article.category'])
                    ->get();
    }

    public function getActiveCartTotal()
    {
        return $this->getActiveCart()->sum('pv_bloccati');
    }

    public function clearActiveCart()
    {
        $activeStatus = CartStatus::where('status_name', 'attivo')->first();
        
        return $this->cartItems()
                    ->where('cart_status_id', $activeStatus->id)
                    ->delete();
    }

    // ==================== JWT METHODS ====================

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // ==================== PASSWORD RESET ====================

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }
}