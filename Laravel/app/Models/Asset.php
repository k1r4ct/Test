<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_path',
        'file_type',
        'file_name',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    // Relationships
    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_assets');
    }

    public function articlesWithThumbnail()
    {
        return $this->hasMany(Article::class, 'thumbnail_asset_id');
    }

    // Scopes
    public function scopeImages($query)
    {
        return $query->where('file_type', 'image');
    }

    public function scopeVideos($query)
    {
        return $query->where('file_type', 'video');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }
}