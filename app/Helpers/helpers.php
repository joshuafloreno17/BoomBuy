<?php

use App\Models\Notification;

if (!function_exists('createNotification')) {

    function createNotification(
        int $userId,
        string $title,
        string $message,
        ?string $type = null,
        ?int $referenceId = null
    ): Notification {

        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'reference_id' => $referenceId,
            'read_at' => null,
        ]);
    }
}

if (!function_exists('notifyOrderSellers')) {

    // Notify every seller who has items in the given order.
    function notifyOrderSellers(
        int $orderId,
        string $title,
        string $message,
        string $type = 'order_status'
    ): void {

        $sellerIds = \Illuminate\Support\Facades\DB::table('order_items')
            ->where('order_id', $orderId)
            ->distinct()
            ->pluck('seller_id');

        foreach ($sellerIds as $sellerId) {
            createNotification((int) $sellerId, $title, $message, $type, $orderId);
        }
    }
}

if (!function_exists('productImageUrl')) {

    // Public URL for a stored product image (or an external one), null when there is none.
    function productImageUrl(?string $image): ?string
    {
        if (empty($image)) {
            return null;
        }

        return str_starts_with($image, 'http')
            ? $image
            : asset('storage/' . ltrim($image, '/'));
    }
}

if (!function_exists('notifyLogisticsUsers')) {

    function notifyLogisticsUsers(
        string $title,
        string $message,
        ?string $type = null,
        ?int $referenceId = null
    ): void {

        $logisticsUserIds = \Illuminate\Support\Facades\DB::table('users')
            ->where('role', 'logistics')
            ->where('status', 'Active')
            ->pluck('id');

        foreach ($logisticsUserIds as $logisticsUserId) {
            createNotification($logisticsUserId, $title, $message, $type, $referenceId);
        }
    }
}

if (!function_exists('notifyAllActiveRiders')) {

    function notifyAllActiveRiders(
        string $title,
        string $message,
        ?string $type = null,
        ?int $referenceId = null
    ): void {

        $riderUserIds = \Illuminate\Support\Facades\DB::table('users')
            ->join('rider_applications', 'rider_applications.user_id', '=', 'users.id')
            ->where('users.role', 'rider')
            ->where('users.status', 'Active')
            ->where('rider_applications.status', 'Approved')
            ->distinct()
            ->pluck('users.id');

        foreach ($riderUserIds as $riderUserId) {
            createNotification($riderUserId, $title, $message, $type, $referenceId);
        }
    }
}

if (!function_exists('calculateAge')) {

    function calculateAge(?string $birthdate): ?int
    {
        if (empty($birthdate)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($birthdate)->age;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('formatFullName')) {

    function formatFullName(?string $firstName, ?string $middleInitial, ?string $lastName): string
    {
        $middle = !empty($middleInitial) ? rtrim(trim($middleInitial), '.') . '. ' : '';

        return trim($firstName . ' ' . $middle . $lastName);
    }
}

/*
|--------------------------------------------------------------------------
| Session / auth helpers (moved here from routes/web.php)
|--------------------------------------------------------------------------
*/

if (!function_exists('requireUserRole')) {

    function requireUserRole($role)
    {
        $user = session()->get('user');

        if (!$user || ($user['role'] ?? '') !== $role) {
            return redirect()->route('login');
        }

        return $user;
    }
}

if (!function_exists('currentMessagingUser')) {

    // Identifies "who is logged in" for the messaging feature, whether
    // that's a buyer/seller/rider (session('user')) or the admin
    // (session('admin_logged_in'), backed by the admin@boombuy.com row).
    function currentMessagingUser()
    {
        $user = session()->get('user');

        if ($user) {
            return $user;
        }

        if (session()->get('admin_logged_in')) {

            $admin = \App\Models\User::where('email', 'admin@boombuy.com')->first();

            if ($admin) {
                return [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'role' => 'admin',
                ];
            }
        }

        return null;
    }
}

/*
|--------------------------------------------------------------------------
| Cart helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('parseCartKey')) {

    // Cart lines are keyed "productId:variationId" so that two different
    // variations (colors, sizes...) of the same product are tracked as
    // separate lines instead of merging into one. variationId is 0 when
    // the product has no variation selected.
    function parseCartKey($key)
    {
        $parts = explode(':', (string) $key);

        return [
            (int) ($parts[0] ?? 0),
            (int) ($parts[1] ?? 0),
        ];
    }
}

if (!function_exists('cartSubtotalForSeller')) {

    // Subtotal of the cart lines belonging to one seller (null = every
    // line) — used so a seller-specific voucher can only ever discount that
    // seller's own products, never a buyer's whole multi-seller cart.
    function cartSubtotalForSeller($cart, $sellerId = null)
    {
        $productIds = array_map(
            fn ($key) => parseCartKey($key)[0],
            array_keys($cart)
        );

        $databaseProducts = \App\Models\Product::whereIn('id', array_unique($productIds))
            ->get()
            ->keyBy('id');

        $subtotal = 0;

        foreach ($cart as $cartKey => $quantity) {

            [$productId, $variationId] = parseCartKey($cartKey);

            $product = $databaseProducts->get($productId);

            if (!$product) {
                continue;
            }

            if ($sellerId !== null && (int) $product->seller_id !== (int) $sellerId) {
                continue;
            }

            $unitPrice = (float) $product->price;

            if ($variationId) {

                $variation = \App\Models\ProductVariation::find($variationId);

                if ($variation) {
                    $unitPrice += (float) $variation->price_adjustment;
                }
            }

            $subtotal += $unitPrice * (int) $quantity;
        }

        return $subtotal;
    }
}

if (!function_exists('cartSummary')) {

    function cartSummary($cart)
    {
        return [
            'subtotal' => number_format(cartSubtotalForSeller($cart), 2),
            'total_items' => array_sum(array_map('intval', $cart)),
            'cart_count' => array_sum($cart),
        ];
    }
}

/*
|--------------------------------------------------------------------------
| Uploads
|--------------------------------------------------------------------------
*/

if (!function_exists('isValidProductImage')) {

    // Validates the uploaded file's actual content (not its client-supplied
    // name), so a script renamed to ".jpg" is rejected. Pair with store(),
    // which also names the saved file from the content.
    function isValidProductImage($file, int $maxKb = 5120): bool
    {
        if (!$file || !$file->isValid()) {
            return false;
        }

        return \Illuminate\Support\Facades\Validator::make(
            ['image' => $file],
            ['image' => 'image|mimes:jpg,jpeg,png,webp|max:' . $maxKb]
        )->passes();
    }
}

/*
|--------------------------------------------------------------------------
| OTP
|--------------------------------------------------------------------------
*/

if (!function_exists('generateAndSendOtp')) {

    function generateAndSendOtp($email, $name)
    {
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        session()->put('otp_code', $otpCode);
        session()->put('otp_email', $email);
        session()->put('otp_expires_at', now()->addMinutes(10));
        session()->put('otp_attempts', 0);

        try {
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\OtpMail($otpCode, $name));
            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }
}

if (!function_exists('otpAttemptsExceeded')) {

    // A 6-digit code can be guessed if tries are unlimited. After this many
    // wrong entries the code is thrown away and the user must request a new
    // one. Returns true when the caller should reject the attempt outright.
    function otpAttemptsExceeded(string $attemptsKey, array $codeKeys, int $max = 5): bool
    {
        $attempts = (int) session()->get($attemptsKey, 0) + 1;

        session()->put($attemptsKey, $attempts);

        if ($attempts > $max) {
            session()->forget($codeKeys);
            return true;
        }

        return false;
    }
}
