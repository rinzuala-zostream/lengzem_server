<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $table = 'tags';
    protected $fillable = ['name', 'slug'];

    // Tags belong to many articles
    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }
}
