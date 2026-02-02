<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CoverImage extends Model
{
    use HasFactory;

    protected $table = 'cover_images';

    protected $fillable = [
        'label',
        'slug',
        'url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Auto-generate slug from label if not provided
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->label);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('label')) {
                $model->slug = Str::slug($model->label);
            }
        });
    }
    /**
     * Scope: Active covers only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function articles()
{
    return $this->hasMany(Article::class, 'cover_image_id');
}
}
