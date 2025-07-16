<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $table = 'subscription_plans';
    protected $fillable = [
        'name',
        'description',
        'price',
        'interval_value',
        'interval_unit',
        'updated_at',
        'created_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'interval_value' => 'integer',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function toArray()
    {
        $array = parent::toArray();

        $array['created_at'] = $this->created_at
            ? $this->created_at->format('F j, Y')
            : null;

        $array['updated_at'] = $this->updated_at
            ? $this->updated_at->format('F j, Y')
            : null;

        return $array;
    }

}
