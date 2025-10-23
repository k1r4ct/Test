<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'store_name',
        'store_type',
        'filter_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'store_type' => 'string',
    ];

    // Relationships
    public function filter()
    {
        return $this->belongsTo(Filter::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function stock()
    {
        return $this->hasMany(Stock::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeDigital($query)
    {
        return $query->where('store_type', 'digital');
    }

    public function scopePhysical($query)
    {
        return $query->where('store_type', 'physical');
    }

    // Helper methods
    public function isDigital()
    {
        return $this->store_type === 'digital';
    }

    public function isPhysical()
    {
        return $this->store_type === 'physical';
    }

    public function isVisibleToUser($user)
    {
        // If no filter, visible to all
        if (!$this->filter_id) {
            return true;
        }

        return $this->filter->matchesUser($user);
    }
}