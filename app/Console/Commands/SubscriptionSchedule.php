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

        // Update subscriptions that are active but not within start_date and end_date
        $expiredCount = Subscription::where('status', 'active')
            ->where(function ($query) use ($now) {
                $query->where('start_date', '>', $now)   // Not started yet
                    ->orWhere('end_date', '<', $now); // Already ended
            })
            ->update(['status' => 'expired']);

        $this->info("✅ Updated {$expiredCount} subscriptions to 'expired'.");
    }
}
