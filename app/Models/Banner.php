<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Banner extends Model
{
    protected $table = 'banners';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'title',
        'img',
        'mobile_img',
        'bannerable_id',
        'bannerable_type',
        'position',
        'priority',
        'is_active',
        'start_at',
        'end_at',
    ];

    /**
     * Type casting
     */
    protected $casts = [
        'is_active' => 'boolean',
        'start_at'  => 'datetime',
        'end_at'    => 'datetime',
    ];

    /**
     * Polymorphic relation
     * (Article, Video, Audio, Ad, etc.)
     */
    public function bannerable()
    {
        return $this->morphTo();
    }

    /**
     * Scope: only active banners
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: banners valid by schedule
     */
    public function scopeScheduled(Builder $query)
    {
        $now = Carbon::now();

        return $query
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')
                  ->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')
                  ->orWhere('end_at', '>=', $now);
            });
    }

    /**
     * Scope: ready to display
     */
    public function scopeVisible(Builder $query)
    {
        return $query->active()->scheduled();
    }

    /**
     * Accessor: normalized banner target
     * Used by App / API
     */
    public function getTargetAttribute()
    {
        return [
            'type' => strtolower(class_basename($this->bannerable_type)),
            'id'   => $this->bannerable_id,
        ];
    }

    /**
     * Hide internal polymorphic fields from API
     */
    protected $hidden = [
        'bannerable_id',
        'bannerable_type',
    ];

    /**
     * Append computed attributes
     */
    protected $appends = [
        'target',
    ];
}
