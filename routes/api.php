<?php

use App\Http\Controllers\Api\Mobile\AccountController;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\BuyerOrderController;
use App\Http\Controllers\Api\Mobile\CartController;
use App\Http\Controllers\Api\Mobile\CatalogController;
use App\Http\Controllers\Api\Mobile\MessageController;
use App\Http\Controllers\Api\Mobile\RiderController;
use App\Http\Controllers\Api\Mobile\SellerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| BoomBuy mobile app API — /api/mobile/v1
|--------------------------------------------------------------------------
|
| Used by the BoomBuy app (buyers, sellers and riders). Sign in with
| POST /login, then send "Authorization: Bearer <token>". Any other account
| (logistics, admin) is refused with code "desktop_only".
|
*/

Route::prefix('mobile/v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1,api-login');

    // What the app needs before anyone signs in.
    Route::get('/config', fn () => response()->json([
        'website_url' => url('/'),
        'register_url' => route('register'),
        'forgot_password_url' => route('password.request'),
        'policies_url' => route('policies'),
        'roles' => \App\Support\MobileAccess::ROLES,
    ]));

    // The shop for everyone, signed in or not (like the website's landing
    // page and shop). A buyer's token, when sent, adds their wishlist.
    Route::middleware('throttle:120,1,api-public')->prefix('shop')->group(function () {
        Route::get('/landing', [CatalogController::class, 'landing']);
        Route::get('/categories', [CatalogController::class, 'categories']);
        Route::get('/products', [CatalogController::class, 'products']);
        Route::get('/products/{id}', [CatalogController::class, 'product'])->whereNumber('id');
        Route::get('/shops/{id}', [CatalogController::class, 'shop'])->whereNumber('id');
    });

    // Every mobile role.
    Route::middleware(['api.token', 'throttle:180,1,api'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/profile/password', [AuthController::class, 'updatePassword']);
        Route::post('/profile/photo', [AuthController::class, 'updatePhoto']);

        Route::get('/notifications', [AuthController::class, 'notifications']);
        Route::post('/notifications/read-all', [AuthController::class, 'readAllNotifications']);
        Route::post('/notifications/{id}/read', [AuthController::class, 'readNotification'])->whereNumber('id');

        Route::get('/messages', [MessageController::class, 'index']);
        Route::get('/messages/{userId}', [MessageController::class, 'thread'])->whereNumber('userId');
        Route::post('/messages/{userId}', [MessageController::class, 'store'])->whereNumber('userId')->middleware('throttle:30,1,api-messages');
    });

    // Buyers.
    Route::middleware(['api.token:buyer', 'throttle:180,1,api'])->prefix('buyer')->group(function () {
        Route::get('/home', [CatalogController::class, 'home']);
        Route::get('/categories', [CatalogController::class, 'categories']);
        Route::get('/products', [CatalogController::class, 'products']);
        Route::get('/products/{id}', [CatalogController::class, 'product'])->whereNumber('id');
        Route::get('/shops/{id}', [CatalogController::class, 'shop'])->whereNumber('id');

        Route::get('/wishlist', [AccountController::class, 'wishlist']);
        Route::post('/wishlist/{id}', [AccountController::class, 'toggleWishlist'])->whereNumber('id');

        Route::get('/addresses', [AccountController::class, 'addresses']);
        Route::post('/addresses', [AccountController::class, 'storeAddress']);
        Route::post('/addresses/{id}/default', [AccountController::class, 'defaultAddress'])->whereNumber('id');
        Route::delete('/addresses/{id}', [AccountController::class, 'deleteAddress'])->whereNumber('id');

        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart', [CartController::class, 'add']);
        Route::post('/cart/update', [CartController::class, 'update']);
        Route::post('/cart/remove', [CartController::class, 'remove']);
        Route::get('/vouchers', [CartController::class, 'vouchers']);
        Route::post('/checkout/preview', [CartController::class, 'checkoutPreview']);
        Route::post('/checkout', [CartController::class, 'placeOrder'])->middleware('throttle:10,1,api-checkout');

        Route::get('/orders', [BuyerOrderController::class, 'index']);
        Route::get('/orders/{id}', [BuyerOrderController::class, 'show'])->whereNumber('id');
        Route::post('/orders/{id}/cancel', [BuyerOrderController::class, 'cancel'])->whereNumber('id');
        Route::post('/orders/{id}/received', [BuyerOrderController::class, 'received'])->whereNumber('id');
        Route::post('/orders/{id}/reorder', [BuyerOrderController::class, 'reorder'])->whereNumber('id');
        Route::post('/orders/{id}/review', [BuyerOrderController::class, 'review'])->whereNumber('id');
        Route::post('/orders/{id}/return', [BuyerOrderController::class, 'requestReturn'])->whereNumber('id');
        Route::post('/returns/{id}/cancel', [BuyerOrderController::class, 'cancelReturn'])->whereNumber('id');
        Route::post('/returns/{id}/escalate', [BuyerOrderController::class, 'escalateReturn'])->whereNumber('id');
    });

    // Sellers.
    Route::middleware(['api.token:seller', 'throttle:180,1,api'])->prefix('seller')->group(function () {
        Route::get('/dashboard', [SellerController::class, 'dashboard']);

        Route::get('/orders', [SellerController::class, 'orders']);
        Route::get('/orders/{id}', [SellerController::class, 'order'])->whereNumber('id');
        Route::post('/orders/{id}/status', [SellerController::class, 'updateStatus'])->whereNumber('id');
        Route::post('/orders/{id}/pickup', [SellerController::class, 'requestPickup'])->whereNumber('id');
        Route::post('/orders/{id}/confirm-pickup', [SellerController::class, 'confirmPickup'])->whereNumber('id');
        Route::post('/orders/{id}/restock', [SellerController::class, 'restockOrder'])->whereNumber('id');

        Route::get('/products', [SellerController::class, 'products']);
        Route::post('/products', [SellerController::class, 'storeProduct']);
        Route::get('/products/{id}', [SellerController::class, 'product'])->whereNumber('id');
        Route::post('/products/{id}', [SellerController::class, 'updateProduct'])->whereNumber('id');
        Route::post('/products/{id}/variations', [SellerController::class, 'updateVariations'])->whereNumber('id');
        Route::post('/products/{id}/archive', [SellerController::class, 'archiveProduct'])->whereNumber('id');
        Route::post('/products/{id}/unarchive', [SellerController::class, 'unarchiveProduct'])->whereNumber('id');

        Route::get('/returns', [SellerController::class, 'returns']);
        Route::post('/returns/{id}/approve', [SellerController::class, 'approveReturn'])->whereNumber('id');
        Route::post('/returns/{id}/reject', [SellerController::class, 'rejectReturn'])->whereNumber('id');
        Route::post('/returns/{id}/restock', [SellerController::class, 'restockReturn'])->whereNumber('id');

        Route::get('/reviews', [SellerController::class, 'reviews']);
        Route::post('/reviews/{id}/reply', [SellerController::class, 'replyReview'])->whereNumber('id');
    });

    // Riders.
    Route::middleware(['api.token:rider', 'throttle:180,1,api'])->prefix('rider')->group(function () {
        Route::get('/dashboard', [RiderController::class, 'dashboard']);

        Route::get('/pickups', [RiderController::class, 'pickups']);
        Route::post('/pickups/{id}/accept', [RiderController::class, 'acceptPickup'])->whereNumber('id');
        Route::post('/pickups/{id}/picked-up', [RiderController::class, 'pickedUp'])->whereNumber('id');

        Route::get('/deliveries', [RiderController::class, 'deliveries']);
        Route::get('/deliveries/{id}', [RiderController::class, 'delivery'])->whereNumber('id');
        Route::post('/deliveries/{id}/out-for-delivery', [RiderController::class, 'outForDelivery'])->whereNumber('id');
        Route::post('/deliveries/{id}/delivered', [RiderController::class, 'delivered'])->whereNumber('id');
        Route::post('/deliveries/{id}/failed', [RiderController::class, 'failed'])->whereNumber('id');

        Route::get('/earnings', [RiderController::class, 'earnings']);
    });
});
