<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $table = 'author';
    protected $fillable = [
        'user_id', 'bio', 'pen_name', 'social_links',
    ];

    // Author belongs to one User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Author has many articles
    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
