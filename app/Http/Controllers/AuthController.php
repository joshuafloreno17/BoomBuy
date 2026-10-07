<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\LoginGate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('pages.login');
    }

    public function login()
    {
        $email = strtolower(trim(request('email')));
        $password = request('password');

        if (
            empty($email) ||
            empty($password)
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Please enter your email and password.'
                );
        }

        // The Admin account isn't a real "role" a user registers for — it's a
        // fixed set of credentials. Detect it here so Admin can use the same
        // login form as everyone else instead of a separate page.
        if ($email === 'admin@boombuy.com' && $password === 'admin123') {

            // Fresh session ID on privilege change (session fixation).
            session()->regenerate();
            session()->put('admin_logged_in', true);

            \App\Models\User::firstOrCreate(
                ['email' => 'admin@boombuy.com'],
                [
                    'name' => 'Admin',
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'admin',
                ]
            );

            return redirect()
                ->route('admin.dashboard')
                ->with('success', 'Welcome, Admin!');
        }

        $user = DB::table('users')
            ->where('email', $email)
            ->first();

        if (!$user) {
            return back()
                ->withInput()
                ->with('error', 'Invalid email or password.');
        }

        if (!Hash::check($password, $user->password)) {
            return back()
                ->withInput()
                ->with('error', 'Invalid email or password.');
        }

        // Suspended/deactivated accounts and unapproved applications.
        if ($reason = LoginGate::blockReason($user)) {
            return back()
                ->withInput()
                ->with('error', $reason);
        }

        LoginGate::startSession($user);

        // Remember Me — a 30-day token cookie that logs this browser back in
        // after the normal session expires (see RestoreRememberedLogin).
        if (request('remember')) {
            LoginGate::remember($user);
        }

        // Where a guest was before being asked to log in (cart, a product).
        // Taken out on every login so it can't linger for someone else.
        $afterLogin = session()->pull('after_login');

        switch ($user->role) {

            case 'seller':
                return redirect()
                    ->route('seller.dashboard')
                    ->with('success', 'Welcome to BoomBuy Seller!');

            case 'rider':
                return redirect()
                    ->route('rider.dashboard')
                    ->with('success', 'Welcome to BoomBuy Rider!');

            case 'buyer':
                // Back to where a guest was sent from (e.g. their cart),
                // else the buyer home. Only paths we stored ourselves.
                return redirect()
                    ->to($afterLogin ?: route('buyer.dashboard'))
                    ->with('success', 'Welcome to BoomBuy!');

            case 'logistics':
                return redirect()
                    ->route('logistics.dashboard')
                    ->with('success', 'Welcome to BoomBuy Logistics!');

            default:
                session()->forget('user');
                LoginGate::signedOut();

                return redirect('/login')
                    ->with('error', 'Invalid account role.');
        }
    }

    public function showRegisterChoose()
    {
        return view('register.choose');
    }

    public function submitRegisterRole()
    {
        $role = strtolower(trim(request('role')));

        if (empty($role)) {
            return back()
                ->withInput()
                ->with('error', 'Please select an account type.');
        }

        switch ($role) {

            case 'buyer':
                return redirect()->route('buyer.register');

            case 'seller':
                return redirect()->route('seller.register');

            case 'rider':
                return redirect()->route('rider.apply');

            default:
                return back()
                    ->withInput()
                    ->with('error', 'Invalid account role.');
        }
    }

    public function showForgotPassword()
    {
        return view('pages.forgot-password');
    }

    public function sendResetOtp()
    {
        $email = strtolower(trim(request('email')));

        if (empty($email)) {

            return back()
                ->withInput()
                ->with('error', 'Please enter your email address.');
        }

        $user = User::where('email', $email)->first();

        // Same answer whether or not the email has an account, so this form
        // can't be used to find out who is registered. With no account there
        // is simply no email, and no code will ever match.
        if (!$user) {

            session([
                'password_reset_user_id' => -1,
                'password_reset_otp' => bin2hex(random_bytes(16)),
                'password_reset_expires_at' => now()->addMinutes(10),
                'password_reset_attempts' => 0,
            ]);

            return redirect()
                ->route('password.reset')
                ->with(
                    'success',
                    'If that email has a BoomBuy account, we sent a 6-digit code to it. Enter it below along with your new password.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | SEND A ONE-TIME RESET CODE — proves whoever is resetting the password
        | actually controls this email address, instead of trusting the email
        | field alone. Uses its own session keys (not otp_code/otp_email/etc.)
        | so an in-progress registration OTP in the same browser can't collide
        | with a password-reset OTP.
        |--------------------------------------------------------------------------
        */

        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        session([
            'password_reset_user_id' => $user->id,
            'password_reset_otp' => $otpCode,
            'password_reset_expires_at' => now()->addMinutes(10),
            'password_reset_attempts' => 0,
        ]);

        try {
            Mail::to($user->email)->send(new \App\Mail\OtpMail($otpCode, $user->name));
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'We could not send the verification code right now. Please try again in a moment.');
        }

        return redirect()
            ->route('password.reset')
            ->with(
                'success',
                'If that email has a BoomBuy account, we sent a 6-digit code to it. Enter it below along with your new password.'
            );
    }

    public function showResetPassword()
    {
        if (!session()->has('password_reset_user_id')) {

            return redirect()
                ->route('password.request')
                ->with(
                    'error',
                    'Please enter your email first.'
                );
        }

        return view('pages.reset-password');
    }

    public function updatePassword()
    {
        $userId = session('password_reset_user_id');
        $storedOtp = session('password_reset_otp');
        $expiresAt = session('password_reset_expires_at');

        $inputOtp = trim((string) request('otp_code'));
        $password = request('password');
        $confirmation = request('password_confirmation');

        if (!$userId) {

            return redirect()
                ->route('password.request')
                ->with(
                    'error',
                    'Password reset session expired. Please try again.'
                );
        }

        if (empty($inputOtp)) {

            return back()
                ->withInput()
                ->with('error', 'Please enter the 6-digit code we emailed you.');
        }

        if (
            empty($storedOtp) ||
            !hash_equals((string) $storedOtp, $inputOtp) ||
            !$expiresAt ||
            now()->greaterThan($expiresAt)
        ) {

            if (otpAttemptsExceeded('password_reset_attempts', ['password_reset_otp', 'password_reset_expires_at'])) {
                return redirect()
                    ->route('password.request')
                    ->with('error', 'Too many wrong codes. Please request a new one.');
            }

            return back()
                ->withInput()
                ->with('error', 'Invalid or expired code. Please request a new one.');
        }

        if (empty($password) || empty($confirmation)) {

            return back()
                ->withInput()
                ->with('error', 'Please complete both password fields.');
        }

        if (strlen($password) < 8) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Password must be at least 8 characters.'
                );
        }

        if ($password !== $confirmation) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Passwords do not match.'
                );
        }

        $user = User::find($userId);

        if (!$user) {

            session()->forget(['password_reset_user_id', 'password_reset_otp', 'password_reset_expires_at']);

            return redirect()
                ->route('password.request')
                ->with(
                    'error',
                    'Account not found.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE PASSWORD IN DATABASE
        |--------------------------------------------------------------------------
        */

        $user->password = Hash::make($password);
        $user->save();

        // Signs out every "Remember me" browser that used the old password.
        LoginGate::passwordChanged($user);

        /*
        |--------------------------------------------------------------------------
        | CLEAR RESET SESSION
        |--------------------------------------------------------------------------
        */

        session()->forget(['password_reset_user_id', 'password_reset_otp', 'password_reset_expires_at']);

        return redirect()
            ->route('login')
            ->with(
                'success',
                'Password successfully changed. You can now login.'
            );
    }

    public function logout()
    {
        LoginGate::forget(session('user.id'));

        session()->forget('user');
        session()->regenerate(true);

        return redirect()
            ->route('login')
            ->with('success', 'You have been logged out.');
    }

    public function showVerifyOtp()
    {
        if (!session()->has('pending_registration')) {
            return redirect()->route('register');
        }

        return view('pages.verify-otp');
    }

    public function verifyOtp()
    {
        $pending = session()->get('pending_registration');

        if (!$pending) {
            return redirect()
                ->route('register')
                ->with(
                    'error',
                    'Your registration session expired. Please register again.'
                );
        }

        $inputCode = trim(request('otp_code'));

        $storedCode = session()->get('otp_code');
        $expiresAt = session()->get('otp_expires_at');

        /*
        |--------------------------------------------------------------------------
        | VERIFY OTP
        |--------------------------------------------------------------------------
        */

        if (
            empty($storedCode) ||
            !hash_equals((string) $storedCode, (string) $inputCode) ||
            !$expiresAt ||
            now()->greaterThan($expiresAt)
        ) {
            if (otpAttemptsExceeded('otp_attempts', ['otp_code', 'otp_expires_at'])) {
                return back()->with(
                    'error',
                    'Too many wrong codes. Please tap "Resend code" to get a new one.'
                );
            }

            return back()->with(
                'error',
                'Invalid or expired code.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CREATE USER ACCOUNT
        |--------------------------------------------------------------------------
        */
        $role = $pending['role']
            ?? (!empty($pending['vehicle_type']) ? 'rider' : 'buyer');

        // A seller/rider/logistics applicant who was rejected applies again
        // on their own account: it is updated and gets a new application.
        $reapplying = \App\Support\RejectedApplicant::find($pending['email'], $role);

        // The email may have been registered meanwhile (another tab, or a
        // double-clicked Verify button) — say so instead of crashing on the
        // unique email constraint.
        if (!$reapplying && User::where('email', $pending['email'])->exists()) {

            session()->forget(['pending_registration', 'otp_code', 'otp_email', 'otp_expires_at', 'otp_attempts']);

            return redirect()
                ->route('login')
                ->with('error', 'This email is already registered. Please log in instead.');
        }

        // The account and its application are created together or not at
        // all — a half-made account (no application) could never log in, and
        // its email would be taken so the person couldn't register again.
        DB::beginTransaction();

        try {

        $fields = [
            'name' => $pending['name'] ?? $pending['full_name'],
            'email' => $pending['email'],
            'password' => $pending['password'],
            'role' => $role,
            'phone' => $pending['phone'] ?? null,
            'address' => $pending['address'] ?? null,
            'is_verified' => true,
            'last_name' => $pending['last_name'] ?? null,
            'first_name' => $pending['first_name'] ?? null,
            'middle_initial' => $pending['middle_initial'] ?? null,
            'sex' => $pending['sex'] ?? null,
            'birthdate' => $pending['birthdate'] ?? null,
            'age' => $pending['age'] ?? null,
            'id_photo' => $pending['id_photo'] ?? null,
            'province' => $pending['province'] ?? null,
            'city_municipality' => $pending['city_municipality'] ?? null,
            'barangay' => $pending['barangay'] ?? null,
            'street_address' => $pending['street_address'] ?? null,
        ];

        if ($reapplying) {
            $reapplying->forceFill($fields)->save();
            $user = $reapplying;
        } else {
            $user = User::create($fields);
        }


        /*
        |--------------------------------------------------------------------------
        | SELLER APPLICATION
        |--------------------------------------------------------------------------
        */

        if ($role === 'seller') {

            DB::table('seller_applications')->insert([

                'user_id' => $user->id,

                'full_name' =>
                    $pending['name'] ?? $pending['full_name'],

                'business_name' =>
                    $pending['business_name'] ?? null,

                'phone' =>
                    $pending['phone'] ?? null,

                'address' =>
                    $pending['address'] ?? null,

                'national_id' =>
                    $pending['national_id'] ?? null,

                'business_permit' =>
                    $pending['business_permit'] ?? null,

                'business_category' =>
                    $pending['business_category'] ?? null,

                'status' =>
                    'Pending Verification',

                'admin_remarks' => null,

                'reviewed_at' => null,

                'reviewed_by' => null,

                'created_at' => now(),

                'updated_at' => now(),

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | RIDER APPLICATION
        |--------------------------------------------------------------------------
        */

        if ($role === 'rider') {

            DB::table('rider_applications')->insert([

                'user_id' => $user->id,

                'full_name' =>
                    $pending['full_name'] ?? $pending['name'],

                'phone' =>
                    $pending['phone'],

                'address' =>
                    $pending['address'],

                'vehicle_type' =>
                    $pending['vehicle_type'],

                'vehicle_model' =>
                    $pending['vehicle_model'],

                'plate_number' =>
                    $pending['plate_number'],

                'national_id' =>
                    $pending['national_id'],

                'drivers_license' =>
                    $pending['drivers_license'],

                'profile_selfie' =>
                    $pending['profile_selfie'],

                'proof_of_address' =>
                    $pending['proof_of_address'],

                'or_cr' =>
                    $pending['or_cr'],

                'status' =>
                    'Pending Verification',

                'admin_remarks' =>
                    null,

                'reviewed_at' =>
                    null,

                'reviewed_by' =>
                    null,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),

            ]);

            notifyLogisticsUsers(
                'New Rider Application',
                ($pending['full_name'] ?? $pending['name']) . ' has applied to become a rider and is awaiting review.',
                'rider'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | LOGISTICS APPLICATION
        |--------------------------------------------------------------------------
        */

        if ($role === 'logistics') {

            DB::table('logistics_applications')->insert([
                'user_id' => $user->id,
                'full_name' => $pending['name'] ?? null,
                'business_name' => $pending['business_name'] ?? null,
                'phone' => $pending['phone'] ?? null,
                'address' => $pending['address'] ?? null,
                'id_photo' => $pending['id_photo'] ?? null,
                'business_permit' => $pending['business_permit'] ?? null,
                'status' => 'Pending Verification',
                'admin_remarks' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }


        // Buyers don't wait for an admin: the record is kept (with their ID)
        // but approved on the spot, and they go straight into the shop.
        if ($role === 'buyer') {

            DB::table('buyer_applications')->insert([
                'user_id' => $user->id,
                'full_name' => $pending['name'] ?? $user->name,
                'phone' => $pending['phone'] ?? null,
                'address' => $pending['address'] ?? null,
                'id_photo' => $pending['id_photo'] ?? null,
                'status' => 'Approved',
                'admin_remarks' => null,
                'reviewed_at' => now(),
                'reviewed_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();
            report($e);

            return back()->with('error', 'We could not finish creating your account. Please try the code again in a moment.');
        }

        /*
        |--------------------------------------------------------------------------
        | CLEAR OTP SESSION
        |--------------------------------------------------------------------------
        */

        session()->forget([
            'pending_registration',
            'otp_code',
            'otp_email',
            'otp_expires_at'
        ]);


        /*
        |--------------------------------------------------------------------------
        | SELLER
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'seller') {

            return redirect()
                ->route('login')
                ->with(
                    'success',
                    'Your seller account has been created! We\'re verifying your documents — you\'ll be able to log in once approved.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | RIDER
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'rider') {

            return redirect()
                ->route('login')
                ->with(
                    'success',
                    'Your rider account has been created! Your application is now pending Admin verification. You can log in once approved.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | LOGISTICS
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'logistics') {

            return redirect()
                ->route('login')
                ->with(
                    'success',
                    'Your Logistics account has been created! We\'re verifying your documents — you\'ll be able to log in once approved.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | BUYER APPLICATION
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'buyer') {

            LoginGate::startSession($user);

            return redirect()
                ->route('buyer.dashboard')
                ->with('success', 'Welcome to BoomBuy, ' . $user->name . '! Your account is ready — happy shopping.');
        }
    }

    public function resendOtp()
    {
        $pending = session()->get('pending_registration');

        if (!$pending) {
            return redirect()
                ->route('register')
                ->with(
                    'error',
                    'Your registration session expired. Please register again.'
                );
        }

        $name = $pending['name']
            ?? $pending['full_name']
            ?? 'BoomBuy User';

        if (!generateAndSendOtp(
            $pending['email'],
            $name
        )) {

            return back()->with(
                'error',
                'We could not resend the verification code right now. Please try again in a moment.'
            );
        }

        return back()->with(
            'success',
            'A new code has been sent to your email.'
        );
    }
}
