<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RedeemCode extends Model
{
    use HasFactory;

    protected $table = 'redeem_codes';

    protected $primaryKey = 'id';

    public $incrementing = true;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'user_id',
        'redeem_code',
        'no_of_apply',
        'is_active',
        'benefit_end_month',
        'expire_date',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'no_of_apply' => 'integer',
        'is_active' => 'boolean',
        'benefit_end_month' => 'date',
        'expire_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Default attribute values
     */
    protected $attributes = [
        'no_of_apply' => 0,
        'is_active' => true,
    ];

    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'redeem_id', 'id');
    }

    /* ============================================================
     |  Relationships
     |============================================================ */

    /**
     * Redeem code belongs to a user (Firebase UID)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /* ============================================================
     |  Query Scopes
     |============================================================ */

    /**
     * Only active redeem codes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Only non-expired redeem codes
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expire_date', '>', now());
    }

    /* ============================================================
     |  Helpers
     |============================================================ */

    /**
     * Check if redeem code is expired
     */
    public function isExpired(): bool
    {
        return $this->expire_date !== null
            && Carbon::now()->greaterThan($this->expire_date);
    }

    /**
     * Mark redeem code as applied
     */
    public function incrementApplyCount(): void
    {
        $this->increment('no_of_apply');
    }

    /**
     * Deactivate redeem code
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function userRedeems()
    {
        return $this->hasMany(UserRedeem::class, 'redeem_id');
    }
}
