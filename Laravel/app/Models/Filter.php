<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

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

    // ==================== RELATIONSHIPS ====================

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

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log filter creation
        static::created(function ($filter) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->info("Filter created", [
                'filter_id' => $filter->id,
                'filter_name' => $filter->filter_name,
                'description' => $filter->description,
                'role_id' => $filter->role_id,
                'qualification_id' => $filter->qualification_id,
                'expand_to_leads' => $filter->expand_to_leads,
                'created_by' => $userName,
            ]);
        });

        // Log filter updates
        static::updated(function ($filter) {
            $changes = $filter->getChanges();
            $original = $filter->getOriginal();

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

                SystemLogService::ecommerce()->info("Filter updated", [
                    'filter_id' => $filter->id,
                    'filter_name' => $filter->filter_name,
                    'changes' => $changesForLog,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log filter deletion
        static::deleted(function ($filter) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->warning("Filter deleted", [
                'filter_id' => $filter->id,
                'filter_name' => $filter->filter_name,
                'role_id' => $filter->role_id,
                'qualification_id' => $filter->qualification_id,
                'deleted_by' => $userName,
            ]);
        });
    }

    // ==================== HELPER METHODS ====================

    public function matchesUser($user)
    {
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