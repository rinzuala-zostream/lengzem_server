<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{

    protected $table = 'user';
    protected $primaryKey = 'num';

    // Primary key is NOT an auto-incrementing integer, but a string
    public $incrementing = false;

    // Primary key type
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'phone', 'name', 'email', 'role', 'bio', 'profile_image_url',
    ];

    
}
