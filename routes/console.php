<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command("app:ads-schedule")->daily();
Schedule::command("app:subscription-reminder")->dailyAt('10:00');
Schedule::command("app:subscription-schedule")->daily();
Schedule::command("app:article-cron")->weeklyOn(1, '19:30') // every Monday 7:30 PM
    ->when(function () {
        // ✅ Only if today’s date is 1–7 (the first week of the month)
        return now()->day <= 7;
    });
