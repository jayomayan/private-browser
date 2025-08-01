<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

return function (Schedule $schedule) {
    $schedule->command('logs:download')->everyFifteenMinutes()->withoutOverlapping(30);
};

$schedule->command('logs:download')->everyFifteenMinutes()->withoutOverlapping(30);
