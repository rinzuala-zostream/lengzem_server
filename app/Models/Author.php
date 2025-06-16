<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $table = 'author';
    protected $fillable = [
        'user_id',
        'bio',
        'pen_name',
        'social_links',
    ];

    // Author belongs to one User
    // In the Author model
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id'); // Ensure you're matching user_id in author with the id in users table
    }

    // Author has many articles
    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
