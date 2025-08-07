<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command("app:ads-schedule")->daily();
Schedule::command("app:subscription-reminder")->dailyAt('10:00');
Schedule::command("app:subscription-schedule")->daily();
