<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('billing:notify-due')->dailyAt('08:00');
Schedule::command('bills:calculate-penalties')->dailyAt('01:00');
Schedule::job(new \App\Jobs\AnomalyDetectionJob)->dailyAt('02:00');
