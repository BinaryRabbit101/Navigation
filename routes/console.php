<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cards come from the HUB map; HUB's build-map.py also runs this right after each sync.
Schedule::command('nav:sync-map')->hourly()->withoutOverlapping();
