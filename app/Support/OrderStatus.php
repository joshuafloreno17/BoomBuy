<?php

namespace App\Support;

/**
 * BoomBuy's order flow (the class's "ERP Flow"):
 *
 *   Pending (placed) → Confirmed (seller accepted) → Preparing (packing,
 *   shipping label) → Ready for Pickup → Pickup Assigned → Picked Up (rider
 *   collected it) → At Sorting Center → [In Transit → At Sorting Center, when
 *   the buyer is in another province] → Sorted (by destination area) →
 *   Assigned for Delivery → Out for Delivery → Delivered → Completed (buyer
 *   confirmed receipt). A failed delivery is rescheduled or returned to the
 *   seller (Returning → Return Ready → Returned to Seller).
 *
 * "Dropped Off" only remains on older orders, from when sellers brought
 * parcels to the Sorting Center themselves.
 */
class OrderStatus
{
    /** Delivered successfully — counts as a sale (with or without the buyer's confirmation). */
    public const DONE = ['Delivered', 'Completed'];

    /** Still with the seller: the seller (and admin) may cancel. */
    public const WITH_SELLER = ['Pending', 'Confirmed', 'Preparing'];

    /** Accepted by the seller and being packed. */
    public const SELLER_WORKING = ['Confirmed', 'Preparing'];

    /** Waiting for a rider to collect it from the seller. */
    public const AWAITING_PICKUP = ['Ready for Pickup', 'Pickup Assigned'];

    /** Waiting for the seller's Sorting Center to confirm it arrived. */
    public const ARRIVING_FROM_SELLER = ['Picked Up', 'Dropped Off'];
}
