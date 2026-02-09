<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleFeatureModel extends Model
{
    use HasFactory;

    // Table name
    protected $table = 'article_features';

    // Primary key
    protected $primaryKey = 'id';

    // Disable timestamps if you’re not using `updated_at`
    public $timestamps = false;

    // Fillable columns for mass assignment
    protected $fillable = [
        'month_year',
        'title',
        'description',
        'img_url',
        'created_at',
    ];
}
