<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdMedia extends Model
{

    protected $table = 'ad_media';

    protected $fillable = ['ad_id', 'media_url', 'media_type'];

    public $timestamps = false;

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }
}
