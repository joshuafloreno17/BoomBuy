<?php

namespace App\Http\Middleware;

use App\Support\AutoReceive;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| AUTO "ORDER RECEIVED"
|--------------------------------------------------------------------------
|
| Marks delivered orders as received once the buyer has had a few days to
| confirm (see AutoReceive). Checked on page visits, at most every few
| minutes, because the laptop serving boombuy.store runs no cron job.
|
*/

class AutoReceiveDeliveredOrders
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            AutoReceive::sweep();
        } catch (\Throwable $e) {
            // Never block a page over this housekeeping.
            report($e);
        }

        return $next($request);
    }
}
