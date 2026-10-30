<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StoreCategoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\BuyerVoucherController;
use App\Http\Controllers\SellerEarningsController;
use App\Http\Controllers\ContactChangeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\RiderController;
use App\Http\Controllers\LogisticsController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SortingCenterController;
use App\Http\Controllers\PromoBannerController;

// Helper functions (requireUserRole, cart helpers, OTP…) live in
// app/Helpers/helpers.php, autoloaded via composer.json.

/*
|--------------------------------------------------------------------------
| RIDER PROFILE PHOTO
|--------------------------------------------------------------------------
*/

Route::post('/logistics/rider/profile/photo', [RiderController::class, 'updatePhoto'])->name('rider.profile.photo');
Route::post('/logistics/rider/profile/password', [RiderController::class, 'updatePassword'])->name('rider.profile.password');
Route::post('/logistics/rider/profile', [RiderController::class, 'updateProfile'])->name('rider.profile.update');


Route::get('/login', [AuthController::class, 'showLogin'])->name('login');


// throttle:N,M,name = at most N attempts per M minutes per visitor — stops
// password / OTP guessing and email spam on the unauthenticated forms. The
// name gives each form its own count; without it every throttled route
// shares one counter, and typing in the search box would use up the login.
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1,login')->name('login.submit');


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

Route::post('/forgot-password', [AuthController::class, 'sendResetOtp'])->middleware('throttle:5,10,forgot-password')->name('password.email');


// ==========================================================
// RESET PASSWORD PAGE
// ==========================================================

Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset');


// ==========================================================
// UPDATE NEW PASSWORD
// ==========================================================

Route::post('/reset-password', [AuthController::class, 'updatePassword'])->middleware('throttle:10,1,reset-password')->name('password.update');



// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


/*
|--------------------------------------------------------------------------
| BUYER
|--------------------------------------------------------------------------
*/

Route::get('/buyer', [BuyerController::class, 'dashboard'])->name('buyer.dashboard');

// Mobile "Me" screen: the dashboard's account sidebar as its own page.
Route::get('/buyer/account', [BuyerController::class, 'account'])->name('buyer.account');

/*
|--------------------------------------------------------------------------
| BUYER PROFILE
|--------------------------------------------------------------------------
*/

Route::get('/buyer/profile', [BuyerController::class, 'profile'])->name('buyer.profile');


Route::post('/buyer/profile', [BuyerController::class, 'updateProfile'])->name('buyer.profile.update');


Route::post('/buyer/profile/photo', [BuyerController::class, 'updatePhoto'])->name('buyer.profile.photo');


Route::post('/buyer/profile/password', [BuyerController::class, 'updatePassword'])->name('buyer.profile.password');

// Address book
Route::post('/buyer/addresses', [BuyerController::class, 'storeAddress'])->name('buyer.addresses.store');
Route::get('/buyer/addresses/{id}/edit', [BuyerController::class, 'editAddress'])->whereNumber('id')->name('buyer.addresses.edit');
Route::put('/buyer/addresses/{id}', [BuyerController::class, 'updateAddress'])->whereNumber('id')->name('buyer.addresses.update');
Route::post('/buyer/addresses/{id}/default', [BuyerController::class, 'setDefaultAddress'])->whereNumber('id')->name('buyer.addresses.default');
Route::delete('/buyer/addresses/{id}', [BuyerController::class, 'deleteAddress'])->whereNumber('id')->name('buyer.addresses.delete');

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
Route::get('/buyer/orders/{id}/track', [BuyerController::class, 'track'])->whereNumber('id')->name('buyer.order.track');


Route::post('/buyer/orders/{id}/received', [BuyerController::class, 'markReceived'])->name('buyer.order.received');


Route::post('/buyer/orders/{id}/cancel', [BuyerController::class, 'cancelOrder'])->name('buyer.order.cancel');


Route::post('/buyer/orders/{id}/reorder', [BuyerController::class, 'reorder'])->name('buyer.order.reorder');


Route::post('/buyer/orders/{orderId}/review/{productId}', [BuyerController::class, 'reviewProduct'])->name('buyer.product.review');
Route::put('/buyer/orders/{orderId}/review/{productId}', [BuyerController::class, 'reviewProduct'])->name('buyer.product.review.update');


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


Route::post('/admin/login', [AdminController::class, 'login'])->middleware('throttle:10,1,admin-login')->name('admin.login.submit');


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

// The admin's own name, login email and password.
Route::get('/admin/account', [AdminController::class, 'account'])->name('admin.account');
Route::post('/admin/account', [AdminController::class, 'updateAccount'])->middleware('throttle:10,1,admin-account')->name('admin.account.update');
Route::post('/admin/account/password', [AdminController::class, 'updateAccountPassword'])->middleware('throttle:10,1,admin-account')->name('admin.account.password');


/*
|--------------------------------------------------------------------------
| PRODUCTS
|--------------------------------------------------------------------------
*/

Route::get('/products', [ShopController::class, 'products'])->name('products');

// A seller's shop: the shop listing narrowed to one seller, with the shop's header.
Route::get('/shop/{seller}', [ShopController::class, 'sellerShop'])->whereNumber('seller')->name('shop.seller');

// Follow a store (B5) and the buyer's Following list
Route::post('/shop/{seller}/follow', [\App\Http\Controllers\StoreFollowController::class, 'toggle'])->whereNumber('seller')->name('store.follow');
Route::get('/following', [\App\Http\Controllers\StoreFollowController::class, 'index'])->name('buyer.following');

// "Remind me" on an upcoming flash sale (B14)
Route::post('/flash-sales/{id}/remind', [BuyerController::class, 'remindFlashSale'])->whereNumber('id')->name('flash.remind');

// Live suggestions for the navbar search box (JSON).
Route::get('/search/suggestions', [ShopController::class, 'searchSuggestions'])->middleware('throttle:120,1,search')->name('search.suggestions');


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

// People by role: each with an Accounts tab and an Applications tab.
Route::get('/admin/buyers', [AdminController::class, 'buyers'])->name('admin.buyers');
Route::get('/admin/sellers', [AdminController::class, 'sellers'])->name('admin.sellers');

// Old address: sends ?role= to Buyers, Sellers or Logistics → Staff & Riders.
Route::get('/admin/accounts', [AdminController::class, 'accounts'])->name('admin.accounts');


Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings');


Route::post('/admin/settings/commission', [AdminController::class, 'updateCommission'])->name('admin.settings.commission.update');


Route::post('/admin/settings/delivery-fee',[AdminController::class, 'updateDeliveryFee'])->name('admin.settings.delivery-fee.update');

// Sorting Centers: one per town; which center each logistics account works at.
Route::get('/admin/sorting-centers', [SortingCenterController::class, 'index'])->name('admin.sorting-centers');
Route::post('/admin/sorting-centers', [SortingCenterController::class, 'store'])->name('admin.sorting-centers.store');
Route::post('/admin/sorting-centers/{id}/toggle', [SortingCenterController::class, 'toggle'])->name('admin.sorting-centers.toggle');
Route::post('/admin/sorting-centers/branches', [SortingCenterController::class, 'storeBranch'])->name('admin.sorting-centers.branches.store');
Route::post('/admin/sorting-centers/staff/{userId}', [SortingCenterController::class, 'assignStaff'])->name('admin.sorting-centers.staff');


Route::post('/admin/settings/announcements', [AdminController::class, 'storeAnnouncement'])->name('admin.settings.announcements.store');


Route::post('/admin/settings/announcements/{id}/toggle', [AdminController::class, 'toggleAnnouncement'])->name('admin.settings.announcements.toggle');


Route::delete('/admin/settings/announcements/{id}', [AdminController::class, 'deleteAnnouncement'])->name('admin.settings.announcements.delete');


// Promo Banners: the landing page / buyer home hero (main carousel + two side tiles).
Route::get('/admin/banners', [PromoBannerController::class, 'index'])->name('admin.banners');
Route::post('/admin/banners', [PromoBannerController::class, 'store'])->name('admin.banners.store');
Route::post('/admin/banners/{id}', [PromoBannerController::class, 'update'])->whereNumber('id')->name('admin.banners.update');
Route::post('/admin/banners/{id}/toggle', [PromoBannerController::class, 'toggle'])->whereNumber('id')->name('admin.banners.toggle');
Route::delete('/admin/banners/{id}', [PromoBannerController::class, 'destroy'])->whereNumber('id')->name('admin.banners.delete');


Route::post('/admin/settings/policies', [AdminController::class, 'updatePolicies'])->name('admin.settings.policies.update');

// Seller compliance: products mentioning these words are hidden on save.
Route::post('/admin/settings/prohibited-words', [AdminController::class, 'updateProhibitedWords'])->name('admin.settings.prohibited.update');




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

Route::post('/admin/order/{id}/cancel', [AdminController::class, 'cancelOrder'])->name('admin.order.cancel');
Route::post('/admin/order/{id}/return', [AdminController::class, 'forceReturn'])->name('admin.order.force-return');



/*
|--------------------------------------------------------------------------
| SELLER
|--------------------------------------------------------------------------
*/

Route::get('/seller', [SellerController::class, 'dashboard'])->name('seller.dashboard');

// Every product of the shop (live, drafts, archived)
Route::get('/seller/products', [SellerController::class, 'products'])->name('seller.products');

/*
|--------------------------------------------------------------------------
| SELLER PROFILE
|--------------------------------------------------------------------------
*/

Route::get('/seller/profile', [SellerController::class, 'profile'])->name('seller.profile');


Route::post('/seller/profile', [SellerController::class, 'updateProfile'])->name('seller.profile.update');

// Shop name + "about this shop" shown on /shop/{seller}
Route::post('/seller/shop', [SellerController::class, 'updateShop'])->name('seller.shop.update');
Route::post('/seller/shop/vacation', [SellerController::class, 'updateVacation'])->name('seller.shop.vacation');

// Chat auto-reply sent when a buyer messages the shop
Route::post('/seller/auto-reply', [SellerController::class, 'updateAutoReply'])->name('seller.autoreply.update');


Route::post('/seller/profile/photo', [SellerController::class, 'updatePhoto'])->name('seller.profile.photo');


Route::post('/seller/profile/password', [SellerController::class, 'updatePassword'])->name('seller.profile.password');

/*
|--------------------------------------------------------------------------
| SELLER — CUSTOMER FEEDBACK / REVIEWS
|--------------------------------------------------------------------------
*/

Route::get('/seller/reviews', [SellerController::class, 'reviews'])->name('seller.reviews');


Route::post('/seller/reviews/{id}/reply', [SellerController::class, 'replyReview'])->name('seller.reviews.reply');
Route::put('/seller/reviews/{id}/reply', [SellerController::class, 'replyReview'])->name('seller.reviews.reply.update');
Route::delete('/seller/reviews/{id}', [SellerController::class, 'deleteReview'])->name('seller.reviews.delete');
Route::post('/seller/reviews/{id}/hide', [SellerController::class, 'deleteReview'])->name('seller.reviews.hide');


Route::get('/seller/reports', [SellerController::class, 'reports'])->name('seller.reports');


Route::get('/seller/vouchers', [SellerController::class, 'vouchers'])->name('seller.vouchers');


Route::post('/seller/vouchers', [SellerController::class, 'storeVoucher'])->name('seller.vouchers.store');


Route::post('/seller/vouchers/{id}/toggle', [SellerController::class, 'toggleVoucher'])->name('seller.vouchers.toggle');


Route::delete('/seller/vouchers/{id}', [SellerController::class, 'deleteVoucher'])->name('seller.vouchers.delete');
Route::put('/seller/vouchers/{id}', [SellerController::class, 'updateVoucher'])->whereNumber('id')->name('seller.vouchers.update');
Route::post('/seller/vouchers/{id}/end', [SellerController::class, 'endVoucher'])->whereNumber('id')->name('seller.vouchers.end');

// Promotions: store sales (S6), flash sales and bundle deals (S8)
Route::get('/seller/promotions', [PromotionController::class, 'index'])->name('seller.promotions');
Route::post('/seller/promotions/sales', [PromotionController::class, 'storeSale'])->name('seller.sales.store');
Route::put('/seller/promotions/sales/{id}', [PromotionController::class, 'updateSale'])->whereNumber('id')->name('seller.sales.update');
Route::post('/seller/promotions/sales/{id}/end', [PromotionController::class, 'endSale'])->whereNumber('id')->name('seller.sales.end');
Route::post('/seller/promotions/flash', [PromotionController::class, 'storeFlash'])->name('seller.flash.store');
Route::post('/seller/promotions/bundles', [PromotionController::class, 'storeBundle'])->name('seller.bundles.store');
Route::post('/seller/promotions/bundles/{id}/toggle', [PromotionController::class, 'toggleBundle'])->whereNumber('id')->name('seller.bundles.toggle');

// Store categories: the store's own categories for its shop page (2 levels, up to 3 per product)
Route::get('/seller/store-categories', [StoreCategoryController::class, 'index'])->name('seller.store-categories');
Route::post('/seller/store-categories', [StoreCategoryController::class, 'store'])->name('seller.store-categories.store');
Route::put('/seller/store-categories/{id}', [StoreCategoryController::class, 'update'])->whereNumber('id')->name('seller.store-categories.update');
Route::post('/seller/store-categories/{id}/move', [StoreCategoryController::class, 'move'])->whereNumber('id')->name('seller.store-categories.move');
Route::post('/seller/store-categories/{id}/toggle', [StoreCategoryController::class, 'toggle'])->whereNumber('id')->name('seller.store-categories.toggle');
Route::delete('/seller/store-categories/{id}', [StoreCategoryController::class, 'destroy'])->whereNumber('id')->name('seller.store-categories.delete');
Route::post('/seller/products/{id}/duplicate', [SellerController::class, 'duplicateProduct'])->whereNumber('id')->name('seller.products.duplicate');
Route::post('/seller/products/bulk', [SellerController::class, 'bulkProducts'])->name('seller.products.bulk');
Route::post('/seller/products/{id}/store-categories', [StoreCategoryController::class, 'assign'])->whereNumber('id')->name('seller.products.store-categories');


Route::get('/seller/products/{id}/variations', [SellerController::class, 'variations'])->name('seller.products.variations');


Route::post('/seller/products/{id}/variations', [SellerController::class, 'storeVariation'])->name('seller.products.variations.store');


Route::put('/seller/products/{id}/variations', [SellerController::class, 'updateVariations'])->name('seller.products.variations.update');

// Phones, tablets, laptops: storage sizes for every color at once.
Route::post('/seller/products/{id}/variations/storage', [SellerController::class, 'addStorage'])->whereNumber('id')->name('seller.products.variations.storage');

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

// Book a BoomBuy rider to collect the packed parcel (instead of dropping it off).
Route::post('/seller/order/{id}/confirm-pickup', [SellerController::class, 'confirmRiderPickup'])->whereNumber('id')->name('seller.order.confirm-pickup');
Route::post('/seller/order/{id}/pickup', [SellerController::class, 'requestPickup'])->whereNumber('id')->name('seller.order.pickup');


// Printable shipping label (waybill no. + QR) to stick on the parcel before rider pickup.
Route::get('/seller/order/{id}/label', [SellerController::class, 'shippingLabel'])->whereNumber('id')->name('seller.order.label');


/*
|--------------------------------------------------------------------------
| RIDER
|--------------------------------------------------------------------------
*/

Route::get('/logistics/rider', [RiderController::class, 'dashboard'])->name('rider.dashboard');

// Items for pickup: sellers' pickup requests in the rider's areas.
Route::get('/logistics/rider/pickups', [RiderController::class, 'pickups'])->name('rider.pickups');
Route::post('/logistics/rider/pickup/{id}/accept', [RiderController::class, 'acceptPickup'])->whereNumber('id')->name('rider.pickup.accept');
Route::post('/logistics/rider/pickup/{id}/picked-up', [RiderController::class, 'confirmPickup'])->whereNumber('id')->name('rider.pickup.picked-up');


/*
|--------------------------------------------------------------------------
| RIDER DELIVERIES
|--------------------------------------------------------------------------
*/

Route::get('/logistics/rider/deliveries', [RiderController::class, 'deliveries'])->name('rider.deliveries');

/*
|--------------------------------------------------------------------------
| RIDER CLAIM DELIVERY
|--------------------------------------------------------------------------
*/




/*
|--------------------------------------------------------------------------
| RIDER DELIVERY DETAILS
|--------------------------------------------------------------------------
*/

Route::get('/logistics/rider/delivery/{id}', [RiderController::class, 'deliveryDetails'])->name('rider.delivery.details');


/*
|--------------------------------------------------------------------------
| RIDER UPDATE DELIVERY STATUS
|--------------------------------------------------------------------------
*/

Route::post('/logistics/rider/delivery/{id}/status', [RiderController::class, 'updateStatus'])->name('rider.delivery.status');

/*
|--------------------------------------------------------------------------
| RIDER PROFILE
|--------------------------------------------------------------------------
*/

Route::get('/logistics/rider/profile', [RiderController::class, 'profile'])->name('rider.profile');


Route::get('/logistics/rider/profit', [RiderController::class, 'profit'])->name('rider.profit');


Route::get('/logistics/rider/deliveries/history', [RiderController::class, 'deliveryHistory'])->name('rider.deliveries.history');

// Rider Modules R1–R7: Online/Offline, job offers, waybill scan, returns, vehicle, COD remittance
Route::post('/logistics/rider/availability', [\App\Http\Controllers\RiderWorkController::class, 'availability'])->name('rider.availability');
Route::post('/logistics/rider/location', [\App\Http\Controllers\RiderWorkController::class, 'location'])->name('rider.location');
Route::get('/logistics/rider/offers', [\App\Http\Controllers\RiderWorkController::class, 'offers'])->name('rider.offers');
Route::post('/logistics/rider/offers/{id}', [\App\Http\Controllers\RiderWorkController::class, 'respond'])->whereNumber('id')->name('rider.offers.respond');
Route::post('/logistics/rider/pickup/{id}/scan', [\App\Http\Controllers\RiderWorkController::class, 'scanPickup'])->whereNumber('id')->name('rider.pickup.scan');
Route::post('/logistics/rider/return/{id}/delivered', [\App\Http\Controllers\RiderWorkController::class, 'returnedToSeller'])->whereNumber('id')->name('rider.return.delivered');
Route::post('/logistics/rider/vehicle', [\App\Http\Controllers\RiderWorkController::class, 'updateVehicle'])->name('rider.vehicle.update');
Route::post('/logistics/rider/cod/remit', [\App\Http\Controllers\RiderWorkController::class, 'remit'])->name('rider.cod.remit');
Route::get('/logistics/rider/document/{field}', [\App\Http\Controllers\RiderWorkController::class, 'document'])->whereIn('field', ['drivers_license', 'or_cr'])->name('rider.document');




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
Route::get('/products/{id}/options', [ShopController::class, 'options'])->whereNumber('id')->name('product.options');

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

// Fresh contents for the navbar cart hover panel (HTML fragment).
Route::get('/cart/preview', [CartController::class, 'preview'])->name('cart.preview');


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

Route::get('/checkout/delivery-quote', [CartController::class, 'deliveryQuote'])->name('checkout.quote');

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

Route::get('/policies', [ShopController::class, 'policies'])->name('policies');

// Download page for the BoomBuy Android app (the APK is public/downloads/boombuy.apk).
Route::view('/app', 'pages.app-download')->name('app.download');


Route::post('/buyer/order/{orderId}/return-refund', [BuyerController::class, 'requestReturnRefund'])->name('buyer.return-refund.store');
Route::post('/buyer/return-refund/{id}/cancel', [BuyerController::class, 'cancelReturnRefund'])->name('buyer.return-refund.cancel');
Route::post('/buyer/return-refund/{id}/escalate', [BuyerController::class, 'escalateReturnRefund'])->name('buyer.return-refund.escalate');


// =====================================================
// SELLER - RETURN / REFUND REQUESTS
// =====================================================

Route::post('/seller/return-refund/{id}/approve', [SellerController::class, 'approveReturnRefund'])->name('seller.return-refund.approve');


Route::post('/seller/return-refund/{id}/reject', [SellerController::class, 'rejectReturnRefund'])->name('seller.return-refund.reject');


// The returned item came back to the seller: put it back in stock. (The
// refund itself is sent by BoomBuy — see admin/returns.)
Route::post('/seller/return-refund/{id}/restock', [SellerController::class, 'restockReturnRefund'])->name('seller.return-refund.restock');


// =====================================================
// SORTING CENTER — RETURNED ITEMS ON THEIR WAY BACK TO THE SELLER
// =====================================================

Route::post('/logistics/returns/{id}/receive', [LogisticsController::class, 'receiveBuyerReturn'])->name('logistics.returns.receive');
Route::post('/logistics/returns/{id}/send', [LogisticsController::class, 'sendBuyerReturn'])->name('logistics.returns.send');
Route::post('/logistics/returns/{id}/arrive', [LogisticsController::class, 'arriveBuyerReturn'])->name('logistics.returns.arrive');
Route::post('/logistics/returns/{id}/hand-to-seller', [LogisticsController::class, 'handBuyerReturnToSeller'])->name('logistics.returns.hand-to-seller');


// =====================================================
// ADMIN — RETURNS & REFUNDS, SELLER PAYOUTS
// =====================================================

Route::get('/admin/returns', [AdminController::class, 'returns'])->name('admin.returns');
Route::post('/admin/returns/{id}/decide', [AdminController::class, 'decideReturn'])->name('admin.returns.decide');
Route::post('/admin/returns/{id}/refunded', [AdminController::class, 'refundReturn'])->name('admin.returns.refunded');





// =========================
// RIDER APPLICATION
// =========================

Route::get('/logistics/rider/apply', [RiderController::class, 'showApply'])->name('rider.apply');


// =========================
// RIDER APPLICATION SUBMIT
// =========================

Route::post('/logistics/rider/apply', [RiderController::class, 'submitApply'])->middleware('throttle:5,10,register')->name('rider.apply.submit');


// =========================
// RIDER ACCOUNT ACTIVATION (riders added by a logistics company)
// =========================

Route::get('/logistics/rider/activate/{token}', [RiderController::class, 'showActivation'])->name('rider.activate');

Route::post('/logistics/rider/activate/{token}', [RiderController::class, 'activate'])->middleware('throttle:10,10,rider-activate')->name('rider.activate.submit');


// Buyer Registration Page
Route::get('/buyer/register', [BuyerController::class, 'showRegister'])->name('buyer.register');


// Buyer Registration Submit
Route::post('/buyer/register', [BuyerController::class, 'register'])->middleware('throttle:5,10,register')->name('buyer.register.submit');


// Seller Registration Page
Route::get('/seller/register', [SellerController::class, 'showRegister'])->name('seller.register');


// Seller Registration Submit
Route::post('/seller/register', [SellerController::class, 'register'])->middleware('throttle:5,10,register')->name('seller.register.submit');


/*
|--------------------------------------------------------------------------
| BOOMBUY LOGISTICS PORTAL (boombuy.store/logistics)
|--------------------------------------------------------------------------
| Its own landing page, login and sign-up, apart from the marketplace.
| Logistics staff sign in here; buyers, sellers and the admin use /login.
| Riders use the BoomBuy app only (see KeepRidersInApp). Same app and
| database, so parcels still follow the orders.
*/

Route::get('/logistics', [AuthController::class, 'showLogisticsHome'])->name('logistics.home');

Route::get('/logistics/login', [AuthController::class, 'showLogisticsLogin'])->name('logistics.login');

Route::post('/logistics/login', [AuthController::class, 'login'])->middleware('throttle:10,1,login')->name('logistics.login.submit');


// Rider pages used to live at /rider/... — old links and emails still work.
Route::get('/rider/{path?}', fn (?string $path = null) => redirect('/logistics/rider' . ($path ? '/' . $path : ''), 301))
    ->where('path', '.*');


// Logistics Registration Page
Route::get('/logistics/register', [LogisticsController::class, 'showRegister'])->name('logistics.register');


// Logistics Registration Submit
Route::post('/logistics/register', [LogisticsController::class, 'register'])->middleware('throttle:5,10,register')->name('logistics.register.submit');


/*
|--------------------------------------------------------------------------
| LOGISTICS DASHBOARD
|--------------------------------------------------------------------------
*/

Route::get('/logistics/dashboard', [LogisticsController::class, 'dashboard'])->name('logistics.dashboard');

// Logistics Modules L1–L8: shipments, dispatch, hub scans, exceptions, RTS, COD, reports, Zones & Rates, audit log
Route::get('/logistics/operations', [\App\Http\Controllers\LogisticsOpsController::class, 'index'])->name('logistics.ops');
Route::post('/logistics/dispatch/{id}/auto', [\App\Http\Controllers\LogisticsOpsController::class, 'autoAssign'])->whereNumber('id')->name('logistics.dispatch.auto');
Route::post('/logistics/dispatch/{id}/assign', [\App\Http\Controllers\LogisticsOpsController::class, 'assign'])->whereNumber('id')->name('logistics.dispatch.assign');
Route::post('/logistics/dispatch/{id}/reassign', [\App\Http\Controllers\LogisticsOpsController::class, 'reassign'])->whereNumber('id')->name('logistics.dispatch.reassign');
Route::post('/logistics/dispatch/bulk', [\App\Http\Controllers\LogisticsOpsController::class, 'bulkAssign'])->name('logistics.dispatch.bulk');
Route::post('/logistics/hub/scan', [\App\Http\Controllers\LogisticsOpsController::class, 'scan'])->name('logistics.hub.scan');
Route::post('/logistics/shipments/{id}/override', [\App\Http\Controllers\LogisticsOpsController::class, 'override'])->whereNumber('id')->name('logistics.shipments.override');
Route::post('/logistics/shipments/{id}/lost', [\App\Http\Controllers\LogisticsOpsController::class, 'lost'])->whereNumber('id')->name('logistics.shipments.lost');
Route::post('/logistics/shipments/{id}/damaged', [\App\Http\Controllers\LogisticsOpsController::class, 'damaged'])->whereNumber('id')->name('logistics.shipments.damaged');
Route::post('/logistics/shipments/{id}/return-rider', [\App\Http\Controllers\LogisticsOpsController::class, 'returnRider'])->whereNumber('id')->name('logistics.shipments.return-rider');
Route::post('/logistics/riders/{riderId}/suspend', [\App\Http\Controllers\LogisticsOpsController::class, 'suspendRider'])->whereNumber('riderId')->name('logistics.riders.suspend');
Route::post('/logistics/riders/{riderId}/reactivate', [\App\Http\Controllers\LogisticsOpsController::class, 'reactivateRider'])->whereNumber('riderId')->name('logistics.riders.reactivate');
Route::get('/logistics/cod', [\App\Http\Controllers\LogisticsOpsController::class, 'cod'])->name('logistics.cod');
Route::post('/logistics/cod/{id}/confirm', [\App\Http\Controllers\LogisticsOpsController::class, 'confirmRemittance'])->whereNumber('id')->name('logistics.cod.confirm');
Route::get('/logistics/reports/export', [\App\Http\Controllers\LogisticsOpsController::class, 'export'])->name('logistics.reports.export');
Route::get('/logistics/rates', [\App\Http\Controllers\LogisticsOpsController::class, 'rates'])->name('logistics.rates');
Route::put('/logistics/rates/{id}', [\App\Http\Controllers\LogisticsOpsController::class, 'updateRate'])->whereNumber('id')->name('logistics.rates.update');
Route::post('/logistics/zones', [\App\Http\Controllers\LogisticsOpsController::class, 'storeZone'])->name('logistics.zones.store');
Route::get('/logistics/audit-log', [\App\Http\Controllers\LogisticsOpsController::class, 'audit'])->name('logistics.audit');


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
Route::get('/logistics/branches', [\App\Http\Controllers\LogisticsBranchController::class, 'index'])->name('logistics.branches');
Route::post('/logistics/branches', [\App\Http\Controllers\LogisticsBranchController::class, 'store'])->name('logistics.branches.store');
Route::post('/logistics/branches/{id}/staff', [\App\Http\Controllers\LogisticsBranchController::class, 'storeStaff'])->whereNumber('id')->name('logistics.branches.staff');
Route::post('/logistics/branches/{id}/toggle', [\App\Http\Controllers\LogisticsBranchController::class, 'toggle'])->whereNumber('id')->name('logistics.branches.toggle');


Route::post('/logistics/parcels/{id}/confirm-received', [LogisticsController::class, 'confirmParcelReceived'])->name('logistics.parcels.confirm-received');


// A scanned shipping label's QR code lands here.
Route::get('/logistics/scan/{code}', [LogisticsController::class, 'scanParcel'])->name('logistics.scan');

Route::post('/logistics/parcels/{id}/dispatch', [LogisticsController::class, 'dispatchParcel'])->name('logistics.parcels.dispatch');
Route::post('/logistics/parcels/{id}/sort', [LogisticsController::class, 'sortParcel'])->whereNumber('id')->name('logistics.parcels.sort');

Route::post('/logistics/parcels/{id}/confirm-arrival', [LogisticsController::class, 'confirmParcelArrival'])->name('logistics.parcels.confirm-arrival');

Route::post('/logistics/parcels/dispatch-all', [LogisticsController::class, 'dispatchAllParcels'])->name('logistics.parcels.dispatch-all');
Route::post('/logistics/parcels/{id}/to-branch', [LogisticsController::class, 'dispatchParcelToBranch'])->whereNumber('id')->name('logistics.parcels.to-branch');
Route::post('/logistics/parcels/{id}/to-center', [LogisticsController::class, 'sendParcelToCenter'])->whereNumber('id')->name('logistics.parcels.to-center');
Route::post('/logistics/parcels/to-center-all', [LogisticsController::class, 'sendAllToCenter'])->name('logistics.parcels.to-center-all');
Route::post('/logistics/parcels/to-branch-all', [LogisticsController::class, 'dispatchAllToBranch'])->name('logistics.parcels.to-branch-all');
Route::post('/logistics/parcels/{id}/at-branch', [LogisticsController::class, 'confirmParcelAtBranch'])->whereNumber('id')->name('logistics.parcels.at-branch');

Route::post('/logistics/parcels/{id}/hand-to-buyer', [LogisticsController::class, 'handParcelToBuyer'])->name('logistics.parcels.hand-to-buyer');

Route::post('/logistics/parcels/{id}/confirm-return', [LogisticsController::class, 'confirmReturnArrival'])->name('logistics.parcels.confirm-return');

Route::post('/logistics/parcels/{id}/hand-back', [LogisticsController::class, 'handParcelBackToSeller'])->name('logistics.parcels.hand-back');

Route::post('/logistics/riders/{riderId}/receive-cod', [LogisticsController::class, 'receiveRiderCod'])->name('logistics.riders.receive-cod');

Route::post('/logistics/parcels/{id}/assign', [LogisticsController::class, 'assignParcel'])->name('logistics.parcels.assign');


Route::post('/logistics/parcels/{id}/confirm-back', [LogisticsController::class, 'confirmParcelBack'])->name('logistics.parcels.confirm-back');
Route::post('/logistics/parcels/{id}/reschedule', [LogisticsController::class, 'rescheduleParcel'])->name('logistics.parcels.reschedule');


Route::post('/logistics/parcels/{id}/return-to-seller', [LogisticsController::class, 'returnParcelToSeller'])->name('logistics.parcels.return-to-seller');


Route::post('/seller/order/{id}/restock', [SellerController::class, 'restockOrder'])->name('seller.order.restock');


Route::post('/logistics/riders/{id}/approve', [LogisticsController::class, 'approveRider'])->name('logistics.riders.approve');


Route::post('/logistics/riders/{id}/reject', [LogisticsController::class, 'rejectRider'])->name('logistics.riders.reject');


Route::get('/logistics/riders/{id}/document/{field}', [LogisticsController::class, 'riderDocument'])->name('logistics.riders.document');


Route::post('/logistics/riders/{userId}/toggle-status', [LogisticsController::class, 'toggleRiderStatus'])->name('logistics.riders.toggle-status');


// Logistics adds a rider itself; the rider activates by email.
Route::get('/logistics/riders/create', [LogisticsController::class, 'createRider'])->name('logistics.riders.create');

Route::post('/logistics/riders/create', [LogisticsController::class, 'storeRider'])->name('logistics.riders.store');

Route::post('/logistics/riders/{userId}/resend-activation', [LogisticsController::class, 'resendRiderActivation'])->middleware('throttle:5,10,rider-activation')->name('logistics.riders.resend-activation');


// Verify OTP Page
Route::get('/verify-otp',[AuthController::class, 'showVerifyOtp'])->name('otp.show');


// Verify OTP Submit
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1,otp-verify')->name('otp.verify');


// Resend OTP
Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:3,5,otp-resend')->name('otp.resend');


Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');


Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

// Clicking a notification: mark it read and jump to what it's about.
Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');

Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');


// =========================
// ADMIN NOTIFICATIONS
// =========================

Route::get('/admin/notifications', [AdminController::class, 'notifications'])->name('admin.notifications');


Route::post('/admin/notifications/{id}/read', [AdminController::class, 'markNotificationRead'])->name('admin.notifications.read');


Route::get('/seller/notifications', [SellerController::class, 'notifications'])->name('seller.notifications');


Route::post('/seller/notifications/{id}/read', [SellerController::class, 'markNotificationRead'])->name('seller.notifications.read');


Route::get('/logistics/notifications', [LogisticsController::class, 'notifications'])->name('logistics.notifications');


Route::post('/logistics/notifications/{id}/read', [LogisticsController::class, 'markNotificationRead'])->name('logistics.notifications.read');


Route::get('/logistics/rider/notifications', [RiderController::class, 'notifications'])->name('rider.notifications');

Route::post('/logistics/rider/notifications/{id}/read', [RiderController::class, 'markNotificationRead'])->name('rider.notifications.read');


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

// Private evidence photos — only the people involved and the admin.
Route::get('/complaints/{id}/evidence', [EvidenceController::class, 'complaint'])->whereNumber('id')->name('complaints.evidence');
Route::get('/return-refund/{id}/evidence', [EvidenceController::class, 'returnRequest'])->whereNumber('id')->name('return-refund.evidence');
Route::get('/orders/{id}/delivery-proof', [EvidenceController::class, 'deliveryProof'])->whereNumber('id')->name('orders.delivery-proof');


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

// Admin's "New message" search — must stay above /messages/{userId}.
Route::get('/messages/recipients', [MessageController::class, 'recipients'])->middleware('throttle:60,1,recipients')->name('messages.recipients');


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
Route::get('/admin/compliance/riders', [AdminController::class, 'complianceRiders'])->name('admin.compliance.riders');
Route::get('/admin/compliance/buyers', [AdminController::class, 'complianceBuyers'])->name('admin.compliance.buyers');


Route::post('/admin/compliance/products/{id}/flag', [AdminController::class, 'flagProduct'])->name('admin.compliance.flag');


Route::post('/admin/compliance/products/{id}/unflag', [AdminController::class, 'unflagProduct'])->name('admin.compliance.unflag');


Route::post('/admin/compliance/warn/{userId}', [AdminController::class, 'warnSeller'])->name('admin.compliance.warn');

// =========================
// SANDBOX PAYMENT (GCash, Maya, card — test payments, no real money)
// =========================

Route::get('/pay/{group}', [PaymentController::class, 'show'])->name('payment.show');
Route::post('/pay/{group}', [PaymentController::class, 'pay'])->name('payment.pay');
Route::post('/pay/{group}/fail', [PaymentController::class, 'fail'])->name('payment.fail');
Route::post('/pay/{group}/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');

// =========================
// BUYER VOUCHER WALLET
// =========================

Route::get('/buyer/vouchers', [BuyerVoucherController::class, 'index'])->name('buyer.vouchers');
Route::post('/vouchers/{id}/claim', [BuyerVoucherController::class, 'claim'])->whereNumber('id')->name('vouchers.claim');
Route::get('/messages/attachment/{id}', [MessageController::class, 'attachment'])->whereNumber('id')->name('messages.attachment');

// Seller earnings and payouts (S12)
Route::get('/seller/earnings', [SellerEarningsController::class, 'index'])->name('seller.earnings');
Route::post('/seller/payouts', [SellerEarningsController::class, 'requestPayout'])->name('seller.payouts.store');
Route::post('/seller/payouts/{id}/cancel', [SellerEarningsController::class, 'cancelPayout'])->whereNumber('id')->name('seller.payouts.cancel');

// Buyer: change email / mobile number with a code (B1)
Route::post('/buyer/profile/email', [ContactChangeController::class, 'requestEmail'])->middleware('throttle:6,10')->name('buyer.email.request');
Route::post('/buyer/profile/phone', [ContactChangeController::class, 'requestPhone'])->middleware('throttle:6,10')->name('buyer.phone.request');
Route::post('/buyer/profile/{kind}/verify', [ContactChangeController::class, 'verify'])->whereIn('kind', ['email', 'phone'])->name('buyer.contact.verify');
