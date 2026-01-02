<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AttributeSetAttribute extends Pivot
{
    use HasFactory;

    protected $table = 'attribute_set_attributes';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

    protected $fillable = [
        'attribute_set_id',
        'attribute_id',
        'sort_order',
        'is_required',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_required' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * The attribute set.
     */
    public function attributeSet()
    {
        return $this->belongsTo(AttributeSet::class);
    }

    /**
     * The attribute.
     */
    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    // ==================== SCOPES ====================

    public function scopeBySet($query, int $setId)
    {
        return $query->where('attribute_set_id', $setId);
    }

    public function scopeByAttribute($query, int $attributeId)
    {
        return $query->where('attribute_id', $attributeId);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if the attribute is required in this set context.
     * Returns pivot override if set, otherwise attribute default.
     */
    public function isEffectivelyRequired(): bool
    {
        if ($this->is_required !== null) {
            return $this->is_required;
        }

        return $this->attribute->is_required ?? false;
    }
}
