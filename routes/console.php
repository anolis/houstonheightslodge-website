<?php

use App\Services\FacebookEvents;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('facebook:sync-events', function (FacebookEvents $events) {
    try {
        $snapshot = $events->sync();
        $this->info('Synced '.count($snapshot['events']).' Facebook events.');

        return 0;
    } catch (Throwable $error) {
        // HTTP exceptions can include credentials; never log the original exception.
        $this->error('Facebook sync failed. Check Page access and connectivity. The last successful feed is unchanged.');

        return 1;
    }
})->purpose('Refresh the public calendar from the lodge Facebook Page');

Schedule::command('facebook:sync-events')->everyFifteenMinutes()->withoutOverlapping()
    ->when(fn () => filled(config('facebook.page_access_token')));
