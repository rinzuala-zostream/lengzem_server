<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{

    protected $table = 'subscriptions';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'payment_id',
        'start_date',
        'end_date',
        'status',
        'amount',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function toArray()
    {
        $array = parent::toArray();

        $array['start_date'] = $this->start_date
            ? $this->start_date->format('F j, Y')
            : null;

        $array['end_date'] = $this->end_date
            ? $this->end_date->format('F j, Y')
            : null;

        return $array;
    }
}