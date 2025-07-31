<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AudioModel extends Model
{
    protected $table = 'audio';

    protected $fillable = [
        'title',
        'description',
        'language',
        'thumbnail_url',
        'duration',
        'release_date',
        'status',
        'author_id',  // Add 'author_id' to the fillable attributes
        'url'
    ];

    protected $casts = [
        'is_premium' => 'boolean',
    ];


    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    // Define the relationship with the User model
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    public function toArray()
    {
        $array = parent::toArray();

        // Format the fields
        $array['created_at'] = $this->created_at
            ? Carbon::parse($this->created_at)->format('F j, Y')
            : null;

        $array['updated_at'] = $this->updated_at
            ? Carbon::parse($this->updated_at)->format('F j, Y')
            : null;

        return $array;
    }
}
