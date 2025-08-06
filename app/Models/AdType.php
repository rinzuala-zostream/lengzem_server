<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdType extends Model
{
    protected $table = 'ad_types';
    protected $fillable = ['name'];

    public function ads()
    {
        return $this->hasMany(Ad::class, 'type_id');
    }
}

