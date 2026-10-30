<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\DeliveryService;
use App\Exceptions\ActionFailed;
use App\Http\Requests\ProfilePhotoRequest;
use App\Services\ProfilePhotoService;
use App\Http\Requests\RiderApplicationRequest;
use App\Services\RiderAccountService;
use App\Support\RegistrationRules;

class RiderController extends Controller
{
    // Kept here for the rider pages; the rules live in DeliveryService.
    public const REFUSED_REASON = DeliveryService::REFUSED_REASON;

    public const FAILURE_REASONS = DeliveryService::FAILURE_REASONS;

    public function updatePhoto(ProfilePhotoRequest $request, ProfilePhotoService $photos)
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        session()->put('user', $photos->store($user, $request->file('profile_photo')));

        return redirect()
            ->route('rider.profile')
            ->with('success', 'Profile picture updated successfully.');
    }

    /** Phone and home address. The town also decides which Sorting Center looks after the rider. */
    public function updateProfile()
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        $phone = trim((string) request('phone'));
        $address = trim((string) request('address'));

        if ($phone === '' || $address === '') {
            return back()->withInput()->with('error', 'Please enter your mobile number and address.');
        }

        if (!preg_match(\App\Support\BuyerProfile::PHONE_PATTERN, $phone)) {
            return back()->withInput()->with('error', 'Please enter a valid mobile number (numbers only).');
        }

        if (\App\Models\User::where('phone', $phone)->where('id', '!=', $user['id'])->exists()) {
            return back()->withInput()->with('error', 'This phone number is already registered.');
        }

        $town = \App\Support\PhLocations::locate($address);

        if (!$town) {
            return back()->withInput()->with('error', 'Pick your province and city/municipality for the address.');
        }

        \App\Models\User::where('id', $user['id'])->update([
            'phone' => $phone,
            'address' => mb_substr($address, 0, 255),
            'province' => $town['province'],
            'city_municipality' => $town['city'],
            'updated_at' => now(),
        ]);

        // The rider application keeps the contact details logistics staff see.
        DB::table('rider_applications')->where('user_id', $user['id'])->update(['phone' => $phone, 'address' => mb_substr($address, 0, 255), 'updated_at' => now()]);

        return back()->with('success', 'Your details were updated.');
    }

    public function updatePassword()
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        $current = request('current_password');
        $new = request('new_password');
        $confirm = request('new_password_confirmation');

        if (empty($current) || empty($new) || empty($confirm)) {
            return back()->with('error', 'Please complete all password fields.');
        }

        $dbUser = \App\Models\User::find($user['id']);

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

        \App\Support\LoginGate::passwordChanged($dbUser, true);

        return back()->with('success', 'Password changed successfully.');
    }

    public function dashboard()
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        // Every parcel a Sorting Center handed this rider to deliver.
        $myDeliveries = $this->riderOrders((int) $user['id']);

        $myDeliveryAssignments = array_values(array_filter(
            $myDeliveries,
            fn ($order) => in_array($order['status'] ?? '', ['Assigned for Delivery', 'Out for Delivery'], true)
        ));

        $inTransit = array_values(array_filter($myDeliveries, fn ($order) => ($order['status'] ?? '') === 'Out for Delivery'));
        $delivered = array_values(array_filter($myDeliveries, fn ($order) => in_array($order['status'] ?? '', \App\Support\OrderStatus::DONE, true)));
        $totalCount = count($myDeliveries);

        // Cash on Delivery money this rider still has to hand in at the Sorting Center.
        $codHeld = app(\App\Services\SortingCenterService::class)->codHeldBy((int) $user['id']);

        // Items for pickup: new requests nearby + pickups this rider accepted.
        $pickups = app(\App\Services\PickupService::class);
        $pickupCounts = [
            'available' => $pickups->available((int) $user['id'])->count(),
            'mine' => $pickups->mine((int) $user['id'])->count(),
        ];

        return view(
            'pages.rider.dashboard',
            compact('user', 'myDeliveries', 'myDeliveryAssignments', 'inTransit', 'delivered', 'totalCount', 'codHeld', 'pickupCounts')
        );
    }

    /** Items for pickup: open requests in the rider's areas, and the ones they accepted. */
    public function pickups(\App\Services\PickupService $pickups)
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        $available = $pickups->available((int) $user['id']);
        $mine = $pickups->mine((int) $user['id']);
        $hasAreas = DB::table('rider_areas')->where('rider_id', $user['id'])->exists();

        return view('pages.rider.pickups', compact('user', 'available', 'mine', 'hasAreas'));
    }

    public function acceptPickup($id, \App\Services\PickupService $pickups)
    {
        return $this->riderAction(fn (array $user) => $pickups->accept((int) $user['id'], (int) $id));
    }

    public function confirmPickup($id, \App\Services\PickupService $pickups)
    {
        return $this->riderAction(fn (array $user) => $pickups->pickedUp((int) $user['id'], (int) $id));
    }

    public function deliveries()
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        $deliveries = $this->riderOrders((int) $user['id']);

        return view('pages.rider.deliveries', compact('user', 'deliveries'));
    }

    /**
     * The orders a Sorting Center assigned this rider to deliver, newest
     * first, shaped for the rider pages. (Orders from before the Sorting
     * Center hop existed were tracked on rider_id.)
     */
    private function riderOrders(int $riderId): array
    {
        $orders = DB::table('orders')
            ->where(fn ($q) => $q->where('delivery_rider_id', $riderId)
                ->orWhere(fn ($old) => $old->whereNull('delivery_rider_id')->whereNull('pickup_date')->where('rider_id', $riderId)))
            ->orderByDesc('created_at')
            ->get();

        $items = DB::table('order_items')->whereIn('order_id', $orders->pluck('id'))->get()->groupBy('order_id');

        return $orders->map(function ($order) use ($items) {
            $order = (array) $order;
            $order['items'] = $items->get($order['id'], collect())->map(fn ($item) => (array) $item)->toArray();
            $order['total'] = (float) $order['total_amount'];
            $order['buyer_name'] = $order['shipping_name'];
            $order['address'] = $order['shipping_address'];
            $order['phone'] = $order['shipping_phone'];
            $order['payment'] = $order['payment_method'];

            return $order;
        })->toArray();
    }

    public function deliveryDetails($id)
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        $riderId = $user['id'] ?? null;

        // GET ORDER FROM DATABASE
        $delivery = DB::table('orders')
            ->where('id', $id)
            ->first();

        if (!$delivery) {
            abort(404);
        }

        // Only the rider the Sorting Center handed it to (rider_id on orders
        // from before the Sorting Center hop existed).
        $isAssigned = (int) ($delivery->delivery_rider_id ?? 0) === (int) $riderId
            || (empty($delivery->delivery_rider_id) && empty($delivery->pickup_date) && (int) $delivery->rider_id === (int) $riderId);

        if (!$isAssigned) {
            abort(404);
        }

        // Get order items
        $items = DB::table('order_items')
            ->where('order_id', $delivery->id)
            ->get();

        // Convert order to array
        $delivery = (array) $delivery;

        // Add data needed by the Blade
        $delivery['items'] = $items->map(function ($item) {
            return (array) $item;
        })->toArray();

        $delivery['total'] = (float) $delivery['total_amount'];

        $delivery['buyer_name'] = $delivery['shipping_name'];

        $delivery['address'] = $delivery['shipping_address'];

        $delivery['phone'] = $delivery['shipping_phone'];

        $delivery['payment'] = $delivery['payment_method'];

        // The page shows "Order Date"; the row only has created_at.
        $delivery['date'] = !empty($delivery['created_at'])
            ? \Illuminate\Support\Carbon::parse($delivery['created_at'])->format('M j, Y · g:i A')
            : null;

        return view(
            'pages.rider.delivery-details',
            compact('user', 'delivery')
        );
    }

    public function updateStatus($id, DeliveryService $deliveries)
    {
        return $this->riderAction(fn (array $user) => $deliveries->updateStatus(
            (int) $user['id'],
            (int) $id,
            (string) request('status'),
            (string) request('failure_code'),
            (string) request('failure_reason'),
            request()->file('delivery_proof'),
            request('otp'),
            request('cod_amount'),
            is_numeric(request('lat')) && is_numeric(request('lng')) && abs((float) request('lat')) <= 90 && abs((float) request('lng')) <= 180
                ? [round((float) request('lat'), 7), round((float) request('lng'), 7)]
                : null
        ));
    }

    /** Runs a rider action and flashes its message (or the rule it broke). */
    private function riderAction(callable $action)
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        try {
            return back()->with('success', $action($user));
        } catch (ActionFailed $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function profile()
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        // Delivery statistics: every order a Sorting Center handed this rider.
        $statusCounts = DB::table('orders')
            ->where('delivery_rider_id', $user['id'])
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalDeliveries = (int) $statusCounts->sum();
        $deliveredCount = (int) (($statusCounts['Delivered'] ?? 0) + ($statusCounts['Completed'] ?? 0));
        $activeCount = (int) collect(['Assigned for Delivery', 'Out for Delivery'])
            ->sum(fn ($status) => $statusCounts[$status] ?? 0);

        $dbUser = \App\Models\User::find($user['id']);

        // License, vehicle, documents and the company (R1).
        $riderRecord = DB::table('rider_applications')->where('user_id', $user['id'])->latest('id')->first();
        $companyName = $riderRecord && $riderRecord->created_by
            ? DB::table('logistics_applications')->where('user_id', $riderRecord->created_by)->latest('id')->value('business_name')
            : null;

        return view(
            'pages.rider.profile',
            compact('user', 'totalDeliveries', 'deliveredCount', 'activeCount', 'dbUser', 'riderRecord', 'companyName')
        );
    }

    public function profit()
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        $from = request('from') ?: now()->subDays(30)->format('Y-m-d');
        $to = request('to') ?: now()->format('Y-m-d');

        $deliveryFee = (float) PlatformSetting::get('delivery_fee', '50');

        // Credit whoever actually completed the final-mile leg: if the Sorting
        // Center assigned a (possibly different) delivery_rider_id, that rider
        // earns the fee, not the one who only handled the seller pickup leg.
        $completedDeliveries = DB::table('orders')
            ->where(function ($query) use ($user) {
                $query->where('delivery_rider_id', $user['id'])
                    ->orWhere(function ($q) use ($user) {
                        $q->whereNull('delivery_rider_id')
                            ->whereNull('pickup_date')
                            ->where('rider_id', $user['id']);
                    });
            })
            ->whereIn('status', \App\Support\OrderStatus::DONE)
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->orderByDesc('delivered_at')
            ->get();

        // Each delivery earns the fee saved when it was delivered (today's
        // fee only for one that somehow has none).
        $earned = fn ($order) => $order->rider_fee !== null ? (float) $order->rider_fee : $deliveryFee;

        $totalDeliveries = $completedDeliveries->count();
        $totalProfit = (float) $completedDeliveries->sum($earned);

        $dailyProfit = $completedDeliveries
            ->groupBy(function ($order) {
                return Carbon::parse($order->delivered_at)->format('Y-m-d');
            })
            ->map(function ($orders, $day) use ($earned) {
                return [
                    'day' => $day,
                    'deliveries' => $orders->count(),
                    'profit' => (float) $orders->sum($earned),
                ];
            })
            ->sortKeysDesc()
            ->values();

        // The chart gets every day of the range, ₱0 included (oldest first);
        // ranges over ~3 months keep only the days with deliveries.
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();
        $chartDays = $dailyProfit->reverse()->values();

        if ($end->gte($start) && $start->diffInDays($end) <= 92) {
            $byDay = $dailyProfit->keyBy('day');
            $chartDays = collect();

            for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                $chartDays->push($byDay[$day->toDateString()] ?? ['day' => $day->toDateString(), 'deliveries' => 0, 'profit' => 0.0]);
            }
        }

        // Pay per job (A33) and the COD money to remit (A24).
        $payToday = \App\Support\RiderJobs::pay((int) $user['id'], now()->startOfDay(), now()->endOfDay());
        $payWeek = \App\Support\RiderJobs::pay((int) $user['id'], now()->startOfWeek(), now()->endOfWeek());
        $cod = app(\App\Services\CodRemittanceService::class)->balance((int) $user['id']);

        return view(
            'pages.rider.profit',
            compact(
                'payToday',
                'payWeek',
                'cod',
                'user',
                'from',
                'to',
                'deliveryFee',
                'totalDeliveries',
                'totalProfit',
                'completedDeliveries',
                'dailyProfit',
                'chartDays'
            )
        );
    }

    public function deliveryHistory()
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        $from = request('from') ?: now()->subDays(30)->format('Y-m-d');
        $to = request('to') ?: now()->format('Y-m-d');

        // Credit whoever actually completed the final-mile leg, same as the
        // Profit page: if the Sorting Center assigned a (possibly different)
        // delivery_rider_id, that delivery belongs in this rider's history too.
        $history = DB::table('orders')
            ->where(function ($query) use ($user) {
                $query->where('delivery_rider_id', $user['id'])
                    ->orWhere(function ($q) use ($user) {
                        $q->whereNull('delivery_rider_id')
                            ->whereNull('pickup_date')
                            ->where('rider_id', $user['id']);
                    });
            })
            ->whereIn('status', \App\Support\OrderStatus::DONE)
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->orderByDesc('delivered_at')
            ->get();

        $itemsByOrder = DB::table('order_items')->whereIn('order_id', $history->pluck('id'))->get()->groupBy('order_id');

        $history = $history->map(function ($order) use ($itemsByOrder) {

            $order = (array) $order;

            $order['items'] = $itemsByOrder->get($order['id'], collect())
                ->map(fn ($item) => (array) $item)
                ->toArray();

            $order['buyer_name'] = $order['shipping_name'] ?? 'Buyer';
            $order['address'] = $order['shipping_address'] ?? 'N/A';

            return $order;

        });

        // Every job once — pickups, deliveries and failed attempts — with its pay (R7).
        $riderId = (int) $user['id'];
        $range = [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()];
        $jobs = collect()
            ->merge(DB::table('orders')->where('rider_id', $riderId)->whereNotNull('pickup_fee')->whereBetween('picked_up_at', $range)->get()
                ->map(fn ($o) => ['at' => $o->picked_up_at, 'order_id' => (int) $o->id, 'kind' => 'Pickup', 'result' => 'Picked up', 'pay' => (float) $o->pickup_fee]))
            ->merge(DB::table('orders')->where('delivery_rider_id', $riderId)->whereNotNull('delivered_at')->whereBetween('delivered_at', $range)->get()
                ->map(fn ($o) => ['at' => $o->delivered_at, 'order_id' => (int) $o->id, 'kind' => 'Delivery', 'result' => 'Delivered', 'pay' => (float) ($o->rider_fee ?? 0)]))
            ->merge(DB::table('order_events')->join('orders', 'orders.id', '=', 'order_events.order_id')
                ->where('orders.delivery_rider_id', $riderId)->where('order_events.status', 'Delivery Failed')->where('order_events.title', 'like', 'Delivery attempt%')
                ->whereBetween('order_events.created_at', $range)->get(['order_events.created_at', 'order_events.order_id', 'order_events.detail'])
                ->map(fn ($e) => ['at' => $e->created_at, 'order_id' => (int) $e->order_id, 'kind' => 'Delivery', 'result' => 'Failed: ' . $e->detail, 'pay' => 0.0]))
            ->map(fn ($job) => $job + ['tracking_number' => \App\Support\Waybill::number($job['order_id'])])
            ->sortByDesc('at')
            ->values();

        return view('pages.rider.delivery-history', compact('user', 'history', 'from', 'to', 'jobs'));
    }

    /** A rider added by a logistics company opens the emailed link to set their password. */
    public function showActivation(string $token, RiderAccountService $accounts)
    {
        $application = $accounts->findByToken($token);

        return view('pages.rider.activate', [
            'token' => $token,
            'problem' => is_string($application) ? $application : null,
            'riderName' => is_object($application) ? $application->full_name : null,
        ]);
    }

    public function activate(Request $request, string $token, RiderAccountService $accounts)
    {
        $application = $accounts->findByToken($token);

        if (is_string($application)) {
            return redirect()->route('rider.activate', $token);
        }

        $request->validate([
            'password' => RegistrationRules::password(),
        ], RegistrationRules::messages());

        $accounts->activate($application, $request->input('password'));

        // The emailed link may be opened in a browser; riders log in in the app.
        return redirect()->route(\App\Support\MobileAccess::riderBlockedHere($request) ? 'app.download' : 'login')->with('success', 'Your rider account is active. You can now log in with your email and new password.');
    }

    public function showApply()
    {
        $application = null;
        $checkedEmail = trim((string) request('email'));

        // Pending/Rejected riders can't log in yet, so there's no session to
        // read their status from — let them look it up by the email they
        // applied with instead of always showing a blank form.
        if (!empty($checkedEmail)) {

            $applicantUser = DB::table('users')
                ->where('email', strtolower($checkedEmail))
                ->where('role', 'rider')
                ->first();

            if ($applicantUser) {

                $application = DB::table('rider_applications')
                    ->where('user_id', $applicantUser->id)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->first();
            }

            if (!$application) {
                session()->flash('error', 'No rider application was found for that email address.');
            }
        }

        return view('pages.rider.apply', compact('application', 'checkedEmail'));
    }

    public function submitApply(RiderApplicationRequest $request)
    {
        $validated = $request->validated();

        /*
        |--------------------------------------------------------------------------
        | STORE RIDER REGISTRATION TEMPORARILY
        |--------------------------------------------------------------------------
        */

        $folder = 'rider-applications/' . Str::uuid();

        $nationalIdPath = $request
            ->file('national_id')
            ->store($folder, 'local');

        $driversLicensePath = $request
            ->file('drivers_license')
            ->store($folder, 'local');

        $selfiePath = $request
            ->file('profile_selfie')
            ->store($folder, 'local');

        $proofOfAddressPath = $request
            ->file('proof_of_address')
            ->store($folder, 'local');

        $orCrPath = $request
            ->file('or_cr')
            ->store($folder, 'local');

        $riderFullName = formatFullName(
            $validated['first_name'],
            $validated['middle_initial'] ?? null,
            $validated['last_name']
        );

        $riderAddress = $validated['street_address'] . ', '
            . $validated['barangay'] . ', '
            . $validated['city_municipality'] . ', '
            . $validated['province'];

        $riderAge = calculateAge($validated['birthdate']);


        /*
        |--------------------------------------------------------------------------
        | SAVE PENDING RIDER DATA IN SESSION
        |--------------------------------------------------------------------------
        */

        session()->put('pending_registration', [

        'full_name' => $riderFullName,

        // Compatible sa existing OTP verification
        'name' => $riderFullName,

        'last_name' => $validated['last_name'],
        'first_name' => $validated['first_name'],
        'middle_initial' => $validated['middle_initial'] ?? null,
        'sex' => $validated['sex'],
        'birthdate' => $validated['birthdate'],
        'age' => $riderAge,

        'phone' => $validated['phone'],
        'address' => $riderAddress,
        'province' => $validated['province'],
        'city_municipality' => $validated['city_municipality'],
        'barangay' => $validated['barangay'],
        'street_address' => $validated['street_address'],

        'vehicle_type' => $validated['vehicle_type'],
        'vehicle_model' => $validated['vehicle_model'],
        'plate_number' => $validated['plate_number'],
        'drivers_license_no' => $validated['drivers_license_no'],
        'license_expiry' => $validated['license_expiry'],

        'email' => strtolower(trim($validated['email'])),

        'password' => Hash::make(
            $validated['password']
        ),

        'national_id' => $nationalIdPath,
        'drivers_license' => $driversLicensePath,
        'profile_selfie' => $selfiePath,
        'proof_of_address' => $proofOfAddressPath,
        'or_cr' => $orCrPath,

        // IMPORTANT
        'role' => 'rider',

        'application_folder' => $folder,

    ]);


    /*
    |--------------------------------------------------------------------------
    | SEND OTP
    |--------------------------------------------------------------------------
    */

    $otpSent = sendRegistrationOtp(
        $validated['email'],
        $riderFullName
    );


    if (!$otpSent) {

        session()->forget('pending_registration');

        return back()
            ->withInput()
            ->with(
                'error',
                'Unable to send OTP. Please check your email and try again.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | REDIRECT TO OTP PAGE
    |--------------------------------------------------------------------------
    */

    return redirect()
        ->route('otp.verify')
        ->with(
            'success',
            'OTP sent successfully! Please check your email.'
        );
    }

    public function notifications()
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        $notifications = Notification::where(
            'user_id',
            $user['id']
        )
        ->orderByDesc('created_at')
        ->paginate(20);

        $unreadCount = Notification::where('user_id', $user['id'])->whereNull('read_at')->count();

        return view(
            'pages.rider.notifications',
            compact('user', 'notifications', 'unreadCount')
        );
    }

    public function markNotificationRead($id)
    {
        $user = requireUserRole('rider');

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
