<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{

    protected $table = 'user';

    // Primary key is NOT an auto-incrementing integer, but a string
    public $incrementing = false;

    // Primary key type
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'phone',
        'name',
        'email',
        'role',
        'bio',
        'profile_image_url',
    ];

    public function articles()
    {
        return $this->hasMany(Article::class, 'user.id', 'id'); // target: user.id
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
