<?php
use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NotificationCreated implements ShouldBroadcast
{
    public $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn()
    {
        // role-based channel
        return new Channel('notifications.' . $this->notification->target_role);
    }

    public function broadcastAs()
    {
        return 'notification.created';
    }
}
