<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

    protected $table = 'categories';
    protected $fillable = ['name', 'slug', 'description'];

    // Category has many articles
    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
