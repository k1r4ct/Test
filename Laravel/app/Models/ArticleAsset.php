<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'asset_id',
    ];

    // Relationships
    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}