<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPassword;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

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
        'ragione_sociale',
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
        return $this->hasMany(log::class);
    }

    public function teamMembers()
    {
        return $this->hasMany(User::class, 'user_id_padre');
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

    // ==================== E-COMMERCE COMPUTED ATTRIBUTES ====================

    /**
     * Get total PV currently blocked in active and pending carts
     */
    public function getPvBloccatiAttribute()
    {
        return $this->cartItems()
                    ->whereHas('cartStatus', function($q) {
                        $q->whereIn('status_name', ['attivo', 'in_attesa_di_pagamento']);
                    })
                    ->sum('pv_temporanei');
    }

    /**
     * Get total PV (accumulated + bonus)
     */
    public function getPvTotaliAttribute()
    {
        return ($this->punti_valore_maturati ?? 0) + ($this->punti_bonus ?? 0);
    }

    /**
     * Get available PV (total PV - blocked PV)
     */
    public function getPvDisponibiliAttribute()
    {
        return $this->pv_totali - $this->pv_bloccati;
    }

    // ==================== E-COMMERCE METHODS ====================

    /**
     * Check if user has enough available PV for a purchase
     */
    public function hasEnoughPv($pvAmount)
    {
        return $this->pv_disponibili >= $pvAmount;
    }

    /**
     * Block PV when adding items to cart
     */
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
        
        // Get or create active cart status
        $activeStatus = CartStatus::where('status_name', 'attivo')->first();
        
        if (!$activeStatus) {
            throw new \Exception('Active cart status not found. Please run seeder.');
        }
        
        // Check if item already exists in active cart
        $cartItem = $this->cartItems()
                         ->where('article_id', $articleId)
                         ->where('cart_status_id', $activeStatus->id)
                         ->first();
        
        if ($cartItem) {
            // Update existing cart item
            $cartItem->quantity += $quantity;
            $cartItem->pv_temporanei += $pvToBlock;
            $cartItem->save();
            
            return $cartItem;
        } else {
            // Create new cart item
            return CartItem::create([
                'user_id' => $this->id,
                'article_id' => $articleId,
                'quantity' => $quantity,
                'pv_temporanei' => $pvToBlock,
                'cart_status_id' => $activeStatus->id,
            ]);
        }
    }

    /**
     * Release blocked PV (when removing from cart)
     */
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

    /**
     * Update cart item quantity
     */
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
        $pvDifference = $newPvTotal - $cartItem->pv_temporanei;
        
        // Check if user has enough PV for the increase
        if ($pvDifference > 0 && !$this->hasEnoughPv($pvDifference)) {
            throw new \Exception('Insufficient PV balance for quantity update');
        }
        
        $cartItem->quantity = $newQuantity;
        $cartItem->pv_temporanei = $newPvTotal;
        $cartItem->save();
        
        return $cartItem;
    }

    /**
     * Get active cart items
     */
    public function getActiveCart()
    {
        $activeStatus = CartStatus::where('status_name', 'attivo')->first();
        
        return $this->cartItems()
                    ->where('cart_status_id', $activeStatus->id)
                    ->with(['article.thumbnail', 'article.category'])
                    ->get();
    }

    /**
     * Get total PV of active cart
     */
    public function getActiveCartTotal()
    {
        return $this->getActiveCart()->sum('pv_temporanei');
    }

    /**
     * Clear active cart
     */
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