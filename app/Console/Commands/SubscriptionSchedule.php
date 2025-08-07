<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use Carbon\Carbon;

class SubscriptionSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:subscription-schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update subscription status based on start and end date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        $subscriptions = Subscription::all();

        foreach ($subscriptions as $subscription) {
            $start = Carbon::parse($subscription->start_date);
            $end = Carbon::parse($subscription->end_date);
            $newStatus = null;

            if ($now->between($start, $end)) {
                $newStatus = 'active';
            } elseif ($now->gt($end)) {
                $newStatus = 'expired';
            }

            if ($newStatus && $subscription->status !== $newStatus) {
                $subscription->status = $newStatus;
                $subscription->save();

                $this->info("Updated subscription ID {$subscription->id} to '{$newStatus}'");
            }
        }

        $this->info("✅ Subscription status update completed.");
    }
}
