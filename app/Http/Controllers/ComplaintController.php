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

        $myOrders = DB::table('orders')
            ->where('buyer_id', $user['id'])
            ->orderByDesc('created_at')
            ->get();

        return view(
            'pages.complaints',
            compact('user', 'myComplaints', 'myOrders')
        );
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

        $evidencePath = null;

        if (request()->hasFile('evidence')) {
            $evidencePath = request()->file('evidence')->store('complaints', 'local');
        }

        Complaint::create([
            'complainant_id' => $user['id'],
            'complainant_role' => $user['role'],
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
