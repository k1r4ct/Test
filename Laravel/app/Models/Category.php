<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_name',
        'description',
        'filter_id',
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

    // Helper methods
    public function isVisibleToUser($user)
    {
        // If no filter, visible to all
        if (!$this->filter_id) {
            return true;
        }

        return $this->filter->matchesUser($user);
    }
}