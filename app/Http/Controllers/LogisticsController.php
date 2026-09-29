<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Product;
use App\Models\RiderArea;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LogisticsController extends Controller
{
    public function showRegister()
    {
        return view('pages.logistics.register');
    }

    // Logistics Registration Submit
    public function register(Request $request)
    {
        $lastName = trim($request->input('last_name'));
        $firstName = trim($request->input('first_name'));
        $middleInitial = trim($request->input('middle_initial'));
        $sex = $request->input('sex');
        $birthdate = $request->input('birthdate');
        $email = strtolower(trim($request->input('email')));
        $password = $request->input('password');
        $passwordConfirmation = $request->input('password_confirmation');
        $phone = trim($request->input('phone'));
        $province = trim($request->input('province'));
        $cityMunicipality = trim($request->input('city_municipality'));
        $barangay = trim($request->input('barangay'));
        $streetAddress = trim($request->input('street_address'));
        $businessName = trim($request->input('business_name'));

        $name = formatFullName($firstName, $middleInitial, $lastName);
        $address = trim($streetAddress . ', ' . $barangay . ', ' . $cityMunicipality . ', ' . $province, ', ');

        // VALIDATION
        if (
            empty($lastName) ||
            empty($firstName) ||
            empty($sex) ||
            empty($birthdate) ||
            empty($email) ||
            empty($password) ||
            empty($passwordConfirmation) ||
            empty($phone) ||
            empty($province) ||
            empty($cityMunicipality) ||
            empty($barangay) ||
            empty($streetAddress) ||
            empty($businessName)
        ) {
            return back()
                ->withInput()
                ->with('error', 'Please complete all fields.');
        }

        if (!request('terms')) {
            return back()
                ->withInput()
                ->with('error', 'Please agree to the Terms & Conditions and Privacy Policy.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()
                ->withInput()
                ->with('error', 'Please enter a valid email address.');
        }

        if (strlen($password) < 8) {
            return back()
                ->withInput()
                ->with('error', 'Password must be at least 8 characters.');
        }

        if ($password !== $passwordConfirmation) {
            return back()
                ->withInput()
                ->with('error', 'Passwords do not match.');
        }

        if (User::where('email', $email)->exists()) {
            return back()
                ->withInput()
                ->with('error', 'Email is already registered.');
        }

        if (User::where('phone', $phone)->exists()) {
            return back()
                ->withInput()
                ->with('error', 'This phone number is already registered.');
        }

        $request->validate([
            'id_photo' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],
            'business_permit' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],
        ], [], [
            'id_photo' => 'valid ID',
            'business_permit' => 'business/DTI permit',
        ]);

        $folder = 'logistics-applications/' . (string) Str::uuid();

        $idPhotoPath = $request->file('id_photo')->store($folder, 'local');
        $businessPermitPath = $request->file('business_permit')->store($folder, 'local');

        session()->put('pending_registration', [
            'name' => $name,
            'last_name' => $lastName,
            'first_name' => $firstName,
            'middle_initial' => $middleInitial ?: null,
            'sex' => $sex,
            'birthdate' => $birthdate,
            'age' => calculateAge($birthdate),
            'email' => $email,
            'password' => Hash::make($password),
            'phone' => $phone,
            'address' => $address,
            'province' => $province,
            'city_municipality' => $cityMunicipality,
            'barangay' => $barangay,
            'street_address' => $streetAddress,
            'role' => 'logistics',
            'business_name' => $businessName,
            'id_photo' => $idPhotoPath,
            'business_permit' => $businessPermitPath,
        ]);

        if (!generateAndSendOtp($email, $name)) {
            return back()
                ->withInput()
                ->with('error', 'We could not send the verification code right now. Please try again in a moment.');
        }

        return redirect()
            ->route('otp.show')
            ->with('success', 'We sent a 6-digit code to your email.');
    }

    public function dashboard()
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $application = DB::table('logistics_applications')
            ->where('user_id', $user['id'])
            ->orderByDesc('created_at')
            ->first();

        $parcelsForSorting = DB::table('orders')
            ->whereIn('status', ['Picked Up', 'At Sorting Center'])
            ->count();

        $activeRiders = DB::table('users')
            ->join('rider_applications', 'rider_applications.user_id', '=', 'users.id')
            ->where('users.role', 'rider')
            ->where('users.status', 'Active')
            ->where('rider_applications.status', 'Approved')
            ->distinct('users.id')
            ->count('users.id');

        $deliveredToday = DB::table('orders')
            ->where('status', 'Delivered')
            ->whereDate('updated_at', now()->toDateString())
            ->count();

        return view(
            'pages.logistics.dashboard',
            compact('user', 'application', 'parcelsForSorting', 'activeRiders', 'deliveredToday')
        );
    }

    public function profile()
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $dbUser = User::find($user['id']);

        $application = DB::table('logistics_applications')
            ->where('user_id', $user['id'])
            ->orderByDesc('created_at')
            ->first();

        return view('pages.logistics.profile', compact('user', 'dbUser', 'application'));
    }

    public function updateProfile()
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $name = trim(request('name'));
        $phone = trim(request('phone'));
        $address = trim(request('address'));

        if (empty($name) || empty($phone) || empty($address)) {
            return back()
                ->withInput()
                ->with('error', 'Please complete all fields.');
        }

        if (
            User::where('phone', $phone)
                ->where('id', '!=', $user['id'])
                ->exists()
        ) {
            return back()
                ->withInput()
                ->with('error', 'This phone number is already registered.');
        }

        $dbUser = User::find($user['id']);
        $dbUser->name = $name;
        $dbUser->phone = $phone;
        $dbUser->address = $address;
        $dbUser->save();

        session()->put('user', array_merge($user, [
            'name' => $name,
        ]));

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePhoto(Request $request)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $request->validate([
            'profile_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $file = $request->file('profile_photo');

        // Extension from the real content, not the client filename (see BuyerController::updatePhoto).
        $filename = 'logistics_' . $user['id'] . '_' . time() . '.' . $file->extension();

        $file->storeAs('profile-photos', $filename, 'public');

        DB::table('users')
            ->where('id', $user['id'])
            ->update([
                'profile_photo' => $filename,
                'updated_at' => now(),
            ]);

        $user['profile_photo'] = $filename;
        session()->put('user', $user);

        return back()->with('success', 'Profile picture updated successfully.');
    }

    public function updatePassword()
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $current = request('current_password');
        $new = request('new_password');
        $confirm = request('new_password_confirmation');

        if (empty($current) || empty($new) || empty($confirm)) {
            return back()->with('error', 'Please complete all password fields.');
        }

        $dbUser = User::find($user['id']);

        if (!Hash::check($current, $dbUser->password)) {
            return back()->with('error', 'Current password is incorrect.');
        }

        if (strlen($new) < 8) {
            return back()->with('error', 'New password must be at least 8 characters.');
        }

        if ($new !== $confirm) {
            return back()->with('error', 'New passwords do not match.');
        }

        $dbUser->password = Hash::make($new);
        $dbUser->save();

        return back()->with('success', 'Password changed successfully.');
    }

    public function riders()
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $riderApplications = DB::table('rider_applications')
            ->join('users', 'users.id', '=', 'rider_applications.user_id')
            ->select('rider_applications.*', 'users.email as user_email', 'users.status as account_status')
            ->orderByDesc('rider_applications.created_at')
            ->get();

        $riderAreas = RiderArea::whereIn(
            'rider_id',
            $riderApplications->pluck('user_id')
        )->get()->groupBy('rider_id');

        return view('pages.logistics.riders', compact('user', 'riderApplications', 'riderAreas'));
    }

    public function storeRiderArea($riderId)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $province = trim(request('province'));
        $cityMunicipality = trim(request('city_municipality'));

        if (empty($province) || empty($cityMunicipality)) {
            return back()->with('error', 'Please select a province and city/municipality.');
        }

        $rider = DB::table('users')->where('id', $riderId)->where('role', 'rider')->first();

        if (!$rider) {
            return back()->with('error', 'Rider not found.');
        }

        RiderArea::firstOrCreate([
            'rider_id' => $riderId,
            'province' => $province,
            'city_municipality' => $cityMunicipality,
        ]);

        return back()->with('success', 'Area assigned to ' . $rider->name . '.');
    }

    public function deleteRiderArea($areaId)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        RiderArea::where('id', $areaId)->delete();

        return back()->with('success', 'Area removed.');
    }

    public function parcels()
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        // Picked up by a rider from the seller, en route to this Sorting Center
        $awaitingConfirmation = DB::table('orders')
            ->where('status', 'Picked Up')
            ->orderBy('updated_at')
            ->get();

        // Physically received at the Sorting Center, waiting to be assigned
        // to a rider for the final-mile delivery leg.
        $awaitingAssignment = DB::table('orders')
            ->where('status', 'At Sorting Center')
            ->orderBy('sorting_center_received_at')
            ->get();

        $activeRiders = DB::table('users')
            ->join('rider_applications', 'rider_applications.user_id', '=', 'users.id')
            ->where('users.role', 'rider')
            ->where('users.status', 'Active')
            ->where('rider_applications.status', 'Approved')
            ->select('users.id', 'users.name')
            ->distinct()
            ->get();

        $riderAreas = RiderArea::whereIn('rider_id', $activeRiders->pluck('id'))->get();

        // For each parcel awaiting assignment, suggest riders whose assigned
        // area name appears in the shipping address — a simple, honest match
        // since orders only store a single free-text shipping address string.
        $suggestedRidersByOrder = [];

        foreach ($awaitingAssignment as $order) {

            $matches = $riderAreas->filter(function ($area) use ($order) {
                return stripos($order->shipping_address, $area->city_municipality) !== false
                    || stripos($order->shipping_address, $area->province) !== false;
            })->pluck('rider_id')->unique();

            $suggestedRidersByOrder[$order->id] = $activeRiders->whereIn('id', $matches)->values();
        }

        $failedDeliveries = DB::table('orders')
            ->where('status', 'Delivery Failed')
            ->orderByDesc('delivery_failed_at')
            ->get();

        return view(
            'pages.logistics.parcels',
            compact('user', 'awaitingConfirmation', 'awaitingAssignment', 'activeRiders', 'suggestedRidersByOrder', 'failedDeliveries')
        );
    }

    public function confirmParcelReceived($id)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $order = DB::table('orders')->where('id', $id)->first();

        if (!$order) {
            return back()->with('error', 'Parcel not found.');
        }

        if ($order->status !== 'Picked Up') {
            return back()->with('error', 'This parcel is not awaiting Sorting Center confirmation.');
        }

        $updated = DB::table('orders')->where('id', $id)->where('status', 'Picked Up')->update([
            'status' => 'At Sorting Center',
            'sorting_center_received_at' => now(),
            'updated_at' => now(),
        ]);

        if (!$updated) {
            return back()->with('error', 'This parcel was just updated. Please refresh and try again.');
        }

        createNotification(
            (int) $order->buyer_id,
            'Parcel at Sorting Center',
            'Your order #' . $id . ' has arrived at the sorting facility and will be assigned to a rider for delivery shortly.',
            'order',
            (int) $id
        );

        notifyOrderSellers(
            (int) $id,
            'Parcel at Sorting Center',
            'Order #' . $id . ' has arrived at the Sorting Center and will be assigned to a rider for delivery.'
        );

        return back()->with('success', 'Parcel #' . $id . ' confirmed as received.');
    }

    public function assignParcel($id)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $riderId = request('rider_id');

        if (empty($riderId)) {
            return back()->with('error', 'Please select a rider to assign this parcel to.');
        }

        $order = DB::table('orders')->where('id', $id)->first();

        if (!$order) {
            return back()->with('error', 'Parcel not found.');
        }

        if ($order->status !== 'At Sorting Center') {
            return back()->with('error', 'This parcel is not awaiting assignment.');
        }

        $rider = DB::table('users')->where('id', $riderId)->where('role', 'rider')->where('status', 'Active')->first();

        if (!$rider) {
            return back()->with('error', 'Selected rider not found or no longer active.');
        }

        $updated = DB::table('orders')->where('id', $id)->where('status', 'At Sorting Center')->update([
            'delivery_rider_id' => $riderId,
            'status' => 'Assigned for Delivery',
            'updated_at' => now(),
        ]);

        if (!$updated) {
            return back()->with('error', 'This parcel was just updated. Please refresh and try again.');
        }

        createNotification(
            (int) $riderId,
            'New Delivery Assignment',
            'You have been assigned to deliver Order #' . $id . '. Please pick it up from the Sorting Center.',
            'delivery',
            (int) $id
        );

        createNotification(
            (int) $order->buyer_id,
            'Rider Assigned for Delivery',
            'Your order #' . $id . ' has been assigned to a rider and will be out for delivery soon.',
            'order',
            (int) $id
        );

        return back()->with('success', 'Parcel #' . $id . ' assigned to ' . $rider->name . '.');
    }

    public function rescheduleParcel($id)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $riderId = request('rider_id');

        if (empty($riderId)) {
            return back()->with('error', 'Please select a rider to reschedule this delivery to.');
        }

        $order = DB::table('orders')->where('id', $id)->first();

        if (!$order) {
            return back()->with('error', 'Parcel not found.');
        }

        if ($order->status !== 'Delivery Failed') {
            return back()->with('error', 'This parcel is not marked as a failed delivery.');
        }

        if (!empty($order->buyer_refused_at)) {
            return back()->with('error', 'The buyer refused this parcel, so it can\'t be re-delivered. Please return it to the seller.');
        }

        if ($order->delivery_attempts >= 2) {
            return back()->with('error', 'This parcel has reached the maximum delivery attempts. Please return it to the seller instead.');
        }

        $rider = DB::table('users')->where('id', $riderId)->where('role', 'rider')->where('status', 'Active')->first();

        if (!$rider) {
            return back()->with('error', 'Selected rider not found or no longer active.');
        }

        $updated = DB::table('orders')->where('id', $id)->where('status', 'Delivery Failed')->update([
            'delivery_rider_id' => $riderId,
            'status' => 'Assigned for Delivery',
            'updated_at' => now(),
        ]);

        if (!$updated) {
            return back()->with('error', 'This parcel was just updated. Please refresh and try again.');
        }

        createNotification(
            (int) $riderId,
            'Delivery Rescheduled',
            'Order #' . $id . ' has been rescheduled to you for another delivery attempt.',
            'delivery',
            (int) $id
        );

        createNotification(
            (int) $order->buyer_id,
            'Delivery Rescheduled',
            'We will attempt to deliver your order #' . $id . ' again shortly.',
            'order',
            (int) $id
        );

        return back()->with('success', 'Parcel #' . $id . ' rescheduled to ' . $rider->name . '.');
    }

    public function returnParcelToSeller($id)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $order = DB::table('orders')->where('id', $id)->first();

        if (!$order) {
            return back()->with('error', 'Parcel not found.');
        }

        if ($order->status !== 'Delivery Failed') {
            return back()->with('error', 'This parcel is not marked as a failed delivery.');
        }

        $updated = DB::table('orders')
            ->where('id', $id)
            ->where('status', 'Delivery Failed')
            ->update([
                'status' => 'Returned to Seller',
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return back()->with('error', 'This parcel was just updated. Please refresh and try again.');
        }

        $refused = !empty($order->buyer_refused_at);

        createNotification(
            (int) $order->buyer_id,
            'Order Returned to Seller',
            $refused
                ? 'Order #' . $id . ' was refused on delivery and has been returned to the seller.'
                : 'After repeated failed delivery attempts, order #' . $id . ' has been returned to the seller.',
            'order',
            (int) $id
        );

        $sellerIds = DB::table('order_items')
            ->where('order_id', $id)
            ->distinct()
            ->pluck('seller_id');

        foreach ($sellerIds as $sellerId) {
            createNotification(
                (int) $sellerId,
                'Order Returned to You',
                $refused
                    ? 'Order #' . $id . ' was refused by the buyer on delivery and has been returned to you. Please restock the items once received.'
                    : 'Order #' . $id . ' could not be delivered after repeated attempts and has been returned to you. Please restock the items once received.',
                'order',
                (int) $id
            );
        }

        return back()->with('success', 'Parcel #' . $id . ' has been returned to the seller.');
    }

    public function approveRider($id)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $application = DB::table('rider_applications')->where('id', $id)->first();

        if (!$application) {
            return back()->with('error', 'Application not found.');
        }

        DB::table('rider_applications')->where('id', $id)->update([
            'status' => 'Approved',
            'admin_remarks' => null,
            'reviewed_at' => now(),
            'reviewed_by' => $user['id'],
            'updated_at' => now(),
        ]);

        createNotification(
            (int) $application->user_id,
            'Rider Application Approved',
            'Congratulations! Your rider application has been approved. You can now access your rider account.',
            'rider',
            (int) $application->id
        );

        $applicant = DB::table('users')->where('id', $application->user_id)->first();

        if ($applicant) {
            // Emailed after the response, so the page doesn't wait on the mail server.
            \Illuminate\Support\defer(function () use ($applicant, $application) {
                try {
                    Mail::to($applicant->email)->send(
                        new \App\Mail\ApplicationStatusMail(
                            $application->full_name ?? $applicant->name,
                            'rider',
                            'Approved'
                        )
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            });
        }

        return back()->with('success', 'Rider application approved.');
    }

    public function rejectRider($id)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $remarks = trim((string) request('admin_remarks'));

        $application = DB::table('rider_applications')->where('id', $id)->first();

        if (!$application) {
            return back()->with('error', 'Application not found.');
        }

        DB::table('rider_applications')->where('id', $id)->update([
            'status' => 'Rejected',
            'admin_remarks' => $remarks !== '' ? $remarks : null,
            'reviewed_at' => now(),
            'reviewed_by' => $user['id'],
            'updated_at' => now(),
        ]);

        $message = $remarks !== ''
            ? 'Your rider application was rejected. Admin remarks: ' . $remarks
            : 'Your rider application was rejected. Please review your application and try again.';

        createNotification(
            $application->user_id,
            'Rider Application Rejected',
            $message,
            'rider',
            $application->id
        );

        $applicant = DB::table('users')->where('id', $application->user_id)->first();

        if ($applicant) {
            // Emailed after the response, so the page doesn't wait on the mail server.
            \Illuminate\Support\defer(function () use ($applicant, $application, $remarks) {
                try {
                    Mail::to($applicant->email)->send(
                        new \App\Mail\ApplicationStatusMail(
                            $application->full_name ?? $applicant->name,
                            'rider',
                            'Rejected',
                            $remarks !== '' ? $remarks : null
                        )
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            });
        }

        return back()->with('success', 'Rider application rejected.');
    }

    public function riderDocument($id, $field)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $allowedFields = ['national_id', 'drivers_license', 'profile_selfie', 'proof_of_address', 'or_cr'];

        if (!in_array($field, $allowedFields)) {
            abort(404);
        }

        $application = DB::table('rider_applications')->where('id', $id)->first();

        if (!$application || empty($application->$field)) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($application->$field)) {
            abort(404);
        }

        return Storage::disk('local')->response($application->$field);
    }

    public function toggleRiderStatus($userId)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $status = request('status');

        if (!in_array($status, ['Active', 'Deactivated'])) {
            return back()->with('error', 'Invalid account status.');
        }

        $rider = DB::table('users')->where('id', $userId)->where('role', 'rider')->first();

        if (!$rider) {
            return back()->with('error', 'Rider account not found.');
        }

        DB::table('users')->where('id', $userId)->update(['status' => $status]);

        createNotification(
            $rider->id,
            'Account Status Updated',
            "Your BoomBuy rider account status was changed to \"{$status}\" by the Logistics Center.",
            'account_status'
        );

        return back()->with('success', $rider->name . '\'s account has been set to ' . $status . '.');
    }

    public function notifications()
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $notifications = Notification::where(
            'user_id',
            $user['id']
        )
        ->orderByDesc('created_at')
        ->get();

        return view(
            'pages.logistics.notifications',
            compact('user', 'notifications')
        );
    }

    public function markNotificationRead($id)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        Notification::where('id', $id)
            ->where('user_id', $user['id'])
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return back();
    }
}
