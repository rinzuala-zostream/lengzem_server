<?php

namespace App\Console\Commands;

use App\Models\Ad;
use Illuminate\Console\Command;

class AdsSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ads-schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically expire ads whose end date has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Ad::where('end_date', '<', now())
            ->where('status', '!=', 'expired')
            ->update(['status' => 'expired']);

        $this->info("✅ $count ad(s) marked as expired.");
    }
}
