<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\User;
use App\Support\RegistrationRules;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * A buyer changing their email or mobile number (Buyer Modules B1): the
 * new one is checked (format, not used by anyone), a 6-digit code is sent,
 * and only the correct code within OTP_MINUTES makes the change — until
 * then the old one keeps working. The code for a new number is emailed
 * (BoomBuy has no SMS sender).
 */
class ContactChangeController extends Controller
{
    public const OTP_MINUTES = 5;

    public function requestEmail()
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $email = strtolower(trim((string) request('email')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withInput()->withErrors(['email' => 'Please enter a valid email address.']);
        }

        if (DB::table('users')->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return back()->withInput()->withErrors(['email' => 'That email is already in use.']);
        }

        $code = $this->start((int) $user['id'], 'email', $email);
        Mail::to($email)->send(new OtpMail($code, $user['name'] ?? 'there', self::OTP_MINUTES));

        return back()->with('success', 'We sent a 6-digit code to ' . $email . '. Enter it to finish the change. Until then you keep logging in with your current email.');
    }

    public function requestPhone()
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $phone = preg_replace('/\s+/', '', (string) request('phone'));

        if (!preg_match(RegistrationRules::PHONE_PATTERN, $phone)) {
            return back()->withInput()->withErrors(['phone' => 'Please enter a valid Philippine mobile number (09XXXXXXXXX or +639XXXXXXXXX).']);
        }

        if (RegistrationRules::phoneTaken($phone, (int) $user['id'])) {
            return back()->withInput()->withErrors(['phone' => 'That mobile number is already in use.']);
        }

        $code = $this->start((int) $user['id'], 'phone', RegistrationRules::normalizePhone($phone));
        $email = DB::table('users')->where('id', $user['id'])->value('email');
        Mail::to($email)->send(new OtpMail($code, $user['name'] ?? 'there', self::OTP_MINUTES));

        return back()->with('success', 'We sent a 6-digit code to your email to confirm the new number. Enter it to finish the change.');
    }

    /** Enter the code: kind = email | phone. */
    public function verify(string $kind)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        abort_unless(in_array($kind, ['email', 'phone'], true), 404);

        $key = $this->key((int) $user['id'], $kind);
        $pending = Cache::get($key);
        $code = trim((string) request('otp'));

        if (!$pending || now()->timestamp > $pending['expires']) {
            Cache::forget($key);

            return back()->withErrors(['otp' => 'That code has expired. Request a new one.']);
        }

        if (!hash_equals($pending['code'], $code)) {
            return back()->withErrors(['otp' => 'That code is not correct.']);
        }

        // Taken by someone else in the meantime?
        $taken = $kind === 'email'
            ? DB::table('users')->whereRaw('LOWER(email) = ?', [$pending['value']])->exists()
            : RegistrationRules::phoneTaken($pending['value'], (int) $user['id']);

        if ($taken) {
            Cache::forget($key);

            return back()->withErrors(['otp' => 'That ' . ($kind === 'email' ? 'email' : 'number') . ' is now used by another account. Choose another one.']);
        }

        User::where('id', $user['id'])->update([$kind => $pending['value'], 'updated_at' => now()]);
        Cache::forget($key);

        if ($kind === 'email') {
            session()->put('user', array_merge($user, ['email' => $pending['value']]));
        }

        return back()->with('success', $kind === 'email' ? 'Email changed. Log in with ' . $pending['value'] . ' from now on.' : 'Mobile number changed to ' . $pending['value'] . '.');
    }

    private function start(int $userId, string $kind, string $value): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->key($userId, $kind), ['value' => $value, 'code' => $code, 'expires' => now()->addMinutes(self::OTP_MINUTES)->timestamp], now()->addMinutes(self::OTP_MINUTES + 1));

        return $code;
    }

    private function key(int $userId, string $kind): string
    {
        return 'contact-change:' . $userId . ':' . $kind;
    }

    /** The code waiting for this user (for the tests and the "code sent" note). */
    public static function pending(int $userId, string $kind): ?array
    {
        return Cache::get('contact-change:' . $userId . ':' . $kind);
    }
}
