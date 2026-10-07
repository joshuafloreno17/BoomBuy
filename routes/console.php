<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Housekeeping that also runs from page visits (see AutoReceiveDeliveredOrders),
// for a machine that has the Laravel scheduler running: `php artisan schedule:work`
// or a Task Scheduler entry calling `php artisan schedule:run` every minute.
Artisan::command('boombuy:housekeeping', function () {
    $received = \App\Support\AutoReceive::sweep(true);
    $stale = \App\Support\StaleOrders::sweep(true);
    $files = \App\Support\OrphanUploads::sweep(true);

    $this->info("Received: {$received} · Cancelled: {$stale['cancelled']} · Returned: {$stale['returned']} · Reminders: {$stale['reminded']} · Returns to BoomBuy: {$stale['escalated']} · Returns cancelled: {$stale['returns_cancelled']} · Old uploads removed: {$files}");
})->purpose('Auto-receive, cancel unconfirmed orders, return uncollected pick-ups, clear abandoned uploads');

\Illuminate\Support\Facades\Schedule::command('boombuy:housekeeping')->everyTenMinutes()->withoutOverlapping();
