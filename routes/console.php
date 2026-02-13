<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('logs:download')
    #->everyFiveMinutes()
    ->hourly()
    ->withoutOverlapping(30);

Schedule::command('snmp:get-serial')
    ->daily()
    ->withoutOverlapping();

