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
        $delivered = array_values(array_filter($myDeliveries, fn ($order) => ($order['status'] ?? '') === 'Delivered'));
        $totalCount = count($myDeliveries);

        // Cash on Delivery money this rider still has to hand in at the Sorting Center.
        $codHeld = app(\App\Services\SortingCenterService::class)->codHeldBy((int) $user['id']);

        return view(
            'pages.rider.dashboard',
            compact('user', 'myDeliveries', 'myDeliveryAssignments', 'inTransit', 'delivered', 'totalCount', 'codHeld')
        );
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
                ->orWhere(fn ($old) => $old->whereNull('delivery_rider_id')->where('rider_id', $riderId)))
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
            || (empty($delivery->delivery_rider_id) && (int) $delivery->rider_id === (int) $riderId);

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
            request()->file('delivery_proof')
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
        $deliveredCount = (int) ($statusCounts['Delivered'] ?? 0);
        $activeCount = (int) collect(['Assigned for Delivery', 'Out for Delivery'])
            ->sum(fn ($status) => $statusCounts[$status] ?? 0);

        return view(
            'pages.rider.profile',
            compact('user', 'totalDeliveries', 'deliveredCount', 'activeCount')
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
                            ->where('rider_id', $user['id']);
                    });
            })
            ->where('status', 'Delivered')
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->orderByDesc('delivered_at')
            ->get();

        $totalDeliveries = $completedDeliveries->count();
        $totalProfit = $totalDeliveries * $deliveryFee;

        $dailyProfit = $completedDeliveries
            ->groupBy(function ($order) {
                return Carbon::parse($order->delivered_at)->format('Y-m-d');
            })
            ->map(function ($orders, $day) use ($deliveryFee) {
                return [
                    'day' => $day,
                    'deliveries' => $orders->count(),
                    'profit' => $orders->count() * $deliveryFee,
                ];
            })
            ->sortKeysDesc()
            ->values();

        return view(
            'pages.rider.profit',
            compact(
                'user',
                'from',
                'to',
                'deliveryFee',
                'totalDeliveries',
                'totalProfit',
                'completedDeliveries',
                'dailyProfit'
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
                            ->where('rider_id', $user['id']);
                    });
            })
            ->where('status', 'Delivered')
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->orderByDesc('delivered_at')
            ->get();

        $history = $history->map(function ($order) {

            $order = (array) $order;

            $order['items'] = DB::table('order_items')
                ->where('order_id', $order['id'])
                ->get()
                ->map(fn ($item) => (array) $item)
                ->toArray();

            $order['buyer_name'] = $order['shipping_name'] ?? 'Buyer';
            $order['address'] = $order['shipping_address'] ?? 'N/A';

            return $order;

        });

        return view('pages.rider.delivery-history', compact('user', 'history', 'from', 'to'));
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

        'email' => $validated['email'],

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

    $otpSent = generateAndSendOtp(
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
