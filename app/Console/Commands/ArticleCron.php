<?php

namespace App\Console\Commands;

use App\Http\Controllers\FCMNotificationController;
use App\Models\Article;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class ArticleCron extends Command
{
    protected $signature = 'app:article-cron';
    protected $description = 'Publish articles that are scheduled for publishing';
    protected $fcmController;

    public function __construct(FCMNotificationController $fcmController)
    {
        $this->fcmController = $fcmController;
        parent::__construct();
    }

    public function handle()
    {
        // Step 1: Publish scheduled articles
        $count = Article::where('status', 'scheduled')
            ->where('published_at', '<=', now())
            ->update(['status' => 'published']);

        $this->info("✅ $count article(s) published.");

        // Step 2: Send monthly push (include month name)
        $currentMonth = Carbon::now()->format('F'); // e.g., January
        $this->sendMonthlyPush($currentMonth);
    }

    private function sendMonthlyPush(string $monthName)
    {
        $request = new Request();
        $request->merge([
            'title' => "Lengzem {$monthName} thla!",
            'body' => "Lengzem {$monthName} thla chhuak chu chhiar theih in a awm e.",
            'image' => "https://cdn.lengzem.in/icon/app_icon.png",
            'type' => 'topic',
            'recipient' => 'all'
        ]);

        $this->fcmController->send($request);
        $this->info("📢 Monthly push notification for {$monthName} sent.");
    }
}
