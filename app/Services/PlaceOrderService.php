<?php

namespace App\Services;

use App\Exceptions\CheckoutFailed;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Voucher;
use App\Support\ChatAutomation;
use App\Support\CheckoutPlan;
use App\Support\CodPolicy;
use App\Support\OrderTimeline;
use App\Support\ParcelRoute;
use App\Support\PhLocations;
use Illuminate\Support\Facades\DB;

/**
 * Turns a buyer's cart lines into orders: one order per seller, each with its
 * own delivery fee, stock taken in the same transaction. Used by the web
 * checkout (CartController::placeOrder) and meant for the mobile API too.
 */
class PlaceOrderService
{
    /**
     * @param  array  $buyer    The session user (id, name).
     * @param  array  $cart     Cart key ("productId" or "productId:variationId") => quantity.
     * @param  array  $details  address, phone, payment.
     * @return int[]  The new order ids, first one first.
     *
     * @throws CheckoutFailed
     */
    public function place(array $buyer, array $cart, array $details, ?string $voucherCode = null): array
    {
        [$address, $phone, $payment, $location, $fulfillment] = $this->checkDetails($buyer, $details);

        $orderItems = $this->buildLines($cart);

        $voucher = $voucherCode ? Voucher::where('code', $voucherCode)->first() : null;

        // One order per seller; the voucher lands only on its own seller's
        // order, and each order carries its own delivery fee (by distance).
        $plan = CheckoutPlan::build($orderItems, $voucher, $location, $fulfillment);

        return $this->create($buyer, $plan, $voucher, $address, $phone, $payment, $location, $fulfillment);
    }

    /** Address, phone and payment method, including the COD pause. */
    private function checkDetails(array $buyer, array $details): array
    {
        $address = trim((string) ($details['address'] ?? ''));
        $phone = trim((string) ($details['phone'] ?? ''));
        $payment = trim((string) ($details['payment'] ?? ''));

        if ($address === '' || $phone === '' || $payment === '') {
            throw CheckoutFailed::inForm('Please complete all checkout information.');
        }

        // Same rule as the address book — the rider has to be able to call it.
        if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
            throw CheckoutFailed::inForm('Please enter a valid phone number (numbers only).');
        }

        if (mb_strlen($address) > 500) {
            throw CheckoutFailed::inForm('The delivery address is too long.');
        }

        // The town and province decide which Sorting Center delivers it.
        $location = PhLocations::locate($address);

        if (!$location) {
            throw CheckoutFailed::inForm(
                'We could not find your town in that address. Please include your city/municipality and province, e.g. "123 Rizal St., Poblacion, Santa Cruz, Laguna".'
            );
        }

        if (!in_array($payment, CodPolicy::PAYMENT_METHODS, true)) {
            throw CheckoutFailed::inForm('Please choose a valid payment method.');
        }

        // Door delivery, or pick-up at the buyer's Sorting Center (only when
        // there's one open nearby to hold it).
        $fulfillment = ($details['fulfillment'] ?? 'delivery') === 'pickup' ? 'pickup' : 'delivery';

        if ($fulfillment === 'pickup' && !ParcelRoute::centerFor($location['province'])) {
            throw CheckoutFailed::inForm('There is no BoomBuy Sorting Center near that address to pick up from yet. Please choose delivery.');
        }

        if (CodPolicy::isCod($payment)) {
            $codStatus = CodPolicy::status((int) $buyer['id']);

            if ($codStatus['blocked']) {
                throw CheckoutFailed::inForm(
                    'Cash on Delivery is paused on your account until ' .
                    $codStatus['available_at']->format('M d, Y') .
                    ' because of repeated cancellations or refused parcels. Please choose another payment method.'
                );
            }
        }

        return [$address, $phone, $payment, $location, $fulfillment];
    }

    /**
     * One line per cart entry, priced and checked: the product is still for
     * sale, the option still exists, there is enough stock (a quick check for
     * a friendly message; the real guard is in create()).
     */
    private function buildLines(array $cart): array
    {
        $lines = [];

        foreach ($cart as $cartKey => $quantity) {
            $quantity = (int) $quantity;

            if ($quantity <= 0) {
                continue;
            }

            [$productId, $variationId] = parseCartKey($cartKey);

            $product = Product::find($productId);

            if (!$product) {
                throw CheckoutFailed::inCart('One of the products could not be found.');
            }

            // The product may have been archived/flagged, or its variation
            // removed, since it went into the cart — this is the last gate.
            if (!$product->isPurchasable()) {
                throw CheckoutFailed::inCart($product->name . ' is no longer available. Please remove it from your cart.');
            }

            [$variation, $variationError] = $product->resolveVariation($variationId);

            if ($variationError || ($variationId && !$variation)) {
                throw CheckoutFailed::inCart(
                    $variationError ?? ('The option you picked for ' . $product->name . ' is no longer available. Please remove it and add it again.')
                );
            }

            $variationLabel = $variation ? $variation->variation_type . ': ' . $variation->variation_value : null;

            // A variation tracks its own stock; the product's stock only
            // applies when there is no variation.
            $stock = $variation ? (int) $variation->stock : (int) $product->stock;

            if ($stock < $quantity) {
                throw CheckoutFailed::inForm(
                    $product->name . ($variationLabel ? " ({$variationLabel})" : '') . ' does not have enough stock.'
                );
            }

            $unitPrice = (float) $product->price + (float) ($variation->price_adjustment ?? 0);

            if ($unitPrice <= 0) {
                throw CheckoutFailed::inCart($product->name . ' has an invalid price right now. Please contact the seller.');
            }

            $lines[] = [
                'product_id' => $product->id,
                'seller_id' => $product->seller_id,
                'product_name' => $product->name,
                // Transient: which stock row to reduce, not an order_items column.
                'variation_id' => $variation->id ?? null,
                'variation_label' => $variationLabel,
                // The option's photo as it was when bought.
                'variation_image' => $variation->image ?? null,
                'price' => $unitPrice,
                'quantity' => $quantity,
            ];
        }

        if (empty($lines)) {
            throw CheckoutFailed::inCart('No valid products found.');
        }

        return $lines;
    }

    /**
     * Orders, their lines, stock and the voucher use, all in one transaction.
     *
     * Each stock decrement is a conditional "UPDATE ... WHERE stock >= quantity",
     * applied by MySQL as one atomic row operation, so two checkouts racing for
     * the last unit can't both succeed. If any line loses the race, the whole
     * checkout (and its notifications) rolls back instead of overselling.
     */
    private function create(array $buyer, array $plan, ?Voucher $voucher, string $address, string $phone, string $payment, array $location, string $fulfillment): array
    {
        $voucherUsed = collect($plan['orders'])->contains(fn ($o) => $o['voucher_code'] !== null);

        $destination = ParcelRoute::centerFor($location['province'], $location['city']);

        return DB::transaction(function () use ($buyer, $plan, $voucher, $voucherUsed, $address, $phone, $payment, $location, $destination, $fulfillment) {
                if ($voucherUsed) {
                    // Conditional so two simultaneous checkouts can't push a
                    // voucher past its max_uses — the loser rolls back.
                    $claimed = Voucher::where('id', $voucher->id)
                        ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'))
                        ->increment('used_count');

                    if (!$claimed) {
                        throw new CheckoutFailed(
                            'That voucher just reached its usage limit. It has been removed — please review your total and place the order again.',
                            voucherDropped: true
                        );
                    }
                }

                $orderIds = [];

                foreach ($plan['orders'] as $planned) {
                    $seller = ParcelRoute::sellerLocation((int) $planned['seller_id']);
                    $origin = ParcelRoute::centerFor($seller['province'], $seller['city']);

                    $orderId = DB::table('orders')->insertGetId([
                        'buyer_id' => $buyer['id'],
                        'total_amount' => $planned['total'],
                        'voucher_code' => $planned['voucher_code'],
                        'discount_amount' => $planned['discount'],
                        'delivery_fee' => $planned['delivery_fee'],
                        'delivery_zone' => $planned['zone'],
                        'fulfillment' => $fulfillment,
                        // The seller's workflow starts here.
                        'status' => 'Pending',
                        'shipping_name' => $buyer['name'] ?? 'Buyer',
                        'shipping_phone' => $phone,
                        'shipping_address' => $address,
                        'shipping_province' => $location['province'],
                        'shipping_city' => $location['city'],
                        // The seller drops it at origin; the buyer's town center delivers it.
                        'origin_center_id' => $origin?->id,
                        'destination_center_id' => $destination?->id,
                        'payment_method' => $payment,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $orderIds[] = $orderId;

                    OrderTimeline::log((int) $orderId, 'Pending', 'Order placed', 'Payment: ' . $payment);

                    createNotification(
                        (int) $buyer['id'],
                        'Order Confirmed',
                        'Your order #' . $orderId . ' has been placed successfully and is now Pending.',
                        'order',
                        (int) $orderId
                    );

                    foreach ($planned['lines'] as $item) {
                        DB::table('order_items')->insert([
                            'order_id' => $orderId,
                            'product_id' => $item['product_id'],
                            'seller_id' => $item['seller_id'],
                            'product_name' => $item['product_name'],
                            'variation_label' => $item['variation_label'] ?? null,
                            'variation_image' => $item['variation_image'] ?? null,
                            'price' => $item['price'],
                            'quantity' => $item['quantity'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $decremented = !empty($item['variation_id'])
                            ? ProductVariation::where('id', $item['variation_id'])
                                ->where('stock', '>=', $item['quantity'])
                                ->decrement('stock', $item['quantity'])
                            : Product::where('id', $item['product_id'])
                                ->where('stock', '>=', $item['quantity'])
                                ->decrement('stock', $item['quantity']);

                        if (!$decremented) {
                            throw new CheckoutFailed(
                                $item['product_name'] . ' just sold out while you were checking out. Please adjust your cart and try again.',
                                voucherDropped: true
                            );
                        }
                    }

                    // One "new order" notification per seller, for their order only.
                    createNotification(
                        (int) $planned['seller_id'],
                        'New Order Received',
                        'You have a new order #' . $orderId . ' that is waiting for processing.',
                        'order',
                        (int) $orderId
                    );

                    ChatAutomation::orderUpdate((int) $orderId, 'placed');
                }

                return $orderIds;
            });
    }
}
