<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Complaint and return/refund evidence are private (people upload photos of
 * their parcels, receipts, sometimes their address). They live on the local
 * disk and are only shown to the people involved and the admin.
 */
class EvidenceController extends Controller
{
    public function complaint($id)
    {
        $complaint = DB::table('complaints')->where('id', $id)->first();

        abort_unless($complaint && $complaint->evidence, 404);

        $viewer = session('user');

        $allowed = session('admin_logged_in')
            || ($viewer && (int) $viewer['id'] === (int) $complaint->complainant_id);

        abort_unless($allowed, 403);

        return $this->serve($complaint->evidence);
    }

    public function returnRequest($id)
    {
        $request = DB::table('return_refund_requests')->where('id', $id)->first();

        abort_unless($request && $request->evidence, 404);

        $viewer = session('user');

        $allowed = session('admin_logged_in')
            || ($viewer && in_array((int) $viewer['id'], [(int) $request->buyer_id, (int) $request->seller_id], true));

        abort_unless($allowed, 403);

        return $this->serve($request->evidence);
    }

    /**
     * The rider's proof-of-delivery photo: for the buyer, the order's sellers,
     * the rider, logistics staff (head office, or the centers it went through)
     * and the admin.
     */
    public function deliveryProof($id)
    {
        $order = DB::table('orders')->where('id', $id)->first();

        abort_unless($order && $order->delivery_proof, 404);

        $viewer = session('user');
        $viewerId = (int) ($viewer['id'] ?? 0);

        $allowed = session('admin_logged_in')
            || ($viewer && in_array($viewerId, [(int) $order->buyer_id, (int) $order->delivery_rider_id], true))
            || ($viewer && DB::table('order_items')->where('order_id', $order->id)->where('seller_id', $viewerId)->exists());

        if (!$allowed && ($viewer['role'] ?? '') === 'logistics') {
            $center = DB::table('users')->where('id', $viewerId)->value('sorting_center_id');
            $allowed = !$center || in_array((int) $center, array_map('intval', array_filter([
                $order->origin_center_id, $order->destination_center_id, $order->current_center_id,
            ])), true);
        }

        abort_unless($allowed, 403);

        return $this->serve($order->delivery_proof);
    }

    private function serve(string $path)
    {
        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->response($path);
        }

        // Uploaded before evidence became private and not moved yet.
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->response($path);
        }

        abort(404);
    }
}
