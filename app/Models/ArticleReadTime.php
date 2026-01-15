<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleReadTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'article_id',
        'session_id',
        'start_time',
        'end_time',
        'duration_seconds',
        'device_type',
    ];
}
