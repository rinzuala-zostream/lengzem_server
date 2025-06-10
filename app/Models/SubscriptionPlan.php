<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'interval_value' => 'integer',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
