<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('customer:clean-account')->daily();
Schedule::command('notifications:clean')->daily();
Schedule::command('customer:clean-otp')->daily();
