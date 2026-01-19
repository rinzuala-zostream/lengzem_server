<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserRedeem extends Model
{
    use HasFactory;

    protected $table = 'user_redeems';

    protected $fillable = [
        'user_id',
        'redeem_id',
        'apply_date',
    ];

    protected $casts = [
        'apply_date' => 'datetime',
    ];

    /**
     * User who applied the redeem
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Redeem item
     */
    public function redeem()
    {
        return $this->belongsTo(RedeemCode::class);
    }
}
