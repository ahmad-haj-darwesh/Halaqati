<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notify:unrecorded-halaqahs')
    ->dailyAt('21:00')
    ->timezone('Asia/Riyadh')
    ->withoutOverlapping();

Schedule::command('activitylog:clean --days=90')->monthly();
