<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ComplaintRequest;

class ComplaintController extends Controller
{
    public function index()
    {
        $user = session()->get('user');

        if (!$user) {
            return redirect()->route('login');
        }

        $myComplaints = Complaint::where('complainant_id', $user['id'])
            ->orderByDesc('created_at')
            ->get();

        $myId = (int) $user['id'];

        // Every order this person is part of — as its buyer, one of its
        // sellers, or its rider — so sellers and riders can pick theirs too.
        $myOrders = DB::table('orders')
            ->where(fn ($q) => $q->where('buyer_id', $myId)
                ->orWhere('rider_id', $myId)
                ->orWhere('delivery_rider_id', $myId)
                ->orWhereExists(fn ($items) => $items->select(DB::raw(1))->from('order_items')
                    ->whereColumn('order_items.order_id', 'orders.id')
                    ->where('order_items.seller_id', $myId)))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        // Who else is on each order, for "Who is this about?".
        $orderPeople = $myOrders->mapWithKeys(fn ($order) => [
            $order->id => collect($this->peopleOnOrder($order))->except($myId)->all(),
        ]);

        $againstNames = DB::table('users')
            ->whereIn('id', $myComplaints->pluck('against_user_id')->filter())
            ->pluck('name', 'id');

        return view(
            'pages.complaints',
            compact('user', 'myComplaints', 'myOrders', 'orderPeople', 'againstNames')
        );
    }

    /**
     * The people on an order, id => "Name (role)": its buyer, its sellers
     * (by shop name) and its delivery rider.
     *
     * @return array<int, string>
     */
    private function peopleOnOrder(object $order): array
    {
        $people = [];

        $buyer = DB::table('users')->where('id', $order->buyer_id)->value('name');
        if ($buyer) {
            $people[(int) $order->buyer_id] = $buyer . ' (buyer)';
        }

        $sellerIds = DB::table('order_items')->where('order_id', $order->id)->distinct()->pluck('seller_id')->filter();
        foreach (\App\Support\SellerShop::many($sellerIds) as $sellerId => $shop) {
            $people[(int) $sellerId] = ($shop['name'] ?? 'Seller') . ' (seller)';
        }

        $riderId = $order->delivery_rider_id ?: $order->rider_id;
        if ($riderId && ($rider = DB::table('users')->where('id', $riderId)->value('name'))) {
            $people[(int) $riderId] = $rider . ' (rider)';
        }

        return $people;
    }

    public function store(ComplaintRequest $request)
    {
        $user = session()->get('user');

        if (!$user) {
            return redirect()->route('login');
        }

        $orderId = request('order_id') ?: null;

        // Only an order the complainant is actually part of — as its buyer,
        // one of its sellers, or one of its riders.
        if ($orderId && !$this->isPartOfOrder((int) $orderId, (int) $user['id'])) {
            return back()->withInput()->with('error', 'You can only file a complaint about your own orders.');
        }

        // Who it is about: someone else on that same order, or nobody (a
        // platform issue). Picking a person needs the order they share.
        $againstId = request('against_user_id') ? (int) request('against_user_id') : null;

        if ($againstId) {
            $order = $orderId ? DB::table('orders')->where('id', $orderId)->first() : null;
            $people = $order ? $this->peopleOnOrder($order) : [];

            if ($againstId === (int) $user['id'] || !array_key_exists($againstId, $people)) {
                return back()->withInput()->with('error', 'Choose the related order first, then someone on that order.');
            }
        }

        $evidencePath = null;

        if (request()->hasFile('evidence')) {
            $evidencePath = request()->file('evidence')->store('complaints', 'local');
        }

        Complaint::create([
            'complainant_id' => $user['id'],
            'complainant_role' => $user['role'],
            'against_user_id' => $againstId,
            'order_id' => $orderId,
            'subject' => request('subject'),
            'description' => request('description'),
            'evidence' => $evidencePath,
            'status' => 'Pending',
        ]);

        return back()->with('success', 'Your complaint has been submitted. Our team will review it shortly.');
    }

    private function isPartOfOrder(int $orderId, int $userId): bool
    {
        $order = DB::table('orders')->where('id', $orderId)->first();

        if (!$order) {
            return false;
        }

        if (in_array($userId, array_map('intval', [$order->buyer_id, $order->rider_id, $order->delivery_rider_id]), true)) {
            return true;
        }

        return DB::table('order_items')
            ->where('order_id', $orderId)
            ->where('seller_id', $userId)
            ->exists();
    }
}
