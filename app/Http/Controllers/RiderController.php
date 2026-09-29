<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RiderController extends Controller
{
    public const REFUSED_REASON = 'Buyer refused the parcel';

    public const FAILURE_REASONS = [
        self::REFUSED_REASON,
        'Buyer not available',
        'Buyer unreachable by phone',
        'Wrong or incomplete address',
        'Other',
    ];

    public function updatePhoto(Request $request)
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        $request->validate([
            'profile_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $file = $request->file('profile_photo');

        // Extension from the real content, not the client filename (see BuyerController::updatePhoto).
        $filename = 'rider_' . $user['id'] . '_' . time() . '.' . $file->extension();

        // Save the actual image
        $file->storeAs(
            'profile-photos',
            $filename,
            'public'
        );

        // Save filename permanently in database
        DB::table('users')
            ->where('id', $user['id'])
            ->update([
                'profile_photo' => $filename,
                'updated_at' => now(),
            ]);

        // Also update current login session
        $user['profile_photo'] = $filename;
        session()->put('user', $user);

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

        $riderId = $user['id'] ?? null;


        // ==========================================
        // AVAILABLE ORDERS
        // ==========================================

        $availableOrders = DB::table('orders')
            ->where('status', 'Ready for Pickup')
            ->whereNull('rider_id')
            ->orderByDesc('created_at')
            ->get();

        $availableOrders = $availableOrders->map(function ($order) {

            $order = (array) $order;

            $items = DB::table('order_items')
                ->where('order_id', $order['id'])
                ->get();

            $order['items'] = $items->map(function ($item) {
                return (array) $item;
            })->toArray();

            $order['total'] = (float) $order['total_amount'];
            $order['buyer_name'] = $order['shipping_name'];
            $order['address'] = $order['shipping_address'];
            $order['phone'] = $order['shipping_phone'];
            $order['payment'] = $order['payment_method'];

            return $order;

        })->toArray();


        // ==========================================
        // MY DELIVERIES
        // ==========================================

        $myDeliveries = DB::table('orders')
            ->where('rider_id', $riderId)
            ->orderByDesc('created_at')
            ->get();

        $myDeliveries = $myDeliveries->map(function ($order) {

            $order = (array) $order;

            $items = DB::table('order_items')
                ->where('order_id', $order['id'])
                ->get();

            $order['items'] = $items->map(function ($item) {
                return (array) $item;
            })->toArray();

            $order['total'] = (float) $order['total_amount'];
            $order['buyer_name'] = $order['shipping_name'];
            $order['address'] = $order['shipping_address'];
            $order['phone'] = $order['shipping_phone'];
            $order['payment'] = $order['payment_method'];

            return $order;

        })->toArray();


        // ==========================================
        // ITEMS FOR DELIVERY (assigned by the Sorting Center for the
        // final-mile leg — may be a different rider than the one who
        // picked the parcel up from the seller)
        // ==========================================

        $myDeliveryAssignments = DB::table('orders')
            ->where('delivery_rider_id', $riderId)
            ->whereIn('status', ['Assigned for Delivery', 'Out for Delivery'])
            ->orderByDesc('updated_at')
            ->get();

        $myDeliveryAssignments = $myDeliveryAssignments->map(function ($order) {

            $order = (array) $order;

            $items = DB::table('order_items')
                ->where('order_id', $order['id'])
                ->get();

            $order['items'] = $items->map(function ($item) {
                return (array) $item;
            })->toArray();

            $order['total'] = (float) $order['total_amount'];
            $order['buyer_name'] = $order['shipping_name'];
            $order['address'] = $order['shipping_address'];
            $order['phone'] = $order['shipping_phone'];
            $order['payment'] = $order['payment_method'];

            return $order;

        })->toArray();


        // ==========================================
        // OUT FOR DELIVERY
        // ==========================================

        $inTransit = array_values(
            array_filter(
                $myDeliveries,
                function ($order) {

                    return ($order['status'] ?? '') === 'Out for Delivery';

                }
            )
        );


        // ==========================================
        // DELIVERED
        // ==========================================

        $delivered = array_values(
            array_filter(
                $myDeliveries,
                function ($order) {

                    return ($order['status'] ?? '') === 'Delivered';

                }
            )
        );


        // ==========================================
        // TOTAL COUNT
        // ==========================================

        $totalCount =
            count($availableOrders) +
            count($myDeliveries);


        // ==========================================
        // RIDER DASHBOARD
        // ==========================================

        return view(
            'pages.rider.dashboard',
            compact(
                'user',
                'availableOrders',
                'myDeliveries',
                'myDeliveryAssignments',
                'inTransit',
                'delivered',
                'totalCount'
            )
        );
    }

    public function deliveries()
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        $riderId = $user['id'] ?? null;

        // ==============================
        // AVAILABLE + MY DELIVERIES
        // ==============================

        $orders = DB::table('orders')
            ->where(function ($query) use ($riderId) {

                // Orders available for any rider
                $query->where(function ($q) {
                    $q->whereIn('status', [
                        'Ready for Pickup',
                        'Shipped'
                    ])
                    ->whereNull('rider_id');
                })

                // OR orders already assigned to this rider (pickup leg)
                ->orWhere('rider_id', $riderId)

                // OR orders assigned to this rider by the Sorting Center (delivery leg)
                ->orWhere('delivery_rider_id', $riderId);

            })
            ->orderByDesc('created_at')
            ->get();


        $deliveries = $orders->map(function ($order) {

            $order = (array) $order;

            // Get order items
            $items = DB::table('order_items')
                ->where('order_id', $order['id'])
                ->get();

            $order['items'] = $items->map(function ($item) {
                return (array) $item;
            })->toArray();

            // Fields expected by Rider Blade
            $order['total'] =
                (float) $order['total_amount'];

            $order['buyer_name'] =
                $order['shipping_name'];

            $order['address'] =
                $order['shipping_address'];

            $order['phone'] =
                $order['shipping_phone'];

            $order['payment'] =
                $order['payment_method'];

            return $order;

        })->toArray();


        return view(
            'pages.rider.deliveries',
            compact(
                'user',
                'deliveries'
            )
        );
    }

    public function claimDelivery($id)
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        $riderId = $user['id'] ?? null;

        $order = DB::table('orders')
            ->where('id', $id)
            ->first();

        if (!$order) {
            return back()->with(
                'error',
                'Delivery not found.'
            );
        }

        // Order must be Ready for Pickup
        if ($order->status !== 'Ready for Pickup') {

            return back()->with(
                'error',
                'This order is not ready for pickup.'
            );
        }

        // Prevent another rider from claiming it
        if (!empty($order->rider_id)) {

            return back()->with(
                'error',
                'This order has already been assigned to another rider.'
            );
        }

        // Atomic first-come-first-served claim: this UPDATE only affects a row
        // if it's STILL unclaimed and Ready for Pickup at the moment it runs,
        // so if two riders click "claim" at the same time, only one of these
        // queries actually changes a row — the database itself is the lock,
        // no separate read-then-write race is possible.
        $claimed = DB::table('orders')
            ->where('id', $id)
            ->where('status', 'Ready for Pickup')
            ->whereNull('rider_id')
            ->update([
                'rider_id' => $riderId,
                'status' => 'Assigned',
                'updated_at' => now(),
            ]);

        if (!$claimed) {

            return back()->with(
                'error',
                'This order was just claimed by another rider. Please choose a different delivery.'
            );
        }

        // ==============================
        // BUYER NOTIFICATION
        // ==============================

        createNotification(
            (int) $order->buyer_id,
            'Rider Assigned',
            'A rider has accepted your order #' . $id .
            ' and will pick it up from the seller shortly.',
            'order',
            (int) $id
        );

        // ==============================
        // RIDER NOTIFICATION
        // ==============================

        createNotification(
            (int) $riderId,
            'Delivery Accepted',
            'You accepted Order #' . $id .
            '. Proceed to the seller\'s location, verify the order, then confirm pickup.',
            'delivery',
            (int) $id
        );

        return back()->with(
            'success',
            'Delivery accepted! Proceed to the seller\'s location and confirm pickup once you have the order.'
        );
    }

    public function confirmPickup($id)
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        $riderId = $user['id'] ?? null;

        $order = DB::table('orders')->where('id', $id)->first();

        if (!$order) {
            return back()->with('error', 'Delivery not found.');
        }

        if ((int) $order->rider_id !== (int) $riderId) {
            return back()->with('error', 'This delivery is not assigned to you.');
        }

        if ($order->status !== 'Assigned') {
            return back()->with('error', 'This order has already been picked up.');
        }

        DB::table('orders')
            ->where('id', $id)
            ->where('rider_id', $riderId)
            ->where('status', 'Assigned')
            ->update([
                'status' => 'Picked Up',
                'updated_at' => now(),
            ]);

        createNotification(
            (int) $order->buyer_id,
            'Order Picked Up',
            'Your order #' . $id . ' has been picked up by the rider and is now on its way.',
            'order',
            (int) $id
        );

        notifyOrderSellers(
            (int) $id,
            'Order Picked Up by Rider',
            'Order #' . $id . ' has been picked up by the rider and is on its way to the Sorting Center.'
        );

        notifyLogisticsUsers(
            'Parcel En Route',
            'Order #' . $id . ' has been picked up by a rider and is on its way to the Sorting Center.',
            'parcel',
            (int) $id
        );

        return back()->with('success', 'Pickup confirmed! You can now mark the order Out for Delivery.');
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

        // Check if this order is available for pickup
        $isAvailable =
            $delivery->status === 'Ready for Pickup'
            && empty($delivery->rider_id);

        // Check if this order belongs to the logged-in rider — either as the
        // pickup-leg rider (rider_id) or, once the Sorting Center has assigned
        // it, as the final-mile delivery rider (delivery_rider_id).
        $isAssigned =
            (int) $delivery->rider_id === (int) $riderId
            || (int) ($delivery->delivery_rider_id ?? 0) === (int) $riderId;

        if (!$isAvailable && !$isAssigned) {
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

        return view(
            'pages.rider.delivery-details',
            compact('user', 'delivery')
        );
    }

    public function updateStatus($id)
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        $riderId = $user['id'] ?? null;

        $status = trim(request('status'));

        $allowedStatuses = [
            'Out for Delivery',
            'Delivered',
            'Delivery Failed',
        ];

        if (!in_array($status, $allowedStatuses)) {
            return back()->with(
                'error',
                'Invalid delivery status.'
            );
        }

        $order = DB::table('orders')
            ->where('id', $id)
            ->first();

        if (!$order) {
            return back()->with(
                'error',
                'Delivery not found.'
            );
        }

        // Make sure this delivery belongs to this rider — the final-mile leg is
        // owned by whoever the Sorting Center assigned (delivery_rider_id); older
        // orders placed before the Sorting Center hop existed fall back to rider_id.
        $effectiveRiderId = $order->delivery_rider_id ?? $order->rider_id;

        if ((int) $effectiveRiderId !== (int) $riderId) {
            return back()->with(
                'error',
                'This delivery is not assigned to you.'
            );
        }

        // Assigned for Delivery → Out for Delivery
        if ($order->status === 'Assigned for Delivery') {

            if ($status !== 'Out for Delivery') {
                return back()->with(
                    'error',
                    'This order must be moved to Out for Delivery first.'
                );
            }

        }

        // Out for Delivery → Delivered or Delivery Failed
        elseif ($order->status === 'Out for Delivery') {

            if (!in_array($status, ['Delivered', 'Delivery Failed'])) {
                return back()->with(
                    'error',
                    'Out for Delivery orders can only be marked as Delivered or Delivery Failed.'
                );
            }

            if ($status === 'Delivery Failed') {

                $code = trim((string) request('failure_code'));
                $details = trim((string) request('failure_reason'));

                if (!in_array($code, self::FAILURE_REASONS, true)) {
                    return back()->with('error', 'Please choose a reason for the failed delivery.');
                }

                if ($code === 'Other' && $details === '') {
                    return back()->with('error', 'Please describe why the delivery failed.');
                }

                $reason = $code === 'Other'
                    ? $details
                    : $code . ($details !== '' ? ' — ' . $details : '');

                $refused = $code === self::REFUSED_REASON;

                // Conditional on still being Out for Delivery so a double
                // submit can't count the attempt (or the refusal) twice.
                $updated = DB::table('orders')
                    ->where('id', $id)
                    ->where('status', 'Out for Delivery')
                    ->update([
                        'status' => 'Delivery Failed',
                        'failure_reason' => $reason,
                        'delivery_failed_at' => now(),
                        'delivery_attempts' => $order->delivery_attempts + 1,
                        // A refusal is final — the Sorting Center returns it to
                        // the seller instead of rescheduling, and it counts
                        // toward the buyer's COD limit.
                        'buyer_refused_at' => $refused ? now() : null,
                        'updated_at' => now(),
                    ]);

                if (!$updated) {
                    return back()->with('error', 'This delivery was just updated. Please refresh and try again.');
                }

                if ($refused) {

                    createNotification(
                        (int) $order->buyer_id,
                        'Parcel Refused',
                        'You refused order #' . $id . ' on delivery, so it will be returned to the seller. Refused and cancelled orders count toward the Cash on Delivery limit on your account.',
                        'order',
                        (int) $id
                    );

                    notifyOrderSellers(
                        (int) $id,
                        'Parcel Refused by Buyer',
                        'The buyer refused order #' . $id . ' on delivery. The parcel will be brought back to the Sorting Center and returned to you.'
                    );

                    return back()->with('success', 'Marked as refused by the buyer. Please bring the parcel back to the Sorting Center — it will be returned to the seller.');
                }

                createNotification(
                    (int) $order->buyer_id,
                    'Delivery Attempt Failed',
                    'We were unable to deliver your order #' . $id . '. Reason: ' . $reason . '. It will be rescheduled shortly.',
                    'order',
                    (int) $id
                );

                notifyOrderSellers(
                    (int) $id,
                    'Delivery Attempt Failed',
                    'The rider could not deliver order #' . $id . '. Reason: ' . $reason . '. The Sorting Center will reschedule or return it.'
                );

                return back()->with('success', 'Delivery marked as failed. The Sorting Center will reschedule or return this parcel.');

            }

        }

        // Prevent invalid updates
        else {

            return back()->with(
                'error',
                'This order cannot be updated from its current status.'
            );

        }

        // Update order status — only from the status we just validated, so a
        // double-tap can't send the buyer duplicate notifications.
        $updated = DB::table('orders')
            ->where('id', $id)
            ->where('status', $order->status)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return back()->with('error', 'This delivery was just updated. Please refresh and try again.');
        }

        // ==============================
        // BUYER NOTIFICATIONS
        // ==============================

        if ($status === 'Out for Delivery') {

            createNotification(
                (int) $order->buyer_id,
                'Order Out for Delivery',
                'Your order #' . $id .
                ' is now out for delivery.',
                'order',
                (int) $id
            );

        } elseif ($status === 'Delivered') {

            createNotification(
                (int) $order->buyer_id,
                'Order Delivered',
                'Your order #' . $id .
                ' has been delivered successfully.',
                'order',
                (int) $id
            );

            notifyOrderSellers(
                (int) $id,
                'Order Delivered',
                'Order #' . $id . ' has been delivered to the buyer.'
            );
        }

        // ==============================
        // RIDER NOTIFICATION
        // ==============================

        if ($status === 'Out for Delivery') {

            createNotification(
                (int) $riderId,
                'Delivery Out for Delivery',
                'Order #' . $id .
                ' is now out for delivery.',
                'delivery_status',
                (int) $id
            );

        } elseif ($status === 'Delivered') {

            createNotification(
                (int) $riderId,
                'Delivery Completed',
                'Order #' . $id .
                ' has been successfully delivered.',
                'delivery_status',
                (int) $id
            );
        }

        return back()->with(
            'success',
            'Delivery status updated to ' . $status . '!'
        );
    }

    public function profile()
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        return view(
            'pages.rider.profile',
            compact('user')
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
            ->whereDate('updated_at', '>=', $from)
            ->whereDate('updated_at', '<=', $to)
            ->orderByDesc('updated_at')
            ->get();

        $totalDeliveries = $completedDeliveries->count();
        $totalProfit = $totalDeliveries * $deliveryFee;

        $dailyProfit = $completedDeliveries
            ->groupBy(function ($order) {
                return Carbon::parse($order->updated_at)->format('Y-m-d');
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
            ->whereDate('updated_at', '>=', $from)
            ->whereDate('updated_at', '<=', $to)
            ->orderByDesc('updated_at')
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

    public function submitApply(Request $request)
    {
        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:5'],
            'sex' => ['required', 'in:Male,Female'],
            'birthdate' => ['required', 'date', 'before:today'],

            'phone' => [
                'required',
                'string',
                'max:30',
                'unique:users,phone'
            ],

            'province' => ['required', 'string', 'max:255'],
            'city_municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'street_address' => ['required', 'string', 'max:255'],

            'vehicle_type' => [
                'required',
                'in:Motorcycle,Car,Van'
            ],

            'vehicle_model' => [
                'required',
                'string',
                'max:255'
            ],

            'plate_number' => [
                'required',
                'string',
                'max:50'
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email'
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed'
            ],

            'national_id' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240'
            ],

            'drivers_license' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240'
            ],

            'profile_selfie' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240'
            ],

            'proof_of_address' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240'
            ],

            'or_cr' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240'
            ],

            'terms' => ['accepted'],

        ], [

            'terms.accepted' =>
                'Please agree to the Terms & Conditions and Privacy Policy.',

        ]);

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
        ->get();

        return view(
            'pages.rider.notifications',
            compact('user', 'notifications')
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
