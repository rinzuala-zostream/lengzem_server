<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'actor_id',
        'action',
        'message',
        'status',
        'target_role',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Polymorphic relation
     * (Article, Subscription, User, etc.)
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * User who triggered the notification
     */
    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /* -------------------------
       Scopes (Very Useful)
    --------------------------*/

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForAdmin($query)
    {
        return $query->where('target_role', 'admin');
    }

    public function scopeForEditor($query)
    {
        return $query->where('target_role', 'editor');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
