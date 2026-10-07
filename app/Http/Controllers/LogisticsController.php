<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\RiderArea;
use App\Models\SortingCenter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\SortingCenterService;
use App\Exceptions\ActionFailed;
use App\Http\Requests\ProfilePhotoRequest;
use App\Services\ProfilePhotoService;

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

        if ($birthdateError = birthdateError($birthdate, 18)) {
            return back()
                ->withInput()
                ->with('error', $birthdateError);
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

        // A rejected applicant may apply again with the same email.
        $reapplying = \App\Support\RejectedApplicant::find($email, 'logistics');

        if (!$reapplying && User::where('email', $email)->exists()) {
            return back()
                ->withInput()
                ->with('error', 'Email is already registered.');
        }

        if (User::where('phone', $phone)->when($reapplying, fn ($q) => $q->where('id', '!=', $reapplying->id))->exists()) {
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
            ->orderByDesc('id')
            ->first();

        // Every courier-side status, in pipeline order.
        $pipelineStatuses = [
            'Ready for Pickup' => 'Waiting at the seller for a pickup rider',
            'Dropped Off' => 'Dropped off by the seller — confirm arrival',
            'Picked Up' => 'Picked up by a rider — confirm arrival',
            'At Sorting Center' => 'At a Sorting Center',
            'In Transit' => 'Between Sorting Centers',
            'Sorted' => 'Sorted by area — assign a rider',
            'Assigned for Delivery' => 'Delivery rider assigned',
            'Out for Delivery' => 'With the rider, on the way to the buyer',
            'Delivery Failed' => 'Needs a reschedule or return',
            'Ready to Collect' => 'Waiting at a center for the buyer to pick up',
            'Returning' => 'Going back to the seller\'s Sorting Center',
            'Return Ready' => 'Waiting for the seller to collect a return',
        ];

        // A center's staff see their own center's numbers; head office sees all.
        $myCenter = $this->staffCenter($user);
        $unassigned = $this->isUnassigned($user);
        $queues = $this->parcelQueues($myCenter, '', $unassigned);
        $involvesMe = function ($query) use ($myCenter, $unassigned) {
            if ($unassigned) {
                $query->whereRaw('1 = 0');
            } elseif ($myCenter) {
                $query->where(fn ($q) => $q->where('origin_center_id', $myCenter->id)
                    ->orWhere('destination_center_id', $myCenter->id)
                    ->orWhere('current_center_id', $myCenter->id)
                    ->orWhere(fn ($w) => $w->whereNull('origin_center_id')->whereNull('destination_center_id')));
            }
        };

        $statusCounts = DB::table('orders')
            ->whereIn('status', array_keys($pipelineStatuses))
            ->where($involvesMe)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $pipeline = collect($pipelineStatuses)
            ->map(fn ($hint, $status) => ['hint' => $hint, 'count' => (int) ($statusCounts[$status] ?? 0)]);

        $activeRiderIds = DB::table('users')
            ->join('rider_applications', 'rider_applications.user_id', '=', 'users.id')
            ->where('users.role', 'rider')
            ->where('users.status', 'Active')
            ->where('rider_applications.status', 'Approved')
            ->distinct()
            ->pluck('users.id');

        // A center's own riders: those covering a province in its region.
        if ($unassigned) {
            $activeRiderIds = collect();
        } elseif ($myCenter) {
            $activeRiderIds = RiderArea::whereIn('rider_id', $activeRiderIds)
                ->whereIn('province', $myCenter->provinces)
                ->distinct()
                ->pluck('rider_id');
        }

        $activeRiders = $activeRiderIds->count();

        // Riders logistics can't suggest for any parcel yet.
        $ridersWithoutArea = $activeRiderIds
            ->diff(RiderArea::whereIn('rider_id', $activeRiderIds)->distinct()->pluck('rider_id'))
            ->count();

        $riderProvinces = $this->riderProvinces($user);

        $pendingRiderApplications = DB::table('rider_applications')
            ->where('status', 'Pending Verification')
            ->when($riderProvinces !== null, fn ($q) => $this->riderInProvinces($q, $riderProvinces, 'rider_applications.user_id'))
            ->count();

        $today = now()->toDateString();

        $deliveredToday = DB::table('orders')
            ->whereIn('status', \App\Support\OrderStatus::DONE)
            ->whereDate('delivered_at', $today)
            ->where($queues['delivering'])
            ->count();

        $failedToday = DB::table('orders')
            ->whereDate('delivery_failed_at', $today)
            ->where($queues['delivering'])
            ->count();

        $returnedThisWeek = DB::table('orders')
            ->where('status', 'Returned to Seller')
            ->where('updated_at', '>=', now()->subDays(7))
            ->where($queues['delivering'])
            ->count();

        // What's waiting on logistics right now — the same lists as the Parcels page.
        $todo = [
            [
                'icon' => 'bi-envelope-paper-fill',
                'label' => 'Confirm arrivals',
                'hint' => 'Parcels riders picked up from sellers, and parcels sent here from other centers.',
                'count' => count($queues['awaitingConfirmation']) + count($queues['incoming']),
                'url' => route('logistics.parcels') . '#awaiting-confirmation',
            ],
            [
                'icon' => 'bi-send-fill',
                'label' => 'Dispatch parcels',
                'hint' => 'Going to a buyer in another town — send them on to that Sorting Center.',
                'count' => count($queues['toDispatch']),
                'url' => route('logistics.parcels') . '#to-dispatch',
            ],
            [
                'icon' => 'bi-diagram-3-fill',
                'label' => 'Sort parcels',
                'hint' => 'Read each address and sort it by the buyer\'s area.',
                'count' => count($queues['toSort']),
                'url' => route('logistics.parcels') . '#to-sort',
            ],
            [
                'icon' => 'bi-inbox-fill',
                'label' => 'Assign riders',
                'hint' => 'Sorted parcels — give each to the rider who covers its area.',
                'count' => count($queues['awaitingAssignment']),
                'url' => route('logistics.parcels') . '#awaiting-assignment',
            ],
            [
                'icon' => 'bi-box-arrow-in-down',
                'label' => 'Assign pickup riders',
                'hint' => 'Sellers\' parcels ready for pickup that no rider accepted yet.',
                'count' => count($queues['pickupsNeedRider']),
                'url' => route('logistics.parcels') . '#pickups-need-rider',
            ],
            [
                'icon' => 'bi-exclamation-triangle-fill',
                'label' => 'Failed deliveries',
                'hint' => 'Reschedule to a rider or return to the seller.',
                'count' => count($queues['failedDeliveries']),
                'url' => route('logistics.parcels') . '#failed-deliveries',
            ],
            [
                'icon' => 'bi-person-vcard-fill',
                'label' => 'Rider applications',
                'hint' => 'New riders waiting for document review.',
                'count' => $pendingRiderApplications,
                'url' => route('logistics.riders'),
            ],
        ];

        $recentParcels = DB::table('orders')
            ->whereIn('status', array_merge(array_keys($pipelineStatuses), ['Delivered', 'Completed', 'Returned to Seller']))
            ->where($involvesMe)
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get(['id', 'status', 'shipping_name', 'shipping_address', 'updated_at']);

        // Here and still without a delivery rider, oldest first.
        $needsRider = $queues['awaitingAssignment']->take(5);

        // Active riders and what each is carrying right now.
        $riders = DB::table('users')
            ->whereIn('id', $activeRiderIds)
            ->orderBy('name')
            ->get(['id', 'name', 'profile_photo', 'city_municipality']);

        $deliveryLoad = DB::table('orders')
            ->whereIn('delivery_rider_id', $activeRiderIds)
            ->whereIn('status', ['Assigned for Delivery', 'Out for Delivery'])
            ->select('delivery_rider_id', DB::raw('COUNT(*) as total'))
            ->groupBy('delivery_rider_id')
            ->pluck('total', 'delivery_rider_id');

        $ridersOnDuty = $riders
            ->map(fn ($rider) => [
                'id' => $rider->id,
                'name' => $rider->name,
                'photo' => $rider->profile_photo,
                'area' => $rider->city_municipality,
                'load' => (int) ($deliveryLoad[$rider->id] ?? 0),
            ])
            ->sortByDesc('load')
            ->take(6)
            ->values();

        return view(
            'pages.logistics.dashboard',
            compact(
                'user',
                'application',
                'pipeline',
                'todo',
                'needsRider',
                'ridersOnDuty',
                'activeRiders',
                'ridersWithoutArea',
                'deliveredToday',
                'failedToday',
                'returnedThisWeek',
                'recentParcels',
                'unassigned'
            )
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
            ->orderByDesc('id')
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

    public function updatePhoto(ProfilePhotoRequest $request, ProfilePhotoService $photos)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        session()->put('user', $photos->store($user, $request->file('profile_photo')));

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

        \App\Support\LoginGate::passwordChanged($dbUser, true);

        return back()->with('success', 'Password changed successfully.');
    }

    public function riders()
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        // Same chip order as the admin's Applications page.
        $statuses = [
            'all' => ['label' => 'All', 'status' => null],
            'pending' => ['label' => 'Pending', 'status' => 'Pending Verification'],
            'approved' => ['label' => 'Approved', 'status' => 'Approved'],
            'rejected' => ['label' => 'Rejected', 'status' => 'Rejected'],
        ];

        $search = trim((string) request('q', ''));

        // A center's staff see the riders of their own province only.
        $riderProvinces = $this->riderProvinces($user);

        $base = DB::table('rider_applications')
            ->join('users', 'users.id', '=', 'rider_applications.user_id')
            ->when($riderProvinces !== null, fn ($q) => $this->riderInProvinces($q, $riderProvinces))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';

                $query->where(function ($q) use ($like) {
                    $q->where('rider_applications.full_name', 'like', $like)
                        ->orWhere('users.email', 'like', $like)
                        ->orWhere('rider_applications.phone', 'like', $like)
                        ->orWhere('rider_applications.plate_number', 'like', $like);
                });
            });

        $statusCounts = (clone $base)
            ->select('rider_applications.status', DB::raw('COUNT(*) as total'))
            ->groupBy('rider_applications.status')
            ->pluck('total', 'status');

        // Open on pending applications when there are any, else the approved riders.
        $statusKey = request('status');

        if (!array_key_exists((string) $statusKey, $statuses)) {
            $statusKey = ($statusCounts['Pending Verification'] ?? 0) > 0 ? 'pending' : 'all';
        }

        $riderApplications = (clone $base)
            ->when($statuses[$statusKey]['status'], fn ($q, $status) => $q->where('rider_applications.status', $status))
            ->select('rider_applications.*', 'users.email as user_email', 'users.status as account_status')
            ->orderBy('rider_applications.created_at', $statusKey === 'pending' ? 'asc' : 'desc')
            ->paginate(15)
            ->withQueryString();

        $riderIds = $riderApplications->pluck('user_id');

        $riderAreas = RiderArea::whereIn('rider_id', $riderIds)->get()->groupBy('rider_id');

        // What each rider is carrying right now, so logistics can spread the work.
        $deliveryLoad = DB::table('orders')
            ->whereIn('delivery_rider_id', $riderIds)
            ->whereIn('status', ['Assigned for Delivery', 'Out for Delivery'])
            ->select('delivery_rider_id', DB::raw('COUNT(*) as total'))
            ->groupBy('delivery_rider_id')
            ->pluck('total', 'delivery_rider_id');

        return view(
            'pages.logistics.riders',
            compact('user', 'riderApplications', 'riderAreas', 'statuses', 'statusKey', 'statusCounts', 'search', 'deliveryLoad')
        );
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

        // Staff give riders areas in their own center's province only.
        $riderProvinces = $this->riderProvinces($user);

        if ($riderProvinces !== null && !in_array($province, $riderProvinces, true)) {
            return back()->with('error', $riderProvinces === [] ? self::NOT_ASSIGNED : 'You can only assign areas in ' . implode(', ', $riderProvinces) . '.');
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

        $area = RiderArea::find($areaId);
        $riderProvinces = $this->riderProvinces($user);

        if ($area && $riderProvinces !== null && !in_array($area->province, $riderProvinces, true)) {
            return back()->with('error', $riderProvinces === [] ? self::NOT_ASSIGNED : 'That area is in another Sorting Center\'s province.');
        }

        $area?->delete();

        return back()->with('success', 'Area removed.');
    }

    /** Sort a parcel by its delivery area (the step before assigning a rider). */
    public function sortParcel($id, \App\Services\SortingCenterService $centers)
    {
        return $this->parcelAction(fn (?int $myCenter) => $centers->sort((int) $id, $myCenter));
    }

    /** Give a seller's waiting pickup to a rider. */
    public function assignPickup($id, \App\Services\PickupService $pickups)
    {
        return $this->parcelAction(fn (?int $myCenter) => $pickups->assignByCenter((int) $id, request("rider_id"), $myCenter));
    }

    public function parcels()
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $search = trim((string) request('q', ''));
        $myCenter = $this->staffCenter($user);
        $unassigned = $this->isUnassigned($user);

        [
            'matching' => $matching,
            'awaitingConfirmation' => $awaitingConfirmation,
            'toDispatch' => $toDispatch,
            'incoming' => $incoming,
            'toSort' => $toSort,
            'awaitingAssignment' => $awaitingAssignment,
            'pickupsNeedRider' => $pickupsNeedRider,
            'failedDeliveries' => $failedDeliveries,
            'onTheRoad' => $onTheRoad,
            'readyToCollect' => $readyToCollect,
            'incomingReturns' => $incomingReturns,
            'returnReady' => $returnReady,
            'delivering' => $delivering,
        ] = $this->parcelQueues($myCenter, $search, $unassigned);

        $centerNames = SortingCenter::pluck('name', 'id');

        // Cash on Delivery money riders collected for this center's
        // deliveries and haven't handed in yet.
        $codToReceive = DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.delivery_rider_id')
            ->whereNotNull('orders.cod_collected_at')
            ->whereNull('orders.cod_remitted_at')
            ->where($delivering)
            ->groupBy('orders.delivery_rider_id', 'users.name')
            ->orderBy('users.name')
            ->selectRaw('orders.delivery_rider_id as rider_id, users.name as rider_name, COUNT(*) as parcels, SUM(orders.total_amount) as amount')
            ->get();

        // A searched parcel that's in none of the lists above (still with
        // the seller, delivered, cancelled…) — say where it is instead of
        // showing nothing.
        $elsewhere = collect();

        if ($search !== '') {
            $shownIds = $awaitingConfirmation->pluck('id')
                ->merge($toDispatch->pluck('id'))
                ->merge($incoming->pluck('id'))
                ->merge($toSort->pluck('id'))
                ->merge($awaitingAssignment->pluck('id'))
                ->merge($pickupsNeedRider->pluck('id'))
                ->merge($failedDeliveries->pluck('id'))
                ->merge($onTheRoad->pluck('id'))
                ->merge($readyToCollect->pluck('id'))
                ->merge($incomingReturns->pluck('id'))
                ->merge($returnReady->pluck('id'));

            $elsewhere = $matching()
                ->whereNotIn('id', $shownIds)
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get();
        }

        $activeRiders = DB::table('users')
            ->join('rider_applications', 'rider_applications.user_id', '=', 'users.id')
            ->where('users.role', 'rider')
            ->where('users.status', 'Active')
            ->where('rider_applications.status', 'Approved')
            ->select('users.id', 'users.name')
            ->distinct()
            ->orderBy('users.name')
            ->get();

        if ($unassigned) {
            $activeRiders = collect();
        }

        $riderAreas = RiderArea::whereIn('rider_id', $activeRiders->pluck('id'))->get();

        // A center's staff pick from their own riders — those covering the
        // center's region. (No riders there yet → everyone, so a parcel is
        // never stuck.) Head office picks from all.
        if ($myCenter) {
            $ownRiderIds = $riderAreas->whereIn('province', $myCenter->provinces)->pluck('rider_id')->unique();

            if ($ownRiderIds->isNotEmpty()) {
                $activeRiders = $activeRiders->whereIn('id', $ownRiderIds)->values();
                $riderAreas = $riderAreas->whereIn('rider_id', $ownRiderIds);
            }
        }

        // For each parcel that needs a rider, suggest riders covering the
        // buyer's town, then the rest of the buyer's province. (For a pickup:
        // the seller's town.)
        $suggestedRidersByOrder = [];

        foreach ($toSort->merge($awaitingAssignment)->merge($failedDeliveries)->merge($pickupsNeedRider) as $order) {

            if ($order->status === 'Ready for Pickup') {
                $sellerId = (int) DB::table('order_items')->where('order_id', $order->id)->value('seller_id');
                $town = \App\Support\ParcelRoute::sellerLocation($sellerId);
                $town = $town['province'] ? $town : null;
            } else {
                $town = $order->shipping_province
                    ? ['province' => $order->shipping_province, 'city' => $order->shipping_city]
                    : \App\Support\PhLocations::locate($order->shipping_address);
            }

            $matches = $riderAreas
                ->filter(fn ($area) => $town && $area->province === $town['province'])
                ->sortByDesc(fn ($area) => \App\Support\PhLocations::sameTown($area->city_municipality, $town['city'] ?? null))
                ->pluck('rider_id')
                ->unique();

            $suggestedRidersByOrder[$order->id] = $matches->map(fn ($id) => $activeRiders->firstWhere('id', $id))->filter()->values();
        }

        $buyerReturns = $this->buyerReturnQueues($myCenter, $unassigned, $search);

        // Names of the riders already attached to the listed parcels.
        $riderNames = DB::table('users')
            ->whereIn('id', $onTheRoad->pluck('delivery_rider_id')->merge($failedDeliveries->pluck('delivery_rider_id'))->filter()->unique())
            ->pluck('name', 'id');

        return view(
            'pages.logistics.parcels',
            compact(
                'user',
                'search',
                'awaitingConfirmation',
                'toDispatch',
                'incoming',
                'toSort',
                'awaitingAssignment',
                'pickupsNeedRider',
                'failedDeliveries',
                'onTheRoad',
                'elsewhere',
                'activeRiders',
                'suggestedRidersByOrder',
                'riderNames',
                'myCenter',
                'centerNames',
                'readyToCollect',
                'incomingReturns',
                'returnReady',
                'codToReceive',
                'unassigned',
                'buyerReturns'
            )
        );
    }

    /**
     * A scanned shipping label (its QR opens this): straight to that parcel
     * on the Parcels page, where its next step's button is.
     */
    public function scanParcel($code)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $orderId = \App\Support\Waybill::parse($code);

        if (!$orderId || !DB::table('orders')->where('id', $orderId)->exists()) {
            return redirect()->route('logistics.parcels')->with('error', 'No parcel matches "' . $code . '".');
        }

        return redirect()->route('logistics.parcels', ['q' => \App\Support\Waybill::number($orderId)]);
    }

    public function confirmParcelReceived($id, SortingCenterService $center)
    {
        return $this->parcelAction(fn (?int $myCenter) => $center->confirmReceived((int) $id, $myCenter));
    }

    public function dispatchParcel($id, SortingCenterService $center)
    {
        return $this->parcelAction(fn (?int $myCenter) => $center->dispatch((int) $id, $myCenter));
    }

    public function confirmParcelArrival($id, SortingCenterService $center)
    {
        return $this->parcelAction(fn (?int $myCenter) => $center->confirmArrival((int) $id, $myCenter));
    }

    public function dispatchAllParcels(SortingCenterService $center)
    {
        return $this->parcelAction(fn (?int $myCenter) => $center->dispatchAll((int) request('destination_center_id'), $myCenter));
    }

    public function handParcelToBuyer($id, SortingCenterService $center)
    {
        return $this->parcelAction(fn (?int $myCenter) => $center->handToBuyer((int) $id, $myCenter, (string) request('pickup_code')));
    }

    public function confirmReturnArrival($id, SortingCenterService $center)
    {
        return $this->parcelAction(fn (?int $myCenter) => $center->confirmReturnArrival((int) $id, $myCenter));
    }

    public function handParcelBackToSeller($id, SortingCenterService $center)
    {
        return $this->parcelAction(fn (?int $myCenter) => $center->handBackToSeller((int) $id, $myCenter));
    }

    public function receiveRiderCod($riderId, SortingCenterService $center)
    {
        return $this->parcelAction(fn (?int $myCenter) => $center->receiveCod((int) $riderId, $myCenter));
    }

    public function assignParcel($id, SortingCenterService $center)
    {
        return $this->parcelAction(fn (?int $myCenter) => $center->assign((int) $id, request('rider_id'), $myCenter));
    }

    public function receiveBuyerReturn($id, \App\Services\ReturnRefundService $returns)
    {
        return $this->parcelAction(fn (?int $myCenter) => $returns->receiveFromBuyer((int) $id, $myCenter));
    }

    public function sendBuyerReturn($id, \App\Services\ReturnRefundService $returns)
    {
        return $this->parcelAction(fn (?int $myCenter) => $returns->sendToSellerCenter((int) $id, $myCenter));
    }

    public function arriveBuyerReturn($id, \App\Services\ReturnRefundService $returns)
    {
        return $this->parcelAction(fn (?int $myCenter) => $returns->arriveAtSellerCenter((int) $id, $myCenter));
    }

    public function handBuyerReturnToSeller($id, \App\Services\ReturnRefundService $returns)
    {
        return $this->parcelAction(fn (?int $myCenter) => $returns->handToSeller((int) $id, $myCenter));
    }

    public function confirmParcelBack($id, SortingCenterService $center)
    {
        return $this->parcelAction(fn (?int $myCenter) => $center->confirmBack((int) $id, $myCenter));
    }

    public function rescheduleParcel($id, SortingCenterService $center)
    {
        return $this->parcelAction(fn (?int $myCenter) => $center->reschedule((int) $id, request('rider_id'), $myCenter));
    }

    public function returnParcelToSeller($id, SortingCenterService $center)
    {
        return $this->parcelAction(fn (?int $myCenter) => $center->returnToSeller((int) $id, $myCenter));
    }

    /**
     * Runs a Sorting Center action as this staff member's center and flashes
     * its message (or the rule it broke).
     */
    private function parcelAction(callable $action)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        if ($this->isUnassigned($user)) {
            return back()->with('error', self::NOT_ASSIGNED);
        }

        try {
            return back()->with('success', $action($this->staffCenter($user)?->id));
        } catch (ActionFailed $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * The parcel lists one staff member works on — the Parcels page shows
     * them and the dashboard counts them, so both always agree.
     *
     * Staff of one center only get that center's parcels; head office (no
     * center) gets all. Parcels from before Sorting Centers (no center at
     * all) show to everyone.
     */
    private function parcelQueues(?SortingCenter $myCenter, string $search = '', bool $none = false): array
    {
        // Order # (with or without "#"), waybill no. (BB-000066), buyer name or address.
        // $none: an account not assigned to any center sees nothing.
        $matching = function () use ($search, $none) {
            return DB::table('orders')->when($none, fn ($q) => $q->whereRaw('1 = 0'))->when($search !== '', function ($query) use ($search) {
                $number = \App\Support\Waybill::parse($search);
                $like = '%' . $search . '%';

                $query->where(function ($q) use ($number, $like) {
                    if ($number) {
                        $q->orWhere('id', $number);
                    }

                    $q->orWhere('shipping_name', 'like', $like)
                        ->orWhere('shipping_address', 'like', $like);
                });
            });
        };

        $mine = fn (string $column) => function ($query) use ($myCenter, $column) {
            if ($myCenter) {
                $query->where(fn ($q) => $q->where($column, $myCenter->id)->orWhereNull($column));
            }
        };

        // The center that delivers to the buyer: the destination, or the
        // origin when there's no center near the buyer.
        $delivering = function ($query) use ($myCenter, $none) {
            if ($none) {
                $query->whereRaw('1 = 0');
            } elseif ($myCenter) {
                $query->where(fn ($q) => $q->where('destination_center_id', $myCenter->id)
                    ->orWhere(fn ($w) => $w->whereNull('destination_center_id')
                        ->where(fn ($o) => $o->where('origin_center_id', $myCenter->id)->orWhereNull('origin_center_id'))));
            }
        };

        return [
            'matching' => $matching,

            // Dropped off by the seller at this center (or, on older orders,
            // brought in by a pickup rider) — confirm it arrived.
            'awaitingConfirmation' => $matching()
                ->whereIn('status', ['Dropped Off', 'Picked Up'])
                ->where($mine('origin_center_id'))
                ->orderBy('updated_at')
                ->get(),

            // Received here, but the buyer lives in another center's town.
            'toDispatch' => $matching()
                ->where('status', 'At Sorting Center')
                ->whereNotNull('destination_center_id')
                ->whereNotNull('current_center_id')
                ->whereColumn('current_center_id', '!=', 'destination_center_id')
                ->where($mine('current_center_id'))
                ->orderBy('sorting_center_received_at')
                ->get(),

            // Dispatched from another center to this one.
            'incoming' => $matching()
                ->where('status', 'In Transit')
                ->where($mine('destination_center_id'))
                ->orderBy('dispatched_at')
                ->get(),

            // At the center that delivers to the buyer: sort it by the
            // buyer's area first…
            'toSort' => $matching()
                ->where('status', 'At Sorting Center')
                ->where(fn ($q) => $q->whereNull('destination_center_id')
                    ->orWhereNull('current_center_id')
                    ->orWhereColumn('current_center_id', 'destination_center_id'))
                ->where($mine('current_center_id'))
                ->orderBy('sorting_center_received_at')
                ->get(),

            // …then give it to the rider who covers that area.
            'awaitingAssignment' => $matching()
                ->where('status', 'Sorted')
                ->where($mine('current_center_id'))
                ->orderBy('updated_at')
                ->get(),

            // Sellers' parcels ready for pickup that no rider accepted yet.
            'pickupsNeedRider' => $matching()
                ->where('status', 'Ready for Pickup')
                ->whereNull('rider_id')
                ->where($mine('origin_center_id'))
                ->orderBy('pickup_date')
                ->get(),

            'failedDeliveries' => $matching()
                ->where('status', 'Delivery Failed')
                ->where($delivering)
                ->orderByDesc('delivery_failed_at')
                ->get(),

            // Already handed to a delivery rider — read-only, so logistics can
            // see where every parcel that left the Sorting Center is.
            'onTheRoad' => $matching()
                ->whereIn('status', ['Assigned for Delivery', 'Out for Delivery'])
                ->where($delivering)
                ->orderBy('updated_at')
                ->get(),

            // Pick-up orders waiting here for their buyer.
            'readyToCollect' => $matching()
                ->where('status', 'Ready to Collect')
                ->where($mine('current_center_id'))
                ->orderBy('updated_at')
                ->get(),

            // Returned parcels on their way back here, to this center's sellers.
            'incomingReturns' => $matching()
                ->where('status', 'Returning')
                ->where($mine('origin_center_id'))
                ->orderBy('dispatched_at')
                ->get(),

            // Returned parcels here, waiting for their seller.
            'returnReady' => $matching()
                ->where('status', 'Return Ready')
                ->where($mine('current_center_id'))
                ->orderBy('updated_at')
                ->get(),

            'delivering' => $delivering,
        ];
    }

    /**
     * Items buyers are sending back to sellers, by what this center has to
     * do: take it from the buyer, send it on, confirm it arrived, give it to
     * the seller. Head office sees every center's; not assigned sees none.
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    private function buyerReturnQueues(?SortingCenter $myCenter, bool $none, string $search = ''): array
    {
        $base = fn () => DB::table('return_refund_requests')
            ->join('orders', 'orders.id', '=', 'return_refund_requests.order_id')
            ->leftJoin('order_items', 'order_items.id', '=', 'return_refund_requests.order_item_id')
            ->leftJoin('users as buyers', 'buyers.id', '=', 'return_refund_requests.buyer_id')
            ->where('return_refund_requests.request_type', 'Return')
            ->when($none, fn ($q) => $q->whereRaw('1 = 0'))
            ->when($search !== '', function ($q) use ($search) {
                $number = (int) ltrim($search, '#');
                $q->where(fn ($w) => $w->where('return_refund_requests.id', $number)->orWhere('return_refund_requests.order_id', \App\Support\Waybill::parse($search) ?? $number));
            })
            ->select('return_refund_requests.*', 'order_items.product_name', 'order_items.quantity', 'buyers.name as buyer_name', 'orders.origin_center_id', 'orders.destination_center_id')
            ->orderBy('return_refund_requests.updated_at');

        $at = fn (string $column) => fn ($q) => $myCenter ? $q->where(fn ($w) => $w->where($column, $myCenter->id)->orWhereNull($column)) : $q;

        return [
            // Approved: the buyer brings it to their own center.
            'toReceive' => $base()->where('return_refund_requests.status', 'approved')->where($at('orders.destination_center_id'))->get(),
            'toSend' => $base()->where('return_refund_requests.status', 'dropped_off')->where($at('return_refund_requests.current_center_id'))->get(),
            'incoming' => $base()->where('return_refund_requests.status', 'in_transit')->where($at('orders.origin_center_id'))->get(),
            'forSeller' => $base()->where('return_refund_requests.status', 'ready_for_seller')->where($at('return_refund_requests.current_center_id'))->get(),
        ];
    }

    /** The center this logistics account works at; null = head office (all centers) — or not assigned, see isUnassigned(). */
    private function staffCenter(array $user): ?SortingCenter
    {
        $centerId = DB::table('users')->where('id', $user['id'])->value('sorting_center_id');

        return $centerId ? SortingCenter::find($centerId) : null;
    }

    /**
     * Not given a center and not head office: a new account the admin hasn't
     * assigned yet. It sees no parcels or riders and can't act on any.
     */
    private function isUnassigned(array $user): bool
    {
        $row = DB::table('users')->where('id', $user['id'])->first(['sorting_center_id', 'is_head_office']);

        return !$row || (!$row->sorting_center_id && !$row->is_head_office);
    }

    /**
     * The provinces whose riders this staff member looks after: their
     * center's. null = head office (every rider); [] = not assigned (none).
     *
     * @return string[]|null
     */
    private function riderProvinces(array $user): ?array
    {
        if ($this->isUnassigned($user)) {
            return [];
        }

        return $this->staffCenter($user)?->provinces;
    }

    /** A rider (or applicant) living in, or covering an area in, one of these provinces. */
    private function riderInProvinces($query, array $provinces, string $userIdColumn = 'users.id'): void
    {
        $query->where(fn ($q) => $q->whereExists(fn ($home) => $home->select(DB::raw(1))->from('users as rider_home')
                ->whereColumn('rider_home.id', $userIdColumn)
                ->whereIn('rider_home.province', $provinces))
            ->orWhereExists(fn ($areas) => $areas->select(DB::raw(1))->from('rider_areas')
                ->whereColumn('rider_areas.rider_id', $userIdColumn)
                ->whereIn('rider_areas.province', $provinces)));
    }

    /** May this staff member decide on / manage this rider? */
    private function coversRider(array $user, int $riderUserId): bool
    {
        $provinces = $this->riderProvinces($user);

        if ($provinces === null) {
            return true;
        }

        if ($provinces === []) {
            return false;
        }

        $query = DB::table('users')->where('users.id', $riderUserId);
        $this->riderInProvinces($query, $provinces);

        return $query->exists();
    }

    private const NOT_YOUR_RIDER = 'This rider belongs to another Sorting Center\'s area.';

    private const NOT_ASSIGNED = 'You are not assigned to a Sorting Center yet. Ask the BoomBuy admin to assign you.';

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

        if (!$this->coversRider($user, (int) $application->user_id)) {
            return back()->with('error', $this->isUnassigned($user) ? self::NOT_ASSIGNED : self::NOT_YOUR_RIDER);
        }

        if ($application->status !== 'Pending Verification') {
            return back()->with('error', 'This application was already ' . strtolower($application->status) . '.');
        }

        DB::table('rider_applications')->where('id', $id)->where('status', 'Pending Verification')->update([
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

        if (!$this->coversRider($user, (int) $application->user_id)) {
            return back()->with('error', $this->isUnassigned($user) ? self::NOT_ASSIGNED : self::NOT_YOUR_RIDER);
        }

        if ($application->status !== 'Pending Verification') {
            return back()->with('error', 'This application was already ' . strtolower($application->status) . '.');
        }

        DB::table('rider_applications')->where('id', $id)->where('status', 'Pending Verification')->update([
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

        abort_unless($this->coversRider($user, (int) $application->user_id), 403);

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

        if (!$this->coversRider($user, (int) $rider->id)) {
            return back()->with('error', $this->isUnassigned($user) ? self::NOT_ASSIGNED : self::NOT_YOUR_RIDER);
        }

        DB::table('users')->where('id', $userId)->update(['status' => $status]);

        createNotification(
            $rider->id,
            'Account Status Updated',
            "Your BoomBuy rider account status was changed to \"{$status}\" by the Logistics Center.",
            'account_status'
        );

        // Their parcels go to other riders instead of getting stuck.
        $note = $status === 'Active'
            ? ''
            : \App\Support\RiderRelease::summary(\App\Support\RiderRelease::release((int) $rider->id));

        return back()->with('success', $rider->name . '\'s account has been set to ' . $status . '.' . $note);
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
        ->paginate(20);

        $unreadCount = Notification::where('user_id', $user['id'])->whereNull('read_at')->count();

        return view(
            'pages.logistics.notifications',
            compact('user', 'notifications', 'unreadCount')
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
