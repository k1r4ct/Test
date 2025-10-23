<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Filter extends Model
{
    use HasFactory;

    protected $fillable = [
        'filter_name',
        'description',
        'role_id',
        'qualification_id',
        'expand_to_leads',
    ];

    protected $casts = [
        'expand_to_leads' => 'boolean',
    ];

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function qualification()
    {
        return $this->belongsTo(qualification::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    // Helper methods
    public function matchesUser($user)
    {
        // Check if filter matches user's role and qualification
        if ($this->role_id && $this->role_id != $user->role_id) {
            return false;
        }

        if ($this->qualification_id && $this->qualification_id != $user->qualification_id) {
            return false;
        }

        return true;
    }

    public function shouldExpandToLeads()
    {
        return $this->expand_to_leads === true;
    }
}