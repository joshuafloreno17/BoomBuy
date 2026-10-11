<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Requests\ProfilePhotoRequest;
use App\Services\ProfilePhotoService;
use App\Support\ApiToken;
use App\Support\CodPolicy;
use App\Support\LoginGate;
use App\Support\MobileAccess;
use App\Support\SellerShop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Signing in to the app, and the account every mobile role shares:
 * who am I, notifications, profile, password, photo.
 */
class AuthController extends MobileController
{
    /**
     * Buyers, sellers and riders get a token. Any other account (logistics,
     * the admin) gets 403 with code "desktop_only" and a message saying to
     * use the website on a computer. The password is checked first, so the
     * reply never tells a stranger what kind of account an email has.
     */
    public function login(Request $request): JsonResponse
    {
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');

        if ($email === '' || $password === '') {
            return $this->fail('Please enter your email and password.');
        }

        $user = DB::table('users')->where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return $this->fail('Invalid email or password.', 401);
        }

        if (!MobileAccess::allows($user->role)) {
            return $this->fail(MobileAccess::DESKTOP_ONLY_MESSAGE, 403, ['code' => 'desktop_only']);
        }

        // Suspended/deactivated accounts and unapproved applications — the website's rules.
        if ($reason = LoginGate::blockReason($user)) {
            return $this->fail($reason, 403, ['code' => 'blocked']);
        }

        $token = ApiToken::issue($user, (string) $request->input('device_name', 'BoomBuy app'));

        return response()->json(['token' => $token, 'user' => $this->userJson($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        ApiToken::revoke($request->bearerToken());

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->user($request);

        return response()->json([
            'user' => $this->userJson($user),
            'unread_notifications' => DB::table('notifications')->where('user_id', $user->id)->whereNull('read_at')->count(),
            'unread_messages' => DB::table('messages')->where('recipient_id', $user->id)->whereNull('read_at')->count(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $name = trim((string) $request->input('name'));
        $phone = trim((string) $request->input('phone'));
        $address = trim((string) $request->input('address'));

        if ($name === '' || $phone === '' || $address === '') {
            return $this->fail('Please complete all fields.');
        }

        if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
            return $this->fail('Please enter a valid phone number (numbers only).');
        }

        if (DB::table('users')->where('phone', $phone)->where('id', '!=', $user->id)->exists()) {
            return $this->fail('This phone number is already registered.');
        }

        // The town and province decide which Sorting Center serves this account.
        $town = \App\Support\PhLocations::locate($address);

        if (!$town) {
            return $this->fail('Include your city/municipality and province in the address, e.g. "123 Rizal St., Poblacion, Santa Cruz, Laguna".');
        }

        DB::table('users')->where('id', $user->id)->update([
            'name' => mb_substr($name, 0, 255),
            'phone' => $phone,
            'address' => mb_substr($address, 0, 500),
            'province' => $town['province'],
            'city_municipality' => $town['city'] ?? $user->city_municipality,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $this->userJson(DB::table('users')->where('id', $user->id)->first()),
        ]);
    }

    /** A new password signs out every browser and phone; this phone gets a fresh token. */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $current = (string) $request->input('current_password');
        $new = (string) $request->input('new_password');
        $confirm = (string) $request->input('new_password_confirmation');

        if ($current === '' || $new === '' || $confirm === '') {
            return $this->fail('Please complete all password fields.');
        }

        if (!Hash::check($current, $user->password)) {
            return $this->fail('Current password is incorrect.');
        }

        if (strlen($new) < 8) {
            return $this->fail('New password must be at least 8 characters.');
        }

        if ($new !== $confirm) {
            return $this->fail('New passwords do not match.');
        }

        DB::table('users')->where('id', $user->id)->update(['password' => Hash::make($new), 'updated_at' => now()]);

        LoginGate::passwordChanged($user);

        return response()->json([
            'message' => 'Password changed successfully.',
            'token' => ApiToken::issue($user, 'BoomBuy app'),
        ]);
    }

    public function updatePhoto(ProfilePhotoRequest $request, ProfilePhotoService $photos): JsonResponse
    {
        $photos->store($this->sessionUser($request), $request->file('profile_photo'));

        return response()->json([
            'message' => 'Profile picture updated successfully.',
            'user' => $this->userJson(DB::table('users')->where('id', $this->user($request)->id)->first()),
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $user = $this->user($request);

        return response()->json([
            'notifications' => DB::table('notifications')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->map(fn ($n) => self::notificationJson($n)),
        ]);
    }

    public function readNotification(Request $request, int $id): JsonResponse
    {
        DB::table('notifications')->where('id', $id)->where('user_id', $this->user($request)->id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'Marked as read.']);
    }

    public function readAllNotifications(Request $request): JsonResponse
    {
        DB::table('notifications')->where('user_id', $this->user($request)->id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    private function userJson(object $user): array
    {
        $json = [
            'id' => (int) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'phone' => $user->phone,
            'address' => $user->address,
            'photo' => self::photoUrl($user->profile_photo ?? null),
            'member_since' => self::iso($user->created_at),
        ];

        if ($user->role === 'buyer') {
            $cod = CodPolicy::status((int) $user->id);
            $json['cod'] = [
                'blocked' => $cod['blocked'],
                'strikes' => $cod['strikes'],
                'limit' => $cod['limit'],
                'available_at' => $cod['available_at']?->toIso8601String(),
            ];
            $json['missing_info'] = \App\Support\BuyerProfile::missing((int) $user->id);
        }

        if ($user->role === 'seller') {
            $shop = SellerShop::one((int) $user->id, false);
            $json['shop_name'] = $shop['name'] ?? null;
        }

        if ($user->role === 'rider') {
            $json['area'] = trim(($user->city_municipality ? $user->city_municipality . ', ' : '') . (string) $user->province, ', ');
        }

        return $json;
    }
}
