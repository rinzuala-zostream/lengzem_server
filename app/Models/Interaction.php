<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interaction extends Model
{

    protected $table = 'interactions';

    protected $fillable = [
        'user_id', 'article_id', 'type', // type: like, dislike, bookmark
    ];

    // Interaction belongs to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Interaction belongs to Article
    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
