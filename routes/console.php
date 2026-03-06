<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule cleanup of expired tips every day at midnight
Schedule::command('tips:cleanup')->daily();

// Schedule growth stage checks every day at midnight
Schedule::command('growth:check')->daily();

// Schedule health status checks every 30 minutes
Schedule::command('hydroponics:check-health-status')
    ->everyThirtyMinutes()
    ->withoutOverlapping();
