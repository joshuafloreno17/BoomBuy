<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\RiderController;
use App\Http\Controllers\LogisticsController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\NotificationController;

// Helper functions (requireUserRole, cart helpers, OTP…) live in
// app/Helpers/helpers.php, autoloaded via composer.json.

/*
|--------------------------------------------------------------------------
| RIDER PROFILE PHOTO
|--------------------------------------------------------------------------
*/

Route::post('/rider/profile/photo', [RiderController::class, 'updatePhoto'])->name('rider.profile.photo');


Route::get('/login', [AuthController::class, 'showLogin'])->name('login');


// throttle:N,M = at most N attempts per M minutes per visitor — stops
// password / OTP guessing and email spam on the unauthenticated forms.
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');


/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

// ==========================================================
// REGISTER PAGE
// ==========================================================

Route::get('/register', [AuthController::class, 'showRegisterChoose'])->name('register');


// ==========================================================
// REGISTER - ROLE SELECTION
// ==========================================================

Route::post('/register', [AuthController::class, 'submitRegisterRole'])->name('register.submit');


// ==========================================================
// FORGOT PASSWORD PAGE
// ==========================================================

Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');


// ==========================================================
// CHECK FORGOT PASSWORD EMAIL
// ==========================================================

Route::post('/forgot-password', [AuthController::class, 'sendResetOtp'])->middleware('throttle:5,10')->name('password.email');


// ==========================================================
// RESET PASSWORD PAGE
// ==========================================================

Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset');


// ==========================================================
// UPDATE NEW PASSWORD
// ==========================================================

Route::post('/reset-password', [AuthController::class, 'updatePassword'])->middleware('throttle:10,1')->name('password.update');



// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


/*
|--------------------------------------------------------------------------
| BUYER
|--------------------------------------------------------------------------
*/

Route::get('/buyer', [BuyerController::class, 'dashboard'])->name('buyer.dashboard');

/*
|--------------------------------------------------------------------------
| BUYER PROFILE
|--------------------------------------------------------------------------
*/

Route::get('/buyer/profile', [BuyerController::class, 'profile'])->name('buyer.profile');


Route::post('/buyer/profile', [BuyerController::class, 'updateProfile'])->name('buyer.profile.update');


Route::post('/buyer/profile/photo', [BuyerController::class, 'updatePhoto'])->name('buyer.profile.photo');


Route::post('/buyer/profile/password', [BuyerController::class, 'updatePassword'])->name('buyer.profile.password');

/*
|--------------------------------------------------------------------------
| WISHLIST
|--------------------------------------------------------------------------
*/

Route::get('/wishlist', [BuyerController::class, 'wishlist'])->name('wishlist.index');


Route::post('/wishlist/toggle/{id}', [BuyerController::class, 'toggleWishlist'])->name('wishlist.toggle');

/*
|--------------------------------------------------------------------------
| BUYER ORDERS
|--------------------------------------------------------------------------
*/

Route::get('/buyer/orders', [BuyerController::class, 'orders'])->name('buyer.orders');


Route::post('/buyer/orders/{id}/received', [BuyerController::class, 'markReceived'])->name('buyer.order.received');


Route::post('/buyer/orders/{id}/cancel', [BuyerController::class, 'cancelOrder'])->name('buyer.order.cancel');


Route::post('/buyer/orders/{id}/reorder', [BuyerController::class, 'reorder'])->name('buyer.order.reorder');


Route::post('/buyer/orders/{orderId}/review/{productId}', [BuyerController::class, 'reviewProduct'])->name('buyer.product.review');


/*
|--------------------------------------------------------------------------
| ADMIN LOGIN
|--------------------------------------------------------------------------
*/

// Admin no longer has a separate login page — the unified /login form
// detects the admin credentials automatically. This route is kept only
// because every admin-gated route redirects here by name when the
// session has expired; it just forwards to the unified form.
Route::get('/admin/login', [AdminController::class, 'showLogin'])->name('admin.login');


Route::post('/admin/login', [AdminController::class, 'login'])->middleware('throttle:10,1')->name('admin.login.submit');


/*
|--------------------------------------------------------------------------
| ADMIN DASHBOARD
|--------------------------------------------------------------------------
*/

Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');

/*
|--------------------------------------------------------------------------
| ADMIN LOGOUT
|--------------------------------------------------------------------------
*/

Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');


/*
|--------------------------------------------------------------------------
| PRODUCTS
|--------------------------------------------------------------------------
*/

Route::get('/products', [ShopController::class, 'products'])->name('products');


/*
|--------------------------------------------------------------------------
| ADMIN PRODUCTS LIST
|--------------------------------------------------------------------------
*/

Route::get('/admin/products', [AdminController::class, 'products'])->name('admin.products');


/*
|--------------------------------------------------------------------------
| ADMIN PRODUCT MODERATION (products are only ever added by sellers)
|--------------------------------------------------------------------------
*/

Route::get('/admin/products/{id}/edit', [AdminController::class, 'editProduct'])->name('admin.products.edit');


Route::put('/admin/products/{id}', [AdminController::class, 'updateProduct'])->name('admin.products.update');


Route::delete('/admin/products/{id}', [AdminController::class, 'deleteProduct'])->name('admin.products.delete');


Route::post('/admin/products/{id}/archive', [AdminController::class, 'archiveProduct'])->name('admin.products.archive');


Route::post('/admin/products/{id}/unarchive', [AdminController::class, 'unarchiveProduct'])->name('admin.products.unarchive');


/*
|--------------------------------------------------------------------------
| ADMIN ACCOUNTS
|--------------------------------------------------------------------------
*/

Route::get('/admin/accounts', [AdminController::class, 'accounts'])->name('admin.accounts');


Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings');


Route::post('/admin/settings/commission', [AdminController::class, 'updateCommission'])->name('admin.settings.commission.update');


Route::post('/admin/settings/delivery-fee', [AdminController::class, 'updateDeliveryFee'])->name('admin.settings.delivery-fee.update');


Route::post('/admin/settings/announcements', [AdminController::class, 'storeAnnouncement'])->name('admin.settings.announcements.store');


Route::post('/admin/settings/announcements/{id}/toggle', [AdminController::class, 'toggleAnnouncement'])->name('admin.settings.announcements.toggle');


Route::delete('/admin/settings/announcements/{id}', [AdminController::class, 'deleteAnnouncement'])->name('admin.settings.announcements.delete');


Route::post('/admin/settings/policies', [AdminController::class, 'updatePolicies'])->name('admin.settings.policies.update');


Route::delete('/admin/accounts/{id}', [AdminController::class, 'deleteAccount'])->name('admin.accounts.delete');


Route::post('/admin/accounts/{id}/status', [AdminController::class, 'updateAccountStatus'])->name('admin.accounts.status');


/*
|--------------------------------------------------------------------------
| ADMIN REPORTS
|--------------------------------------------------------------------------
*/

Route::get('/admin/reports', [AdminController::class, 'reports'])->name('admin.reports');

/*
|--------------------------------------------------------------------------
| ADMIN — SELLER, BUYER & LOGISTICS APPLICATIONS
|
| Rider applications moved to Logistics (see logistics.riders) — this
| screen now covers the other three roles that still need Admin approval.
|--------------------------------------------------------------------------
*/

Route::get('/admin/applications', [AdminController::class, 'applications'])->name('admin.applications');


/*
|--------------------------------------------------------------------------
| ADMIN — LOGISTICS OVERVIEW (READ-ONLY)
|
| Rider vetting and parcel/sorting-center operations are fully owned by
| the Logistics role (see logistics.riders / logistics.parcels) — Admin
| has no action buttons here, only visibility into what's happening.
|--------------------------------------------------------------------------
*/

Route::get('/admin/logistics', [AdminController::class, 'logistics'])->name('admin.logistics');


Route::post('/admin/applications/{type}/{id}/approve', [AdminController::class, 'approveApplication'])->name('admin.applications.approve');


Route::post('/admin/applications/{type}/{id}/reject', [AdminController::class, 'rejectApplication'])->name('admin.applications.reject');


Route::get('/admin/applications/{type}/{id}/document/{field}', [AdminController::class, 'applicationDocument'])->name('admin.applications.document');

/*
|--------------------------------------------------------------------------
| ADMIN ORDERS
|--------------------------------------------------------------------------
*/

Route::get('/admin/orders', [AdminController::class, 'orders'])->name('admin.orders');

Route::get('/admin/order/{id}', [AdminController::class, 'orderDetails'])->name('admin.order.details');

Route::post('/admin/order/{id}/status', [AdminController::class, 'updateOrderStatus'])->name('admin.order.status');



/*
|--------------------------------------------------------------------------
| SELLER
|--------------------------------------------------------------------------
*/

Route::get('/seller', [SellerController::class, 'dashboard'])->name('seller.dashboard');

/*
|--------------------------------------------------------------------------
| SELLER PROFILE
|--------------------------------------------------------------------------
*/

Route::get('/seller/profile', [SellerController::class, 'profile'])->name('seller.profile');


Route::post('/seller/profile', [SellerController::class, 'updateProfile'])->name('seller.profile.update');


Route::post('/seller/profile/photo', [SellerController::class, 'updatePhoto'])->name('seller.profile.photo');


Route::post('/seller/profile/password', [SellerController::class, 'updatePassword'])->name('seller.profile.password');

/*
|--------------------------------------------------------------------------
| SELLER — CUSTOMER FEEDBACK / REVIEWS
|--------------------------------------------------------------------------
*/

Route::get('/seller/reviews', [SellerController::class, 'reviews'])->name('seller.reviews');


Route::post('/seller/reviews/{id}/reply', [SellerController::class, 'replyReview'])->name('seller.reviews.reply');


Route::get('/seller/reports', [SellerController::class, 'reports'])->name('seller.reports');


Route::get('/seller/vouchers', [SellerController::class, 'vouchers'])->name('seller.vouchers');


Route::post('/seller/vouchers', [SellerController::class, 'storeVoucher'])->name('seller.vouchers.store');


Route::post('/seller/vouchers/{id}/toggle', [SellerController::class, 'toggleVoucher'])->name('seller.vouchers.toggle');


Route::delete('/seller/vouchers/{id}', [SellerController::class, 'deleteVoucher'])->name('seller.vouchers.delete');


Route::get('/seller/products/{id}/variations', [SellerController::class, 'variations'])->name('seller.products.variations');


Route::post('/seller/products/{id}/variations', [SellerController::class, 'storeVariation'])->name('seller.products.variations.store');


Route::delete('/seller/products/{id}/variations/{variationId}', [SellerController::class, 'deleteVariation'])->name('seller.products.variations.delete');

/*
|--------------------------------------------------------------------------
| SELLER ADD PRODUCT
|--------------------------------------------------------------------------
*/

Route::get('/seller/products/create', [SellerController::class, 'showCreateProduct'])->name('seller.products.create');


Route::get('/seller/add-product', [SellerController::class, 'redirectLegacyAddProduct']);



/*
|--------------------------------------------------------------------------
| SELLER SAVE PRODUCT
|--------------------------------------------------------------------------
*/

Route::post('/seller/products/store', [SellerController::class, 'storeProduct'])->name('seller.products.store');


/*
|--------------------------------------------------------------------------
| SELLER ORDERS
|--------------------------------------------------------------------------
*/

Route::get('/seller/orders', [SellerController::class, 'orders'])->name('seller.orders');


/*
|--------------------------------------------------------------------------
| SELLER ORDER DETAILS
|--------------------------------------------------------------------------
*/

Route::get('/seller/order/{id}', [SellerController::class, 'orderDetails'])->name('seller.order.details');


Route::get('/seller/order/{id}/waybill', [SellerController::class, 'orderWaybill'])->name('seller.order.waybill');


/*
|--------------------------------------------------------------------------
| SELLER UPDATE ORDER STATUS
|--------------------------------------------------------------------------
*/

Route::post('/seller/order/{id}/status', [SellerController::class, 'updateOrderStatus'])->name('seller.order.status');


Route::post('/seller/order/{id}/confirm-pickup', [SellerController::class, 'confirmPickup'])->name('seller.order.confirm-pickup');

/*
|--------------------------------------------------------------------------
| RIDER
|--------------------------------------------------------------------------
*/

Route::get('/rider', [RiderController::class, 'dashboard'])->name('rider.dashboard');


/*
|--------------------------------------------------------------------------
| RIDER DELIVERIES
|--------------------------------------------------------------------------
*/

Route::get('/rider/deliveries', [RiderController::class, 'deliveries'])->name('rider.deliveries');

/*
|--------------------------------------------------------------------------
| RIDER CLAIM DELIVERY
|--------------------------------------------------------------------------
*/

Route::post('/rider/delivery/{id}/claim', [RiderController::class, 'claimDelivery'])->name('rider.delivery.claim');


Route::post('/rider/delivery/{id}/confirm-pickup', [RiderController::class, 'confirmPickup'])->name('rider.delivery.confirm-pickup');

/*
|--------------------------------------------------------------------------
| RIDER DELIVERY DETAILS
|--------------------------------------------------------------------------
*/

Route::get('/rider/delivery/{id}', [RiderController::class, 'deliveryDetails'])->name('rider.delivery.details');


/*
|--------------------------------------------------------------------------
| RIDER UPDATE DELIVERY STATUS
|--------------------------------------------------------------------------
*/

Route::post('/rider/delivery/{id}/status', [RiderController::class, 'updateStatus'])->name('rider.delivery.status');

/*
|--------------------------------------------------------------------------
| RIDER PROFILE
|--------------------------------------------------------------------------
*/

Route::get('/rider/profile', [RiderController::class, 'profile'])->name('rider.profile');


Route::get('/rider/profit', [RiderController::class, 'profit'])->name('rider.profit');


Route::get('/rider/deliveries/history', [RiderController::class, 'deliveryHistory'])->name('rider.deliveries.history');




/*
|--------------------------------------------------------------------------
| STORE / HOME
|--------------------------------------------------------------------------
*/

Route::get('/', [ShopController::class, 'home'])->name('home');



/*
|--------------------------------------------------------------------------
| PRODUCT DETAILS
|--------------------------------------------------------------------------
*/

Route::get('/product-details/{id}', [ShopController::class, 'productDetails'])->name('product.details');

/*
|--------------------------------------------------------------------------
| CART
|--------------------------------------------------------------------------
*/


// Add to cart
Route::post('/cart/add/{id}', [CartController::class, 'add'])->name('cart.add');

/*
|--------------------------------------------------------------------------
| BUY NOW
|--------------------------------------------------------------------------
|
| FIX: this route was previously registered twice (identical duplicate)
| under the same name 'buy.now'. Kept a single, combined version here:
| validates quantity from the request like the original first version,
| defaulting to 1 if not supplied — same behaviour either caller relied on.
|
*/

Route::post('/buy-now/{id}', [CartController::class, 'buyNow'])->name('buy.now');


// Update cart
Route::post('/cart/update/{key}', [CartController::class, 'update'])->name('cart.update');


// Remove from cart
Route::post('/cart/remove/{key}', [CartController::class, 'remove'])->name('cart.remove');


// Cart page
Route::get('/cart', [CartController::class, 'index'])->name('cart');


Route::post('/cart/voucher/apply', [CartController::class, 'applyVoucher'])->name('cart.voucher.apply');


Route::post('/cart/voucher/remove', [CartController::class, 'removeVoucher'])->name('cart.voucher.remove');


/*
|--------------------------------------------------------------------------
| CHECKOUT
|--------------------------------------------------------------------------
*/

Route::get('/checkout', [CartController::class, 'checkout'])->name('checkout');



/*
|--------------------------------------------------------------------------
| PLACE ORDER
|--------------------------------------------------------------------------
*/

Route::post('/checkout/place-order', [CartController::class, 'placeOrder'])->name('checkout.place');


/*
|--------------------------------------------------------------------------
| ORDER SUCCESS
|--------------------------------------------------------------------------
*/

Route::get('/orders/success/{id}', [CartController::class, 'orderSuccess'])->name('orders.success');



/*
|--------------------------------------------------------------------------
| SELLER EDIT PRODUCT
|--------------------------------------------------------------------------
*/

Route::get('/seller/products/{id}/edit', [SellerController::class, 'editProduct'])->name('seller.products.edit');

/*
|--------------------------------------------------------------------------
| SELLER UPDATE PRODUCT
|--------------------------------------------------------------------------
*/

Route::put('/seller/products/{id}', [SellerController::class, 'updateProduct'])->name('seller.products.update');



/*
|--------------------------------------------------------------------------
| SELLER DELETE PRODUCT
|--------------------------------------------------------------------------
*/

Route::delete('/seller/products/{id}', [SellerController::class, 'deleteProduct'])->name('seller.products.delete');


Route::post('/seller/products/{id}/archive', [SellerController::class, 'archiveProduct'])->name('seller.products.archive');


Route::post('/seller/products/{id}/unarchive', [SellerController::class, 'unarchiveProduct'])->name('seller.products.unarchive');



Route::get('/categories', [ShopController::class, 'categories'])->name('categories');


Route::post('/buyer/order/{orderId}/return-refund', [BuyerController::class, 'requestReturnRefund'])->name('buyer.return-refund.store');


// =====================================================
// SELLER - RETURN / REFUND REQUESTS
// =====================================================

Route::post('/seller/return-refund/{id}/approve', [SellerController::class, 'approveReturnRefund'])->name('seller.return-refund.approve');


Route::post('/seller/return-refund/{id}/reject', [SellerController::class, 'rejectReturnRefund'])->name('seller.return-refund.reject');


// =====================================================
// SELLER RETURN / REFUND — MARK AS RETURNED
// =====================================================

Route::post('/seller/return-refund/{id}/returned', [SellerController::class, 'markReturnRefundReturned'])->name('seller.return-refund.returned');


// =====================================================
// SELLER REFUND — START REFUND
// =====================================================

Route::post('/seller/return-refund/{id}/refund-processing', [SellerController::class, 'startReturnRefundProcessing'])->name('seller.return-refund.processing');


// =====================================================
// SELLER REFUND — COMPLETE REFUND
// =====================================================

Route::post('/seller/return-refund/{id}/complete', [SellerController::class, 'completeReturnRefund'])->name('seller.return-refund.complete');

// =========================
// RIDER APPLICATION
// =========================

Route::get('/rider/apply', [RiderController::class, 'showApply'])->name('rider.apply');


// =========================
// RIDER APPLICATION SUBMIT
// =========================

Route::post('/rider/apply', [RiderController::class, 'submitApply'])->middleware('throttle:5,10')->name('rider.apply.submit');


// Buyer Registration Page
Route::get('/buyer/register', [BuyerController::class, 'showRegister'])->name('buyer.register');


// Buyer Registration Submit
Route::post('/buyer/register', [BuyerController::class, 'register'])->middleware('throttle:5,10')->name('buyer.register.submit');


// Seller Registration Page
Route::get('/seller/register', [SellerController::class, 'showRegister'])->name('seller.register');


// Seller Registration Submit
Route::post('/seller/register', [SellerController::class, 'register'])->middleware('throttle:5,10')->name('seller.register.submit');


// Logistics Registration Page
Route::get('/logistics/register', [LogisticsController::class, 'showRegister'])->name('logistics.register');


// Logistics Registration Submit
Route::post('/logistics/register', [LogisticsController::class, 'register'])->middleware('throttle:5,10')->name('logistics.register.submit');


/*
|--------------------------------------------------------------------------
| LOGISTICS DASHBOARD
|--------------------------------------------------------------------------
*/

Route::get('/logistics', [LogisticsController::class, 'dashboard'])->name('logistics.dashboard');


/*
|--------------------------------------------------------------------------
| LOGISTICS PROFILE
|--------------------------------------------------------------------------
*/

Route::get('/logistics/profile', [LogisticsController::class, 'profile'])->name('logistics.profile');


Route::post('/logistics/profile', [LogisticsController::class, 'updateProfile'])->name('logistics.profile.update');


Route::post('/logistics/profile/photo', [LogisticsController::class, 'updatePhoto'])->name('logistics.profile.photo');


Route::post('/logistics/profile/password', [LogisticsController::class, 'updatePassword'])->name('logistics.profile.password');


/*
|--------------------------------------------------------------------------
| LOGISTICS — RIDER MANAGEMENT
|--------------------------------------------------------------------------
*/

Route::get('/logistics/riders', [LogisticsController::class, 'riders'])->name('logistics.riders');


Route::post('/logistics/riders/{riderId}/areas', [LogisticsController::class, 'storeRiderArea'])->name('logistics.riders.areas.store');


Route::post('/logistics/riders/areas/{areaId}/delete', [LogisticsController::class, 'deleteRiderArea'])->name('logistics.riders.areas.delete');


/*
|--------------------------------------------------------------------------
| LOGISTICS — PARCEL SORTING
|--------------------------------------------------------------------------
*/

Route::get('/logistics/parcels', [LogisticsController::class, 'parcels'])->name('logistics.parcels');


Route::post('/logistics/parcels/{id}/confirm-received', [LogisticsController::class, 'confirmParcelReceived'])->name('logistics.parcels.confirm-received');


Route::post('/logistics/parcels/{id}/assign', [LogisticsController::class, 'assignParcel'])->name('logistics.parcels.assign');


Route::post('/logistics/parcels/{id}/reschedule', [LogisticsController::class, 'rescheduleParcel'])->name('logistics.parcels.reschedule');


Route::post('/logistics/parcels/{id}/return-to-seller', [LogisticsController::class, 'returnParcelToSeller'])->name('logistics.parcels.return-to-seller');


Route::post('/seller/order/{id}/restock', [SellerController::class, 'restockOrder'])->name('seller.order.restock');


Route::post('/logistics/riders/{id}/approve', [LogisticsController::class, 'approveRider'])->name('logistics.riders.approve');


Route::post('/logistics/riders/{id}/reject', [LogisticsController::class, 'rejectRider'])->name('logistics.riders.reject');


Route::get('/logistics/riders/{id}/document/{field}', [LogisticsController::class, 'riderDocument'])->name('logistics.riders.document');


Route::post('/logistics/riders/{userId}/toggle-status', [LogisticsController::class, 'toggleRiderStatus'])->name('logistics.riders.toggle-status');


// Verify OTP Page
Route::get('/verify-otp', [AuthController::class, 'showVerifyOtp'])->name('otp.show');


// Verify OTP Submit
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('otp.verify');


// Resend OTP
Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:3,5')->name('otp.resend');


Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');


Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');


// =========================
// ADMIN NOTIFICATIONS
// =========================

Route::get('/admin/notifications', [AdminController::class, 'notifications'])->name('admin.notifications');


Route::post('/admin/notifications/{id}/read', [AdminController::class, 'markNotificationRead'])->name('admin.notifications.read');


Route::get('/seller/notifications', [SellerController::class, 'notifications'])->name('seller.notifications');


Route::post('/seller/notifications/{id}/read', [SellerController::class, 'markNotificationRead'])->name('seller.notifications.read');


Route::get('/logistics/notifications', [LogisticsController::class, 'notifications'])->name('logistics.notifications');


Route::post('/logistics/notifications/{id}/read', [LogisticsController::class, 'markNotificationRead'])->name('logistics.notifications.read');


Route::get('/rider/notifications', [RiderController::class, 'notifications'])->name('rider.notifications');

Route::post('/rider/notifications/{id}/read', [RiderController::class, 'markNotificationRead'])->name('rider.notifications.read');


/*
|--------------------------------------------------------------------------
| COMPLAINTS & DISPUTES
|--------------------------------------------------------------------------
|
| Any logged-in buyer, seller, or rider can file a complaint (about
| another user, an order, or a general platform issue). Admin reviews
| and resolves them from the admin panel.
|
*/

Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');


Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaints.store');


Route::get('/admin/complaints', [AdminController::class, 'complaints'])->name('admin.complaints');


Route::post('/admin/complaints/{id}/status', [AdminController::class, 'updateComplaintStatus'])->name('admin.complaints.update');


/*
|--------------------------------------------------------------------------
| MESSAGES / CHAT
|--------------------------------------------------------------------------
|
| A simple inbox-and-thread messaging system between any two BoomBuy
| accounts (buyer, seller, rider, or admin). Not real-time — messages
| load on page visit/refresh, same as the rest of this app.
|
*/

Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');


Route::get('/messages/{userId}', [MessageController::class, 'thread'])->name('messages.thread');


Route::post('/messages/{userId}', [MessageController::class, 'store'])->name('messages.store');


/*
|--------------------------------------------------------------------------
| SELLER COMPLIANCE MONITORING
|--------------------------------------------------------------------------
|
| Cross-checks each approved seller's products against the business
| category they were approved for (set by admin during application
| approval), and lets admin flag individual products as prohibited or
| inappropriate — flagged products are hidden from the storefront
| immediately (see /products route) — plus issue a warning notification
| or jump straight to suspending the seller's account.
|
*/

Route::get('/admin/compliance', [AdminController::class, 'compliance'])->name('admin.compliance');


Route::post('/admin/compliance/products/{id}/flag', [AdminController::class, 'flagProduct'])->name('admin.compliance.flag');


Route::post('/admin/compliance/products/{id}/unflag', [AdminController::class, 'unflagProduct'])->name('admin.compliance.unflag');


Route::post('/admin/compliance/warn/{userId}', [AdminController::class, 'warnSeller'])->name('admin.compliance.warn');
