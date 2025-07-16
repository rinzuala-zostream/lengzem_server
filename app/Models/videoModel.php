<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class videoModel extends Model
{
    protected $table = 'video';

    protected $fillable = [
        'title',
        'description',
        'language',
        'thumbnail_url',
        'duration',
        'release_date',
        'status',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
