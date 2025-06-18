<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{

    protected $table = 'comments';

    protected $fillable = [
        'article_id',
        'user_id',
        'parent_id',
        'comment',
    ];

    // Comment belongs to an Article
    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    // Comment belongs to a User


    // Parent comment (for threaded comments)
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id'); // Ensure you're matching user_id in author with the id in users table
    }
}
