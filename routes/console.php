<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule cleanup of expired tips every day at midnight
Schedule::command('tips:cleanup')->daily();

// Schedule growth stage checks every hour
Schedule::command('growth:check')->hourly();
