<?php

use App\Http\Controllers\Api\RiderApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| BoomBuy mobile API — /api/v1
|--------------------------------------------------------------------------
|
| Used by the BoomBuy Rider app (mobile/boombuy_rider). Sign in with
| POST /api/v1/login, then send "Authorization: Bearer <token>".
| docs/API.md lists every endpoint.
|
*/

Route::prefix('v1')->group(function () {
    Route::post('/login', [RiderApiController::class, 'login'])->middleware('throttle:10,1,api-login');

    Route::middleware(['api.token:rider', 'throttle:120,1,api'])->group(function () {
        Route::post('/logout', [RiderApiController::class, 'logout']);
        Route::get('/me', [RiderApiController::class, 'me']);

        Route::get('/deliveries', [RiderApiController::class, 'deliveries']);
        Route::get('/deliveries/{id}', [RiderApiController::class, 'delivery'])->whereNumber('id');
        Route::post('/deliveries/{id}/out-for-delivery', [RiderApiController::class, 'outForDelivery'])->whereNumber('id');
        Route::post('/deliveries/{id}/delivered', [RiderApiController::class, 'delivered'])->whereNumber('id');
        Route::post('/deliveries/{id}/failed', [RiderApiController::class, 'failed'])->whereNumber('id');

        Route::get('/earnings', [RiderApiController::class, 'earnings']);

        Route::get('/notifications', [RiderApiController::class, 'notifications']);
        Route::post('/notifications/{id}/read', [RiderApiController::class, 'readNotification'])->whereNumber('id');
    });
});
