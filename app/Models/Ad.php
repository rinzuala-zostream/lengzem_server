<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{

    protected $table = 'ads';

    protected $fillable = [
        'title', 'description', 'type_id', 'start_date', 'end_date', 'status'
    ];

    public function type()
    {
        return $this->belongsTo(AdType::class, 'type_id');
    }

    public function media()
    {
        return $this->hasMany(AdMedia::class);
    }
}
