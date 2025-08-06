<?php

namespace App\Console\Commands;

use App\Http\Controllers\FCMNotificationController;
use Illuminate\Console\Command;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionReminder extends Command
{
    protected $signature = 'app:subscription-reminder';
    protected $description = 'Send FCM reminders for subscriptions expiring in 3, 2 days, or today.';
    protected $fcm;

    public function __construct(FCMNotificationController $fCMNotificationController)
    {
        parent::__construct();
        $this->fcm = $fCMNotificationController;
    }

    public function handle()
    {
        $dates = [
            now()->addDays(3)->toDateString() => '📅 Subscription expires in 3 days',
            now()->addDays(2)->toDateString() => '📆 Subscription expires in 2 days',
            now()->toDateString() => '⚠️ Subscription expires today',
        ];

        $count = 0;

        foreach ($dates as $targetDate => $title) {
            $subscriptions = Subscription::with('user', 'plan')
                ->whereDate('end_date', '=', $targetDate)
                ->where('status', 'active')
                ->get();

            foreach ($subscriptions as $subscription) {
                $user = $subscription->user;
                $plan = $subscription->plan;
                $message = "{$plan->name} plan will expire on {$subscription->end_date->format('F j, Y')}.";

                $fakeRequest = new Request([
                    'title' => $title,
                    'body' => $message,
                    'image' => $plan->image ?? '',
                    //'key' => $plan->id ?? '',
                ]);

                $this->fcm->send($fakeRequest);
                $this->info("🔔 Reminder sent to {$user->email} - {$title}");
                $count++;
            }
        }

        $this->info($count === 0
            ? "📭 No subscriptions expiring in 3 days, 2 days, or today."
            : "✅ {$count} total reminder(s) sent.");
    }
}
