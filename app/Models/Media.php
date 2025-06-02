<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{

    protected $table = 'media';

    protected $fillable = [
        'article_id', 'media_type', 'url', 'caption',
    ];

    // Media belongs to Article
    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
