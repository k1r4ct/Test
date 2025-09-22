namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'title',
        'description',
        'status',
        'priority',
        'contract_id',
        'created_by_user_id',
        'assigned_to_user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function contract()
    {
        return $this->belongsTo(contract::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at', 'asc');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to_user_id', $userId);
    }

    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by_user_id', $userId);
    }

    public function getCustomerNameAttribute()
    {
        if ($this->contract && $this->contract->customer_data) {
            $customer = $this->contract->customer_data;
            if ($customer->nome && $customer->cognome) {
                return $customer->nome . ' ' . $customer->cognome;
            } elseif ($customer->ragione_sociale) {
                return $customer->ragione_sociale;
            }
        }
        return 'N/A';
    }

    public function getProductNameAttribute()
    {
        return $this->contract && $this->contract->product ? 
               $this->contract->product->descrizione : 'N/A';
    }

    public static function generateTicketNumber()
    {
        $lastTicket = self::orderBy('id', 'desc')->first();
        $nextNumber = $lastTicket ? $lastTicket->id + 1 : 1;
        return 'TK-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public static function getStatusOptions()
    {
        return [
            'new' => 'Nuovo',
            'in-progress' => 'In Lavorazione', 
            'waiting' => 'In Attesa',
            'resolved' => 'Risolto'
        ];
    }

    public static function getPriorityOptions()
    {
        return [
            'low' => 'Bassa',
            'medium' => 'Media',
            'high' => 'Alta'
        ];
    }

    // Boot method to auto-generate ticket number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
        });
    }
}